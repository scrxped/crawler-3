<?php

namespace Zstate\Crawler;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zstate\Crawler\Event\AfterEngineStopped;
use Zstate\Crawler\Event\AfterRequestSent;
use Zstate\Crawler\Event\BeforeRequestSent;
use Zstate\Crawler\Event\RequestFailed;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Exception\InvalidRequestException;
use Zstate\Crawler\Middleware\MiddlewareStack;
use Zstate\Crawler\Service\RequestFingerprint;
use Zstate\Crawler\Storage\HistoryInterface;
use Zstate\Crawler\Storage\QueueInterface;

/**
 * @package Zstate\Crawler
 */
class Scheduler
{

    /**
     * @var array
     */
    private $pending = [];

    /**
     * @var int
     */
    private $concurrency;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HistoryInterface
     */
    private $history;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var MiddlewareStack
     */
    private $middlewareStack;

    /**
     * Configuration hash can include the following key value pairs:
     *
     * - concurrency: (integer) Pass this configuration option to limit the
     *   allowed number of outstanding concurrently executing promises,
     *   creating a capped pool of promises. There is no limit by default.
     *
     * @param ClientInterface $client
     * @param EventDispatcherInterface $eventDispatcher
     * @param HistoryInterface $history
     * @param QueueInterface $queue
     * @param int $concurrency
     */
    public function __construct(
        ClientInterface $client,
        EventDispatcherInterface $eventDispatcher,
        HistoryInterface $history,
        QueueInterface $queue,
        MiddlewareStack $middlewareStack,
        int $concurrency)
    {
        $this->concurrency = $concurrency;
        $this->client = $client;
        $this->history = $history;
        $this->queue = $queue;
        $this->eventDispatcher = $eventDispatcher;
        $this->middlewareStack = $middlewareStack;
    }

    public function __destruct()
    {
        $this->eventDispatcher->dispatch(AfterEngineStopped::class, new AfterEngineStopped);
    }

    public function run(): void
    {
        while(! $this->queue->isEmpty()) {

            $this->schedule();

            reset($this->pending);

            // Consume a potentially fluctuating list of promises while
            // ensuring that indexes are maintained (precluding array_shift).
            /** @var PromiseInterface $promise */
            while ($promise = current($this->pending)) {

                $idx = key($this->pending);
                next($this->pending);

                try {
                    $promise->wait();
                } catch (\Exception $e) {
                    unset($this->pending[$idx]);
                }
            }
        }
    }

    private function schedule(): void
    {
        if (! $this->concurrency) {
            while ($this->nextRequest());

            return;
        }

        // Add only up to N pending promises.
        $concurrency = $this->concurrency;
        $concurrency = max($concurrency - count($this->pending), 0);
        // Concurrency may be set to 0 to disallow new promises.
        if (!$concurrency) {
            return;
        }
        // Add the first pending promise.
        $this->nextRequest();
        // Note this is special handling for concurrency=1 so that we do
        // not advance the iterator after adding the first promise. This
        // helps work around issues with generators that might not have the
        // next value to yield until promise callbacks are called.
        while (--$concurrency && $this->nextRequest());
    }

    /**
     * @return bool
     */
    private function nextRequest(): bool
    {
        // If queue is empty, then idling and waiting
        if($this->queue->isEmpty()) {
            return false;
        }

        $request = $this->queue->dequeue();

        // If request is in the history, then idling
        if($this->history->contains($request)) {
            return false;
        }

        $this->eventDispatcher->dispatch(BeforeRequestSent::class, new BeforeRequestSent($request));

        $idx = RequestFingerprint::calculate($request);

        try {
            // Run request through the request middleware stack
            $request = $this->middlewareStack->getRequestMiddlewareStack()($request);

            $promise = $this->client->sendAsync($request);

            $this->eventDispatcher->dispatch(AfterRequestSent::class, new AfterRequestSent($request));

            $this->pending[$idx] = $promise->then(
                function (ResponseInterface $response) use ($idx, $request): ResponseInterface {

                    // Run response through the response middleware stack
                    $response = $this->middlewareStack->getResponseMiddlewareStack()($response, $request);

                    $this->eventDispatcher->dispatch(ResponseReceived::class, new ResponseReceived($response, $request));

                    $this->step($idx);

                    return $response;
                }
            )->otherwise(
                function (Exception $reason) use ($idx, $request) {
                    $this->eventDispatcher->dispatch(RequestFailed::class, new RequestFailed($reason, $request));
                    $this->step($idx);
                }
            );

            // Add request to the history
            $this->history->add($request);

            return true;

        } catch (InvalidRequestException $e) {
            $this->eventDispatcher->dispatch(RequestFailed::class, new RequestFailed($e, $request));
            // Skipping the request if it is invalid (For example, if the request is not allowed by robots.txt rule)
            return false;
        }
    }

    /**
     * @param $idx
     */
    private function step($idx): void
    {
        unset($this->pending[$idx]);

        $this->schedule();
    }
}
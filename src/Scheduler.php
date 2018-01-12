<?php

namespace Zstate\Crawler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\History;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\Service\RequestFingerprint;

class Scheduler
{

    private $pending = [];

    /** @var callable|int */
    private $concurrency;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var History
     */
    private $history;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var LinkExtractorInterface
     */
    private $linkExtractor;

    /**
     * Configuration hash can include the following key value pairs:
     *
     * - concurrency: (integer) Pass this configuration option to limit the
     *   allowed number of outstanding concurrently executing promises,
     *   creating a capped pool of promises. There is no limit by default.
     *
     * @param ClientInterface $client
     * @param mixed $iterable Promises or values to iterate.
     * @param array $config Configuration options
     */
    public function __construct(
        ClientInterface $client,
        History $history,
        Queue $queue,
        LinkExtractorInterface $linkExtractor,
        int $concurrency)
    {
        $this->concurrency = $concurrency;
        $this->client = $client;
        $this->history = $history;
        $this->queue = $queue;
        $this->linkExtractor = $linkExtractor;
    }

    public function run()
    {
        $this->schedule();

        reset($this->pending);
        if (empty($this->pending)) {
            return;
        }

        // Consume a potentially fluctuating list of promises while
        // ensuring that indexes are maintained (precluding array_shift).
        /** @var PromiseInterface $promise */
        while ($promise = current($this->pending)) {

            next($this->pending);

            try {
                $promise->wait();
            } catch (\Exception $e) {
                $promise->reject($e);
            }
        }
    }

    public function queue(RequestInterface $request): void
    {
        $this->queue->enqueue($request);
    }

    private function schedule()
    {
        if (! $this->concurrency) {
            // Add all pending promises.
            while ($this->nextRequest());

            return;
        }

        // Add only up to N pending promises.
        $concurrency = is_callable($this->concurrency)
            ? call_user_func($this->concurrency, count($this->pending))
            : $this->concurrency;
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

    private function nextRequest()
    {
        // If queue is empty, then idling and waiting on response, will timeout automatically
        if($this->queue->isEmpty()) {
            return true;
        }

        $request = $this->queue->dequeue();

        // If request is in the history, then idling
        if($this->history->contains($request)) {
            return true;
        }

        $idx = RequestFingerprint::calculate($request);
        $promise = $this->client->sendAsync($request);

        $this->pending[$idx] = $promise->then(
            function (ResponseInterface $response) use ($request, $idx): void {
                $this->extractAndQueueLinks($response, $request);
                $this->step($idx);
            }
        );

        // Add request to the history
        $this->history->add($request);

        return true;
    }

    private function step($idx)
    {
        unset($this->pending[$idx]);

        $this->schedule();
    }

    private function extractAndQueueLinks(ResponseInterface $response, RequestInterface $request)
    {
        $links = $this->linkExtractor->extract($response);

        foreach ($links as $extractedLink) {

            $currentUri = $request->getUri();
            $visitUri = UriResolver::resolve($currentUri, new Uri($extractedLink));

            $request = new Request('GET', $visitUri);

            // Adding referer header for logging purposes
            $request = $request->withHeader('Referer', (string) $currentUri);

            // Don't queue if the request is in the history
            // @todo: Investigate why it timeouts earlier when removing the condition
            if(! $this->history->contains($request)) {
                $this->queue->enqueue($request);
            }
        }
    }
}
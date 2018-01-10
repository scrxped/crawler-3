<?php

namespace Zstate\Crawler;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Repository\History;
use Zstate\Crawler\Service\LinkExtractorInterface;

class Scheduler
{

    private $pending = [];

    /** @var \Iterator */
    private $iterable;

    /** @var callable|int */
    private $concurrency;

    /** @var callable */
    private $onRejected;

    /** @var bool */
    private $mutex;
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
        UriIterator $iterable,
        History $history,
        Queue $queue,
        LinkExtractorInterface $linkExtractor,
        int $concurrency)
    {
        $this->iterable = $iterable;
        $this->concurrency = $concurrency;
        $this->client = $client;
        $this->history = $history;
        $this->queue = $queue;
        $this->linkExtractor = $linkExtractor;
    }

    public function run()
    {
        $this->refillPending();

        reset($this->pending);
        if (empty($this->pending) && !$this->iterable->valid()) {
            return;
        }

        // Consume a potentially fluctuating list of promises while
        // ensuring that indexes are maintained (precluding array_shift).
        while ($promise = current($this->pending)) {

            next($this->pending);

            $promise->wait();
        }
    }

    private function refillPending()
    {
        if (! $this->concurrency) {
            // Add all pending promises.
            while ($this->addPending());

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
        $this->addPending();
        // Note this is special handling for concurrency=1 so that we do
        // not advance the iterator after adding the first promise. This
        // helps work around issues with generators that might not have the
        // next value to yield until promise callbacks are called.
        while (--$concurrency && $this->addPending());
    }

    private function addPending()
    {
        if (! $this->iterable->valid()) {
            return false;
        }

        $this->advanceIterator();

        $uri = $this->iterable->current();
        $idx = $this->iterable->key();

        // Waiting on response
        if($uri === null) {
            return true;
        }

        $request = new Request("GET", $uri);
        $promise = $this->client->sendAsync($request);

        $this->pending[$idx] = $promise->then(
            function (ResponseInterface $response) use ($request, $idx): void {
                echo $request->getUri() . ": " . $response->getStatusCode() . "\n";
                $this->extractAndQueueLinks($response, $request);
                $this->step($idx);
            }
        );

        $this->history->add($request);

        return true;
    }

    private function advanceIterator()
    {
        $this->iterable->next();
    }

    private function step($idx)
    {
        unset($this->pending[$idx]);

        if (! $this->isFinished()) {

            $this->refillPending();
        }
    }

    private function isFinished()
    {
        if (! $this->pending && ! $this->iterable->valid()) {
            return true;
        }

        return false;
    }

    private function extractAndQueueLinks(ResponseInterface $response, RequestInterface $request)
    {
        $links = $this->linkExtractor->extract($response);

        foreach ($links as $extractedLink) {

            $visitUri = UriResolver::resolve(new Uri($request->getUri()), new Uri($extractedLink));

            $request = new Request('GET', $visitUri);
            if (! $this->history->contains($request)) {
                $this->queue->enqueue($request->getUri());
            }
        }
    }
}
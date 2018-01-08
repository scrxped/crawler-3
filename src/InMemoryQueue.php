<?php

namespace Zstate\Crawler;


use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Zstate\Crawler\Service\RequestFingerprint;

class InMemoryQueue implements Queue
{
    private $queue = [];

    public function enqueue(UriInterface $uri): void
    {
        $uri = (string) RequestFingerprint::normalizeUri($uri);

        if(! $this->isUriInQueue($uri)) {
            $this->queue[] = $uri;
        }

    }

    public function dequeue(): UriInterface
    {
        $uri =  array_shift($this->queue);

        return new Uri($uri);
    }

    public function isEmpty(): bool
    {
        return count($this->queue) === 0;
    }

    /**
     * @param string $uri
     * @return bool
     */
    private function isUriInQueue(string $uri): bool
    {
        return in_array($uri, $this->queue);
    }
}
<?php

namespace Zstate\Crawler;


use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;

class InMemoryQueue implements Queue
{
    private $queue = [];

    public function enqueue(RequestInterface $request): void
    {
        $fingerprint = RequestFingerprint::calculate($request);

        if(! $this->isRequestInQueue($fingerprint)) {
            $this->queue[$fingerprint] = $request;
        }

    }

    public function dequeue(): RequestInterface
    {
        $request =  array_shift($this->queue);

        return $request;
    }

    public function isEmpty(): bool
    {
        return count($this->queue) === 0;
    }

    /**
     * @param string $fingerprint
     * @return bool
     */
    private function isRequestInQueue(string $fingerprint): bool
    {
        return array_key_exists($fingerprint, $this->queue);
    }
}
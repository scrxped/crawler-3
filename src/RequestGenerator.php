<?php

namespace Zstate\Crawler;


use BadMethodCallException;
use GuzzleHttp\Psr7\Request;
use Iterator;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Repository\History;

class RequestGenerator implements Iterator
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var RequestInterface | null
     */
    private $current = null;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var float
     */
    private $lastFailedIterationTime = 0;

    /**
     * @var float
     */
    private $lastSuccessfulIterationTime = 0;

    public function __construct(Queue $queue, int $timeout)
    {
        $this->queue = $queue;
        $this->timeout = $timeout;
    }


    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current(): ? RequestInterface
    {
        return $this->current;
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next(): void
    {
        $this->key++;

        if(! $this->queue->isEmpty()) {
            $this->lastSuccessfulIterationTime = microtime(true);

            $uri = $this->queue->dequeue();

            $this->current = new Request('GET' , $uri);

        } else {
            $this->lastFailedIterationTime = microtime(true);

            $this->current = null;
        }
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid(): bool
    {
        if(! $this->queue->isEmpty() || ! $this->isTimeout()) {
            return true;
        }

        return false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind(): void
    {
        throw new BadMethodCallException("RequestGenerator is forward-only iterator, and cannot be rewound once iteration has started.");
    }

    /**
     * @return bool
     */
    private function isTimeout(): bool
    {
        return ($this->lastFailedIterationTime - $this->lastSuccessfulIterationTime >= $this->timeout);
    }
}
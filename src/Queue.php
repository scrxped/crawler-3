<?php

namespace Zstate\Crawler;


use Psr\Http\Message\RequestInterface;

interface Queue
{
    public function enqueue(RequestInterface $request): void;

    public function dequeue(): RequestInterface;

    public function isEmpty(): bool;
}
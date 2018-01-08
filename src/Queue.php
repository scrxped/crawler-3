<?php

namespace Zstate\Crawler;


use Psr\Http\Message\UriInterface;

interface Queue
{
    public function enqueue(UriInterface $request): void;

    public function dequeue(): UriInterface;

    public function isEmpty(): bool;
}
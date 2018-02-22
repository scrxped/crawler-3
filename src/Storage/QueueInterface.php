<?php
declare(strict_types=1);

namespace Zstate\Crawler\Storage;

use Psr\Http\Message\RequestInterface;

interface QueueInterface
{
    public function enqueue(RequestInterface $request): void;

    public function dequeue(): RequestInterface;

    public function isEmpty(): bool;
}
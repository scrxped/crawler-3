<?php
declare(strict_types=1);

namespace Zstate\Crawler\Storage;

use Psr\Http\Message\RequestInterface;

interface HistoryInterface
{
    public function contains(RequestInterface $request): bool;

    public function add(RequestInterface $request): void;
}
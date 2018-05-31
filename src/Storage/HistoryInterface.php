<?php
declare(strict_types=1);

namespace Zstate\Crawler\Storage;

use Psr\Http\Message\RequestInterface;

/**
 * @package Zstate\Crawler\Storage
 */
interface HistoryInterface
{
    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function contains(RequestInterface $request): bool;

    /**
     * @param RequestInterface $request
     */
    public function add(RequestInterface $request): void;
}
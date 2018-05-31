<?php

namespace Zstate\Crawler\Middleware;

use Psr\Http\Message\RequestInterface;

/**
 * @package Zstate\Crawler\Middleware
 */
interface RequestMiddleware
{
    /**
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function processRequest(RequestInterface $request): RequestInterface;
}

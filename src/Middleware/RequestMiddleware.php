<?php

namespace Zstate\Crawler\Middleware;


use Psr\Http\Message\RequestInterface;

interface RequestMiddleware
{
    /**
     * Be careful when modifying the request in your middleware. If you change the request URI, then the URI resolution might
     * not work properly for some pages.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function processRequest(RequestInterface $request): RequestInterface;
}
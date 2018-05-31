<?php

namespace Zstate\Crawler\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Zstate\Crawler\Middleware
 */
interface ResponseMiddleware
{
    /**
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function processResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface;
}

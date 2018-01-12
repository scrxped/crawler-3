<?php

namespace Zstate\Crawler\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Middleware
{
    /**
     * @param RequestInterface $request
     * @param array $options
     * @return RequestInterface
     */
    public function processRequest(RequestInterface $request, array $options): RequestInterface;

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Just like try/catch, you can choose to propagate or not by returning $reason or throwing an Exception
     * @param RequestInterface $request
     * @param Exception $reason
     * @return Exception
     */
    public function processFailure(RequestInterface $request, Exception $reason): Exception;
}

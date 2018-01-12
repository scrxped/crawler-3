<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;

class MiddlewareWithExceptionInProcessRequest extends BaseMiddleware
{
    public function processRequest(RequestInterface $request, array $options): RequestInterface
    {
        throw new Exception('Exception in MiddlewareWithExceptionInProcessRequest::processRequest');
    }
}

<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;

class MiddlewareWithExceptionInProcessResponse extends BaseMiddleware
{
    public function processResponse(RequestInterface $request, ResponseInterface $response)
    {
        throw new Exception('Exception in MiddlewareWithExceptionInProcessResponse::processResponse');
    }
}

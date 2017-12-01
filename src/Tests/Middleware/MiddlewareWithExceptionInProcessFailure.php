<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;

class MiddlewareWithExceptionInProcessFailure extends BaseMiddleware
{
    public function processFailure(RequestInterface $request, Exception $reason)
    {
        throw new Exception('Exception in MiddlewareWithExceptionInProcessFailure::processFailure');
    }
}

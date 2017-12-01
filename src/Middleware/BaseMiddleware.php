<?php

namespace Zstate\Crawler\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseMiddleware implements Middleware
{
    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request, array $options)
    {
        return $request;
    }

    /**
     * @inheritdoc
     */
    public function processResponse(RequestInterface $request, ResponseInterface $response)
    {
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function processFailure(RequestInterface $request, Exception $reason)
    {
        return $reason;
    }
}

<?php
declare(strict_types=1);

namespace Zstate\Crawler\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseMiddleware implements Middleware
{
    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request, array $options): RequestInterface
    {
        return $request;
    }

    /**
     * @inheritdoc
     */
    public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function processFailure(RequestInterface $request, Exception $reason): Exception
    {
        return $reason;
    }
}

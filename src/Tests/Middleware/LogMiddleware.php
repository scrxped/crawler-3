<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\ResponseMiddleware;

class LogMiddleware implements ResponseMiddleware
{
    private $log = [];

    public function processResponse(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $this->log[] = "Process Response: " . (string)$request->getUri() . " status:" . $response->getStatusCode();

        return $response;
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }
}

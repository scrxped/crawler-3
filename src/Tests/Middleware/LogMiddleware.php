<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Middleware\Middleware;

class LogMiddleware implements Middleware
{
    private $log = [];

    public function processRequest(RequestInterface $request, array $options)
    {
        $this->log[] = "Process Request: {$request->getMethod()} " . (string)$request->getUri();

        return $request;
    }

    public function processResponse(RequestInterface $request, ResponseInterface $response)
    {
        $this->log[] = "Process Response: " . (string)$request->getUri() . " status:" . $response->getStatusCode();

        return $response;
    }

    public function processFailure(RequestInterface $request, Exception $reason)
    {
        $reasonMessage = $reason->getMessage();

        $this->log[] = "Process Failure: " . $reasonMessage;

        return $reason;
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }
}

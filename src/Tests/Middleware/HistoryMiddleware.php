<?php

namespace Zstate\Crawler\Tests\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Middleware\BaseMiddleware;

class HistoryMiddleware extends BaseMiddleware
{
    private $history = [];

    public function processRequest(RequestInterface $request, array $options)
    {
        $stream = $request->getBody();

        $requestBody = (string) $stream;

        $stream->rewind();

        $history = trim($request->getMethod() . " " . (string) $request->getUri() . " " . $requestBody);

        $this->history[] = $history;

        return parent::processRequest($request, $options);
    }

    public function getHistory()
    {
        return $this->history;
    }

    public function processFailure(RequestInterface $request, Exception $reason)
    {
        $reasonMessage = $reason->getMessage();

        return $reason;
    }
}

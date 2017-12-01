<?php

namespace Zstate\Crawler\Middleware;

use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Exception\IgnoreRequestException;
use Zstate\Crawler\Repository\History;

class DuplicateRequestFilter extends BaseMiddleware
{
    /**
     * @var History
     */
    private $history;

    /**
     * DuplicateRequestFilter constructor.
     * @param History $history
     */
    public function __construct(History $history)
    {
        $this->history = $history;
    }

    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request, array $options)
    {
        if (! $this->history->contains($request)) {
            $this->history->add($request);
            return $request;
        }
        //Ignore Request, stop propagation
        throw new IgnoreRequestException("Link is visited already: {$request->getUri()}");
    }
}

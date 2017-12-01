<?php

namespace Zstate\Crawler\Handler;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise;

class MockHandler implements Handler
{
    private $handler;

    /**
     * MockHandler constructor.
     * @param array $queue
     */
    public function __construct(array $queue)
    {
        $this->handler = new \GuzzleHttp\Handler\MockHandler($queue);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        Promise\queue()->run();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        return $this->handler->__invoke($request, $options);
    }
}

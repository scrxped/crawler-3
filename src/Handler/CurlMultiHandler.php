<?php

namespace Zstate\Crawler\Handler;

use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise;

class CurlMultiHandler implements Handler
{
    /**
     * @var GuzzleCurlMultiHandler
     */
    private $handler;

    /**
     * CurlMultiHandler constructor.
     * @param GuzzleCurlMultiHandler $handler
     */
    public function __construct(GuzzleCurlMultiHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->handler->execute();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        return $this->handler->__invoke($request, $options);
    }
}

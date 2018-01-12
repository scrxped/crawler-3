<?php

namespace Zstate\Crawler\Handler;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;

interface Handler
{
    /**
     * @return void
     */
    public function execute(): void;

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface;
}

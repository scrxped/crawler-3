<?php

namespace Zstate\Crawler\Middleware;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Queue;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * @var array
     */
    private $authOptions;
    /**
     * @var Queue
     */
    private $queue;

    public function __construct(Queue $queue, array $authOptions)
    {
        $this->authOptions = $authOptions;
        $this->queue = $queue;
    }

    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request, array $options)
    {
        $authOptions = $this->authOptions;

        if ($this->isLoginPage($request, $authOptions['loginUri'])) {
            $request = new Request('POST', $authOptions['loginUri']);

            $this->queue->enqueue($request);
        }

        return $request;
    }

    /**
     * @param RequestInterface $request
     * @param $loginUri
     * @return bool
     */
    private function isLoginPage(RequestInterface $request, string $loginUri): bool
    {
        $currentUri = (string) $request->getUri();
        return strpos($currentUri, $loginUri) !== false && $request->getMethod() === 'GET';
    }
}

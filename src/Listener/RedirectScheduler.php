<?php

namespace Zstate\Crawler\Listener;


use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Queue;

class RedirectScheduler
{
    /**
     * @var Queue
     */
    private $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if(! $this->isRedirect($response)) {
            return;
        }

        $redirectRequest = $this->redirectRequest($request, $response);

        $this->queue->enqueue($redirectRequest);
    }

    private function redirectRequest(RequestInterface $request, ResponseInterface $response, array $protocols = []): RequestInterface
    {
        $location = UriResolver::resolve($request->getUri(), new Uri($response->getHeaderLine('Location')));

        $redirectRequest = new Request('GET', $location);

        return $redirectRequest;
    }

    private function isRedirect(ResponseInterface $response)
    {
        if (substr($response->getStatusCode(), 0, 1) != '3' || !$response->hasHeader('Location')) {
            return false;
        }

        return true;
    }
}
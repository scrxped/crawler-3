<?php
declare(strict_types=1);

namespace Zstate\Crawler\Extension;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Event\ResponseReceived;
use function Zstate\Crawler\is_redirect;
use Zstate\Crawler\Storage\QueueInterface;

/**
 * @package Zstate\Crawler\Extension
 */
class RedirectScheduler extends Extension
{
    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @param QueueInterface $queue
     */
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if (! is_redirect($response)) {
            return;
        }

        $redirectRequest = $this->redirectRequest($request, $response);

        $this->queue->enqueue($redirectRequest);
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $protocols
     * @return RequestInterface
     */
    private function redirectRequest(RequestInterface $request, ResponseInterface $response, array $protocols = []): RequestInterface
    {
        $location = UriResolver::resolve($request->getUri(), new Uri($response->getHeaderLine('Location')));

        $redirectRequest = new Request('GET', $location);

        return $redirectRequest;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseReceived::class => 'responseReceived'
        ];
    }
}

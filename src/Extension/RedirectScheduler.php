<?php
declare(strict_types=1);

namespace Zstate\Crawler\Extension;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Event\ResponseReceived;
use function Zstate\Crawler\is_redirect;
use Zstate\Crawler\Policy\UriPolicy;
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
     * @var UriPolicy
     */
    private $policy;

    /**
     * @param QueueInterface $queue
     */
    public function __construct(QueueInterface $queue, UriPolicy $policy)
    {
        $this->queue = $queue;
        $this->policy = $policy;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if (! is_redirect($response)) {
            return;
        }

        $redirectRequest = $this->createRedirectRequest($request, $response);

        // Queue only redirects, which are allowed by filtering policy
        if($this->policy->isUriAllowed(new AbsoluteUri($redirectRequest->getUri()))) {
            $this->queue->enqueue($redirectRequest);
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $protocols
     * @return RequestInterface
     */
    private function createRedirectRequest(RequestInterface $request, ResponseInterface $response, array $protocols = []): RequestInterface
    {
        $location = UriResolver::resolve($request->getUri(), new Uri($response->getHeaderLine('Location')));

        return new Request('GET', $location);
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

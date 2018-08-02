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

/**
 * @package Zstate\Crawler\Extension
 */
class RedirectScheduler extends Extension
{
    /**
     * @var UriPolicy
     */
    private $policy;

    /**
     * RedirectScheduler constructor.
     * @param UriPolicy $policy
     */
    public function __construct(UriPolicy $policy)
    {
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
            $this->getQueue()->enqueue($redirectRequest);
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

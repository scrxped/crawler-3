<?php

namespace Zstate\Crawler\Middleware;

use GuzzleHttp\ClientInterface;
use function GuzzleHttp\Promise\is_rejected;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Zstate\Crawler\Service\LinkExtractorInterface;

class RequestScheduler extends BaseMiddleware
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var LinkExtractorInterface
     */
    private $uriExtractor;

    /**
     * RequestScheduler constructor.
     * @param ClientInterface $client
     * @param LinkExtractorInterface $uriExtractor
     */
    public function __construct(ClientInterface $client, LinkExtractorInterface $uriExtractor)
    {
        $this->client = $client;
        $this->uriExtractor = $uriExtractor;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    private function isRedirection(ResponseInterface $response)
    {
        return $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
    }

    /**
     * @param UriInterface $uri
     * @return PromiseInterface
     */
    public function schedule(UriInterface $uri)
    {
        if ($uri->getScheme() === '') {
            throw new \InvalidArgumentException('URI must be absolute.');
        }

        /** @var PromiseInterface $promise */
        $promise = $this->client->requestAsync('GET', (string) $uri);

        return $promise;
    }

    /**
     * @inheritdoc
     */
    public function processResponse(RequestInterface $request, ResponseInterface $response)
    {
        // Redirect responses might have HTML with a redirect link which scheduler must ignore
        if (! $this->isRedirection($response)) {
            $uris = $this->uriExtractor->extract($response);
            foreach ($uris as $uri) {
                $visitUri = UriResolver::resolve($request->getUri(), new Uri($uri));
                $this->schedule($visitUri);
            }
        }

        return $response;
    }
}

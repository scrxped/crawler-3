<?php
declare(strict_types=1);

namespace Zstate\Crawler\Extension;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Exception\InvalidRequestException;
use Zstate\Crawler\Policy\UriPolicy;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\Storage\QueueInterface;

/**
 * @package Zstate\Crawler\Extension
 */
class ExtractAndQueueLinks extends Extension
{
    private const REQUEST_DEPTH_HEADER = 'X-Crawler-Request-Depth';

    /**
     * @var LinkExtractorInterface
     */
    private $linkExtractor;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var UriPolicy
     */
    private $policy;
    /**
     * @var int|null
     */
    private $depth;

    /**
     * @param LinkExtractorInterface $linkExtractor
     * @param UriPolicy $policy
     * @param QueueInterface $queue
     * @param int|null $depth
     */
    public function __construct(LinkExtractorInterface $linkExtractor, UriPolicy $policy, QueueInterface $queue, ? int $depth)
    {
        $this->linkExtractor = $linkExtractor;
        $this->queue = $queue;
        $this->policy = $policy;
        $this->depth = $depth;
    }

    /**
     * @param ResponseReceived $event
     */
    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $currentRequest = $event->getRequest();

        $links = $this->linkExtractor->extract($response);
        $currentUri = $currentRequest->getUri();

        foreach ($links as $extractedLink) {
            $nextUriToVisit = UriResolver::resolve($currentUri, new Uri($extractedLink));

            if (! $this->policy->isUriAllowed(new AbsoluteUri($nextUriToVisit))) {
                continue;
            }

            $nextRequest = new Request('GET', $nextUriToVisit);

            // @todo: Add option to controll Referer header
            // Add referer header for logging purposes
            /** @var Request $nextRequest */
            $nextRequest = $nextRequest->withHeader('Referer', (string) $currentUri);

            $nextRequest = $this->validateAndTrackRequestDepth($currentRequest, $nextRequest);

            $this->queue->enqueue($nextRequest);
        }
    }

    /**
     * @param $request
     * @param $nextRequest
     * @return RequestInterface
     */
    private function validateAndTrackRequestDepth(RequestInterface $request, RequestInterface $nextRequest): RequestInterface
    {
        if ($this->depth) {
            $currentRequestDepth = $this->getRequestDepth($request);
            if ($currentRequestDepth === $this->depth) {
                throw new InvalidRequestException('The crawl depth is reached');
            }

            $nextRequest = $this->addRequestDepthHeader($currentRequestDepth, $nextRequest);
        }

        return $nextRequest;
    }

    private function addRequestDepthHeader(int $currentRequestDepth, RequestInterface $request): RequestInterface
    {
        $nextRequestDepth = $currentRequestDepth + 1;

        $request = $request->withHeader(self::REQUEST_DEPTH_HEADER, $nextRequestDepth);

        return $request;
    }

    private function getRequestDepth(RequestInterface $request): int
    {
        return (int) $request->getHeaderLine(self::REQUEST_DEPTH_HEADER);
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

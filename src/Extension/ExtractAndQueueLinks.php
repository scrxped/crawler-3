<?php
declare(strict_types=1);

namespace Zstate\Crawler\Extension;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Policy\UriPolicy;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\Storage\QueueInterface;

/**
 * @package Zstate\Crawler\Extension
 */
class ExtractAndQueueLinks extends Extension
{
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
     * @param LinkExtractorInterface $linkExtractor
     * @param UriPolicy $policy
     * @param QueueInterface $queue
     */
    public function __construct(LinkExtractorInterface $linkExtractor, UriPolicy $policy, QueueInterface $queue)
    {
        $this->linkExtractor = $linkExtractor;
        $this->queue = $queue;
        $this->policy = $policy;
    }

    /**
     * @param ResponseReceived $event
     */
    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $links = $this->linkExtractor->extract($response);
        $currentUri = $request->getUri();

        foreach ($links as $extractedLink) {
            $visitUri = UriResolver::resolve($currentUri, new Uri($extractedLink));
            $absoluteUri  =  new AbsoluteUri($visitUri);

            if (! $this->policy->isUriAllowed($absoluteUri)) {
                continue;
            }

            $request = new Request('GET', $visitUri);

            // Add referer header for logging purposes
            /** @var Request $request */
            $request = $request->withHeader('Referer', (string) $currentUri);

            $this->queue->enqueue($request);
        }
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

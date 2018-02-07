<?php

namespace Zstate\Crawler\Listener;


use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\Storage\QueueInterface;

class ExtractAndQueueLinks
{
    /**
     * @var LinkExtractorInterface
     */
    private $linkExtractor;
    /**
     * @var QueueInterface
     */
    private $queue;

    public function __construct(LinkExtractorInterface $linkExtractor, QueueInterface $queue)
    {
        $this->linkExtractor = $linkExtractor;
        $this->queue = $queue;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $links = $this->linkExtractor->extract($response);
        $currentUri = $request->getUri();

        foreach ($links as $extractedLink) {


            $visitUri = UriResolver::resolve($currentUri, new Uri($extractedLink));

            $request = new Request('GET', $visitUri);

            // Add referer header for logging purposes
            $request = $request->withHeader('Referer', (string) $currentUri);

            $this->queue->enqueue($request);
        }

    }
}
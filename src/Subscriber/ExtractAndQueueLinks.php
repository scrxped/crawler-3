<?php
declare(strict_types=1);

namespace Zstate\Crawler\Subscriber;


use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\Storage\QueueInterface;

class ExtractAndQueueLinks implements EventSubscriberInterface
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

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ResponseReceived::class => 'responseReceived'
        ];
    }
}
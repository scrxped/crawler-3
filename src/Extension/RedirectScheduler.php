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

class RedirectScheduler extends Extension
{
    /**
     * @var QueueInterface
     */
    private $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function responseReceived(ResponseReceived $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if(! is_redirect($response)) {
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

    public static function getSubscribedEvents(): array
    {
        return [
            ResponseReceived::class => 'responseReceived'
        ];
    }
}
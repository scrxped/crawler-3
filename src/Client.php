<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Uri;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
use Zstate\Crawler\Middleware\AuthMiddleware;
use Zstate\Crawler\Middleware\DuplicateRequestFilter;
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\MiddlewareWrapper;
use Zstate\Crawler\Middleware\RequestScheduler;
use Zstate\Crawler\Repository\History;
use Zstate\Crawler\Repository\InMemoryHistory;
use Zstate\Crawler\Service\LinkExtractor;
use Zstate\Crawler\Service\LinkExtractorInterface;
use Zstate\Crawler\UriIterator;

class Client
{
    /**
     * @var CurlMultiHandler
     */
    private $handler;

    /**
     * @var HandlerStack
     */
    private $stack;

    /**
     * @var History
     */
    private $history;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var RequestScheduler
     */
    private $scheduler;

    /**
     * @var array
     */
    private $configuration;
    /**
     * @var Queue
     */
    private $queue;


    public function __construct(
        Handler $handler,
        History $history,
        Queue $queue,
        LinkExtractorInterface $linkExtractor,
        array $configuration
    )
    {
        $this->stack = HandlerStack::create($handler);
        $this->handler = $handler;
        $this->history = $history;
        $this->configuration = $configuration;
        $this->queue = $queue;

        $config = $this->configureDefaults($configuration);
        $iterator = new UriIterator($queue,5);

        $this->httpClient = new GuzzleHttpClient($config);

        $this->scheduler = new Scheduler(
            $this->httpClient,
            $iterator,
            $history,
            $queue,
            $linkExtractor,
            $config['concurrency'] ?? 1
        );
    }

    public static function create(array $config): self
    {
        $handler = new CurlMultiHandler(new GuzzleCurlMultiHandler);
        $linkExtractor = LinkExtractor::fromConfig($config);

        $crawler = new self($handler, new InMemoryHistory, new InMemoryQueue, $linkExtractor, $config);

        return $crawler;
    }

    /**
     * Push a middleware to the top of the stack
     *
     * @param Middleware $middleware
     */
    public function addMiddleware(Middleware $middleware): void
    {
        $middlewareCallable = new MiddlewareWrapper($middleware);

        $this->stack->push($middlewareCallable, get_class($middleware));
    }

    /**
     * @param array $authOptions
     * [
     *   'loginUri' => 'http://site2.local/admin/login.php',
     *   'form_params' => ['username' => 'test', 'password' => 'password']
     * ]
     * @return Client
     */
    public function withAuth(array $authOptions): self
    {
        // Override start url
        $this->configuration['start_url'] = $authOptions['loginUri'];

        $this->addMiddleware(new AuthMiddleware($this->httpClient, $authOptions));

        return $this;
    }

    public function withLog(Middleware $middleware): void
    {
        $this->stack->unshift(new MiddlewareWrapper($middleware), 'logger');
    }

    public function run(): void
    {
        if (! isset($this->configuration['start_url'])) {
            throw new \RuntimeException('Please specify the start URI.');
        }

        $this->queue->enqueue(new Uri($this->configuration['start_url']));

        $this->scheduler->run();
    }

    /**
     * @param array $config
     * @return array
     */
    private function configureDefaults(array $config): array
    {
        $configuration = [
            'debug' => false,
            'verify' => false,
            'cookies' => true
        ];

        $configuration = array_merge($configuration, $config);

        $configuration['handler'] = $this->stack;

        return $configuration;
    }
}

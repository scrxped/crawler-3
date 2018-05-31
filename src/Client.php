<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Event\ResponseHeadersReceived;
use Zstate\Crawler\Event\TransferStatisticReceived;
use Zstate\Crawler\Extension\AutoThrottle;
use Zstate\Crawler\Extension\Extension;
use Zstate\Crawler\Extension\ExtractAndQueueLinks;
use Zstate\Crawler\Extension\RedirectScheduler;
use Zstate\Crawler\Extension\Storage;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
use Zstate\Crawler\Handler\HandlerStack;
use Zstate\Crawler\Middleware\MiddlewareStack;
use Zstate\Crawler\Middleware\RequestMiddleware;
use Zstate\Crawler\Middleware\ResponseMiddleware;
use Zstate\Crawler\Middleware\RobotsTxtMiddleware;
use Zstate\Crawler\Policy\AggregateUriPolicy;
use Zstate\Crawler\Service\LinkExtractor;
use Zstate\Crawler\Service\StorageService;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;
use Zstate\Crawler\Storage\History;
use Zstate\Crawler\Storage\HistoryInterface;
use Zstate\Crawler\Storage\Queue;
use Zstate\Crawler\Storage\QueueInterface;

/**
 * @package Zstate\Crawler
 */
class Client
{
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var array
     */
    private $config;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var SqliteAdapter
     */
    private $storageAdapter;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var HistoryInterface
     */
    private $history;

    /**
     * @var Handler
     */
    private $handler;

    /**
     * @var array
     */
    private $extentions = [];

    /**
     * @var Session
     */
    private $session;

    /**
     * @var MiddlewareStack
     */
    private $middlewareStack;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->initializeConfig($config);
        $this->initializeStorageAdapter();
        $this->initializeQueue();
        $this->initializeHistory();
        $this->initializeEventDispatcher();
        $this->initializeHandlerStack();
        $this->initializeHttpClient();
        $this->initializeSession();
        $this->initializeDefaultExtentions();
        $this->initializeMiddlewareStack();
        $this->initializeScheduler();
    }

    /**
     * @param array $config
     */
    private function initializeConfig(array $config)
    {
        $this->config = Config::fromArray($config);
    }

    /**
     * @return array
     */
    private function getConfig(): Config
    {
        return $this->config;
    }

    private function initializeStorageAdapter(): void
    {
        $config = $this->getConfig();
        $dsn = $config->saveProgressIn();

        $this->storageAdapter = SqliteAdapter::create($dsn);
    }

    private function getStorageAdapter(): SqliteAdapter
    {
        return $this->storageAdapter;
    }

    private function initializeQueue(): void
    {
        $this->queue = new Queue($this->getStorageAdapter());
    }

    private function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    private function initializeHistory(): void
    {
        $this->history = new History($this->getStorageAdapter());
    }

    /**
     * @return HistoryInterface
     */
    private function getHistory(): HistoryInterface
    {
        return $this->history;
    }

    public function setHandler(Handler $handler): void
    {
        $this->handler = $handler;

        $this->getHandlerStack()->setHandler($this->handler);
    }

    /**
     * @return Handler
     */
    private function getHandler(): Handler
    {
        if(null === $this->handler) {
            return new CurlMultiHandler(new GuzzleCurlMultiHandler);
        }

        return $this->handler;
    }

    private function initializeHandlerStack(): void
    {
        $stack = new HandlerStack($this->getHandler());

        $this->handlerStack = $stack;
    }

    /**
     * @return HandlerStack
     */
    private function getHandlerStack(): HandlerStack
    {
        return $this->handlerStack;
    }

    private function initializeHttpClient(): void
    {
        $config = $this->getConfig()->requestOptions();

        $config['handler'] = $this->getHandlerStack();

        $config['on_stats'] = function (TransferStats $stats) {
            $this->getDispatcher()->dispatch(TransferStatisticReceived::class, new TransferStatisticReceived($stats));
        };

        $config['on_headers'] = function (ResponseInterface $response) {
            $this->getDispatcher()->dispatch(ResponseHeadersReceived::class, new ResponseHeadersReceived($response));
        };

        $this->httpClient = new GuzzleHttpClient($config);
    }

    /**
     * @return ClientInterface
     */
    private function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    private function initializeScheduler(): void
    {
        $this->scheduler = new Scheduler(
            $this->getHttpClient(),
            $this->getDispatcher(),
            $this->getHistory(),
            $this->getQueue(),
            $this->getMiddlewareStack(),
            $this->getConfig()->concurrency()
        );
    }

    /**
     * @return Scheduler
     */
    private function getScheduler(): Scheduler
    {
        return $this->scheduler;
    }

    private function initializeSession()
    {
        $this->session = new Session($this->getHttpClient());
    }

    /**
     * @return Session
     */
    private function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @param Extension $extension
     */
    public function addExtension(Extension $extension): void
    {
        $extension->initialize($this->getConfig(), $this->getSession());

        $this->getDispatcher()->addSubscriber($extension);

        $this->extentions[] = $extension;
    }

    private function initializeDefaultExtentions(): void
    {
        $this->addExtension(new AutoThrottle);

        $this->addExtension(new Storage(new StorageService($this->getStorageAdapter())));

        $this->addExtension(new RedirectScheduler($this->getQueue()));

        $this->addExtension(new ExtractAndQueueLinks(new LinkExtractor, new AggregateUriPolicy($this->getConfig()->filterOptions()), $this->getQueue()));
    }

    private function initializeEventDispatcher(): void
    {
        $this->dispatcher = new EventDispatcher;
    }

    /**
     * @return EventDispatcher
     */
    private function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * Adds a request middleware to the stack
     *
     * @param RequestMiddleware $middleware
     */
    public function addRequestMiddleware(RequestMiddleware $middleware): void
    {
        $this->getMiddlewareStack()->addRequestMiddleware($middleware);
    }

    /**
     * Adds a response middleware to the stack
     *
     * @param ResponseMiddleware $middleware
     */
    public function addResponseMiddleware(ResponseMiddleware $middleware): void
    {
        $this->getMiddlewareStack()->addResponseMiddleware($middleware);
    }

    private function initializeMiddlewareStack(): void
    {
        $this->middlewareStack = new MiddlewareStack;

        // Adding robots.txt middleware if enabled.
        $filterOptions = $this->getConfig()->filterOptions();
        if($filterOptions->obeyRobotsTxt()) {
            $this->addRequestMiddleware(new RobotsTxtMiddleware);
        }
    }

    /**
     * @return MiddlewareStack
     */
    private function getMiddlewareStack(): MiddlewareStack
    {
        return $this->middlewareStack;
    }

    public function run(): void
    {
        $config = $this->getConfig();

        $this->getDispatcher()->dispatch(BeforeEngineStarted::class, new BeforeEngineStarted);

        $queue = $this->getQueue();

        foreach ($config->startUris() as $uri) {
            $queue->enqueue(new Request('GET', $uri));
        }

        $this->getScheduler()->run();
    }
}

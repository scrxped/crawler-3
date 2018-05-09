<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Event\TransferStatisticReceived;
use Zstate\Crawler\Extension\AutoThrottle;
use Zstate\Crawler\Extension\Extension;
use Zstate\Crawler\Extension\ExtractAndQueueLinks;
use Zstate\Crawler\Extension\RedirectScheduler;
use Zstate\Crawler\Extension\Storage;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\MiddlewareWrapper;
use Zstate\Crawler\Policy\AggregateUriPolicy;
use Zstate\Crawler\Service\LinkExtractor;
use Zstate\Crawler\Service\StorageService;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;
use Zstate\Crawler\Storage\History;
use Zstate\Crawler\Storage\HistoryInterface;
use Zstate\Crawler\Storage\Queue;
use Zstate\Crawler\Storage\QueueInterface;

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
    private $storageAdapter;
    private $queue;
    /**
     * @var HandlerStack
     */
    private $handlerStack;
    private $dispatcher;
    private $history;
    private $handler;
    private $extentions = [];
    private $transferStats;
    private $session;

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
        $this->initializeScheduler();
    }

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

    public function setHandler(Handler $handler)
    {
        $this->handler = $handler;

        $this->getHandlerStack()->setHandler($this->handler);
    }

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

    private function promiseTransferStats(): PromiseInterface
    {
        return $this->transferStats;
    }

    private function initializeTransferStatsPromise()
    {
        $this->transferStats = new Promise;
    }

    private function initializeSession()
    {
        $this->session = new Session($this->getHttpClient());
    }

    private function getSession(): Session
    {
        return $this->session;
    }

    public function addExtension(Extension $extension)
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

        $this->addExtension(
            new ExtractAndQueueLinks(
                new LinkExtractor,
                new AggregateUriPolicy($this->getConfig()->filterOptions()),
                $this->getQueue()
            )
        );
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
     * Push a middleware to the top of the stack
     *
     * @param Middleware $middleware
     */
    public function addMiddleware(Middleware $middleware): void
    {
        $middlewareCallable = new MiddlewareWrapper($middleware);

        $this->getHandlerStack()->push($middlewareCallable, get_class($middleware));
    }

    private function setupDefaultMiddlewares(): void
    {
        $stack = $this->getHandlerStack();

        $stack->push(\GuzzleHttp\Middleware::prepareBody(), 'prepare_body');
        $stack->push(\GuzzleHttp\Middleware::cookies(), 'cookies');
        $stack->push(\GuzzleHttp\Middleware::redirect(), 'allow_redirects');
        $stack->push(\GuzzleHttp\Middleware::httpErrors(), 'http_errors');
    }

    public function run(): void
    {
        $this->setupDefaultMiddlewares();

        $config = $this->getConfig();

        $this->getDispatcher()->dispatch(
            BeforeEngineStarted::class,
            new BeforeEngineStarted
        );

        $queue = $this->getQueue();

        foreach ($config->startUris() as $uri) {
            $queue->enqueue(new Request('GET', $uri));
        }

        $this->getScheduler()->run();
    }
}

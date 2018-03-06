<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zstate\Crawler\Config\Config;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
use Zstate\Crawler\Policy\AggregateUriPolicy;
use Zstate\Crawler\Subscriber\Authenticator;
use Zstate\Crawler\Subscriber\ExtractAndQueueLinks;
use Zstate\Crawler\Subscriber\RedirectScheduler;
use Zstate\Crawler\Subscriber\Storage;
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\MiddlewareWrapper;
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

    public function __construct(array $config)
    {
        $this->setConfig($config);
        $this->setStorageAdapter();
        $this->setQueue();
        $this->setHistory();
        $this->setHandlerStack();
        $this->setHttpClient();
        $this->setEventDispatcher();
        $this->setScheduler();
    }

    private function setConfig(array $config)
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

    private function setStorageAdapter(): void
    {
        $config = $this->getConfig();
        $dsn = $config->saveProgressIn();

        $this->storageAdapter = SqliteAdapter::create($dsn);
    }

    private function getStorageAdapter(): SqliteAdapter
    {
        return $this->storageAdapter;
    }

    private function setQueue(): void
    {
        $this->queue = new Queue($this->getStorageAdapter());
    }

    private function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    private function setHistory(): void
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

    private function setHandlerStack(): void
    {
        $stack = HandlerStack::create($this->getHandler());

        $this->handlerStack = $stack;
    }

    private function getHandlerStack(): HandlerStack
    {
        return $this->handlerStack;
    }

    private function setHttpClient(): void
    {
        $config = $this->getConfig()->requestOptions();

        $config['handler'] = $this->getHandlerStack();

        $this->httpClient = new GuzzleHttpClient($config);
    }

    /**
     * @return ClientInterface
     */
    private function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    private function setScheduler(): void
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


    private function setEventDispatcher(): void
    {
        $this->dispatcher = new EventDispatcher;
        $config = $this->getConfig();


        if(null !== $config->loginOptions()) {
            $this->dispatcher->addSubscriber(
                new Authenticator(
                    $this->getHttpClient(),
                    $config->loginOptions()
                )
            );
        }

        $this->dispatcher->addSubscriber(
            new Storage(new StorageService($this->getStorageAdapter()))
        );

        $this->dispatcher->addSubscriber(
            new RedirectScheduler($this->getQueue())
        );

        $this->dispatcher->addSubscriber(
            new ExtractAndQueueLinks(
                new LinkExtractor,
                new AggregateUriPolicy($this->getConfig()->filterOptions()),
                $this->getQueue()
            )
        );
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

    public function withLog(Middleware $middleware): void
    {
        $this->getHandlerStack()->unshift(new MiddlewareWrapper($middleware), get_class($middleware));
    }

    public function run(): void
    {
        $config = $this->getConfig();

        $this->getDispatcher()->dispatch(
            BeforeEngineStarted::class,
            new BeforeEngineStarted($config)
        );

        $scheduler = $this->getScheduler();

        $scheduler->queue(new Request('GET', $config->startUri()));

        $scheduler->run();
    }
}

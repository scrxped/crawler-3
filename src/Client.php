<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Event\ResponseReceived;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
use Zstate\Crawler\Listener\Authenticator;
use Zstate\Crawler\Listener\RedirectScheduler;
use Zstate\Crawler\Listener\StorageCreator;
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\MiddlewareWrapper;
use Zstate\Crawler\Service\LinkExtractor;
use Zstate\Crawler\Service\StorageService;
use Zstate\Crawler\Storage\Adapter\SqliteAdapter;
use Zstate\Crawler\Storage\Adapter\SqliteDsn;
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
        $this->config = $config;
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return $this->config;
    }

    private function setStorageAdapter(): void
    {
        $config = $this->getConfig();
        $dsn = $config['save_progress_in'] ?? 'memory';

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

    private function setHandlerStack(): void
    {
        $config = $this->getConfig();

        $handler = $config['handler'] ?? new CurlMultiHandler(new GuzzleCurlMultiHandler);
        $stack = HandlerStack::create($handler);

        $this->handlerStack = $stack;
    }

    private function getHandlerStack(): HandlerStack
    {
        return $this->handlerStack;
    }

    private function setHttpClient(): void
    {
        $config = $this->getConfig();

        $config['handler'] = $this->getHandlerStack();

        $config = $this->configureDefaults($config);

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
        $config = $this->getConfig();

        $linkExtractor = LinkExtractor::fromConfig($config);

        $this->scheduler = new Scheduler(
            $this->getHttpClient(),
            $this->getHandlerStack(),
            $this->getDispatcher(),
            $this->getHistory(),
            $this->getQueue(),
            $linkExtractor,
            $config['concurrency'] ?? 10
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

        $this->dispatcher->addListener(
            BeforeEngineStarted::class,
            [new Authenticator($this->getHttpClient()), 'beforeEngineStarted']
        );

        $this->dispatcher->addListener(
            BeforeEngineStarted::class,
            [new StorageCreator(new StorageService($this->getStorageAdapter())), 'beforeEngineStarted']
        );

        $this->dispatcher->addListener(
            ResponseReceived::class,
            [new RedirectScheduler($this->getQueue()), 'responseReceived']
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
        $this->config['auth'] = $authOptions;

        return $this;
    }

    public function withLog(Middleware $middleware): void
    {
        $this->getHandlerStack()->unshift(new MiddlewareWrapper($middleware), get_class($middleware));
    }

    public function run(): void
    {
        $config = $this->getConfig();

        if (! isset($config['start_url'])) {
            throw new \RuntimeException('Please specify the start URI.');
        }

        $this->getDispatcher()->dispatch(BeforeEngineStarted::class, new BeforeEngineStarted($config));

        $scheduler = $this->getScheduler();

        $scheduler->queue(new Request('GET', $config['start_url']));

        $scheduler->run();
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
            'cookies' => true,
            'allow_redirects' => false
        ];

        $configuration = array_merge($configuration, $config);

        return $configuration;
    }
}

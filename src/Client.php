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
use Zstate\Crawler\Middleware\Middleware;
use Zstate\Crawler\Middleware\MiddlewareWrapper;
use Zstate\Crawler\Service\LinkExtractor;

class Client
{
    /**
     * @var HandlerStack
     */
    private $stack;

    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var array
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    private function __construct(Scheduler $scheduler, ClientInterface $client, HandlerStack $handlerStack, EventDispatcherInterface $eventDispatcher, array $config)
    {
        $this->stack = $handlerStack;
        $this->scheduler = $scheduler;
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpClient = $client;
    }

    public static function create(array $config): self
    {

        $queue = self::getQueue($config);

        $stack = self::createHandlerStack($config['handler'] ?? new CurlMultiHandler(new GuzzleCurlMultiHandler));

        $httpClient = self::createHttpClient($config, $stack);

        $dispatcher = self::createEventDispatcher($httpClient, $config, $queue);

        $scheduler = self::createScheduler($config, $httpClient, $stack, $dispatcher, $queue);

        $crawler = new self($scheduler, $httpClient, $stack, $dispatcher, $config);

        return $crawler;
    }

    private static function getQueue(array $config): Queue
    {
        return $config['queue'] ?? new InMemoryQueue;
    }

    private static function getHistory(array $config): History
    {
        return $config['history'] ?? new InMemoryHistory;
    }

    /**
     * @param Handler $handler
     * @return HandlerStack
     */
    private static function createHandlerStack(Handler $handler): HandlerStack
    {
        $stack = HandlerStack::create($handler);

        return $stack;
    }

    /**
     * @param array $config
     * @param $stack
     * @return ClientInterface
     */
    private static function createHttpClient(array $config, HandlerStack $stack): ClientInterface
    {
        $config['handler'] = $stack;

        $config = self::configureDefaults($config);

        $httpClient = new GuzzleHttpClient($config);

        return $httpClient;
    }

    /**
     * @param array $config
     * @param $httpClient
     * @return Scheduler
     */
    private static function createScheduler(array $config, ClientInterface $httpClient, HandlerStack $handlerStack, EventDispatcherInterface $eventDispatcher, Queue $queue): Scheduler
    {
        $linkExtractor = LinkExtractor::fromConfig($config);

        $scheduler = new Scheduler(
            $httpClient,
            $handlerStack,
            $eventDispatcher,
            self::getHistory($config),
            $queue,
            $linkExtractor,
            $config['concurrency'] ?? 10
        );

        return $scheduler;
    }

    private static function createEventDispatcher(ClientInterface $httpClient, array $config, Queue $queue): EventDispatcherInterface
    {
        $dispatcher = new EventDispatcher;

        $dispatcher->addListener(BeforeEngineStarted::class, [new Authenticator($httpClient), 'beforeEngineStarted']);
        $dispatcher->addListener(ResponseReceived::class, [new RedirectScheduler($queue), 'responseReceived']);

        return $dispatcher;
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
        $this->config['auth'] = $authOptions;

        return $this;
    }

    public function withLog(Middleware $middleware): void
    {
        $this->stack->unshift(new MiddlewareWrapper($middleware), get_class($middleware));
    }

    public function run(): void
    {
        if (! isset($this->config['start_url'])) {
            throw new \RuntimeException('Please specify the start URI.');
        }

        $this->eventDispatcher->dispatch(BeforeEngineStarted::class, new BeforeEngineStarted($this->config));

        $this->scheduler->queue(new Request('GET', $this->config['start_url']));

        $this->scheduler->run();
    }

    /**
     * @param array $config
     * @return array
     */
    private static function configureDefaults(array $config): array
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

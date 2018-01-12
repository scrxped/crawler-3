<?php
namespace Zstate\Crawler;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Zstate\Crawler\Handler\CurlMultiHandler;
use Zstate\Crawler\Handler\Handler;
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

    public function __construct(Scheduler $scheduler, HandlerStack $handlerStack)
    {
        $this->stack = $handlerStack;
        $this->scheduler = $scheduler;
    }

    public static function create(array $config): self
    {
        $stack = self::createHandlerStack($config['handler'] ?? new CurlMultiHandler(new GuzzleCurlMultiHandler));

        $httpClient = self::createHttpClient($config, $stack);

        $scheduler = self::createScheduler($config, $httpClient);

        $crawler = new self($scheduler, $stack);

        return $crawler;
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
    private static function createScheduler(array $config, $httpClient): Scheduler
    {
        $linkExtractor = LinkExtractor::fromConfig($config);

        $history = $config['history'] ?? new InMemoryHistory;

        $queue = $config['queue'] ?? new InMemoryQueue;

        $scheduler = new Scheduler(
            $httpClient,
            $history,
            $queue,
            $linkExtractor,
            $config['concurrency'] ?? 1
        );

        if (! isset($config['start_url'])) {
            throw new \RuntimeException('Please specify the start URI.');
        }

        $scheduler->queue(new Request('GET', $config['start_url']));

        return $scheduler;
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
        $body = http_build_query($authOptions['form_params'], '', '&');
        $request = new Request(
            'POST',
            $authOptions['loginUri'],
            ['content-type' => 'application/x-www-form-urlencoded'],
            $body
        );

        $this->scheduler->queue($request);

        return $this;
    }

    public function withLog(Middleware $middleware): void
    {
        $this->stack->unshift(new MiddlewareWrapper($middleware), 'logger');
    }

    public function run(): void
    {
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
            'cookies' => true
        ];

        $configuration = array_merge($configuration, $config);

        return $configuration;
    }
}

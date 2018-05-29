<?php
declare(strict_types=1);


namespace Zstate\Crawler\Handler;


use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class HandlerStack
{
    /**
     * @var \GuzzleHttp\HandlerStack
     */
    private $handlerStack;

    public function __construct(Handler $handler)
    {
        $handlerStack = new \GuzzleHttp\HandlerStack($handler);

        // Initializing GuzzleHttp core middlewares
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
        $handlerStack->push(Middleware::cookies(), 'cookies');
        $handlerStack->push(Middleware::redirect(), 'allow_redirects');

        $this->handlerStack = $handlerStack;
    }

    public function setHandler(Handler $handler)
    {
        $this->handlerStack->setHandler($handler);
    }

    /**
     * Invokes the handler stack as a composed handler
     *
     * @param RequestInterface $request
     * @param array            $options
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $handler = $this->handlerStack;

        return $handler($request, $options);
    }

    public function push(callable $middleware)
    {
        $this->handlerStack->push($middleware);
    }

    public function __toString(): string
    {
        return $this->handlerStack->__toString();
    }
}
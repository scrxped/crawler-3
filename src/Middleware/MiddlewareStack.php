<?php
declare(strict_types=1);


namespace Zstate\Crawler\Middleware;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareStack
{
    private $requestMiddleware = [];

    private $responseMiddleware = [];

    private $cachedRequestMiddlewareStack;

    private $cachedResponseMiddlewareStack;

    public function addRequestMiddleware(RequestMiddleware $requestMiddleware): void
    {
        $this->requestMiddleware[] = $requestMiddleware;
    }

    public function addResponseMiddleware(ResponseMiddleware $responseMiddleware): void
    {
        $this->responseMiddleware[] = $responseMiddleware;
    }

    public function getRequestMiddlewareStack(): callable
    {
        if (! $this->cachedRequestMiddlewareStack) {

            $prev = function (RequestInterface $request): RequestInterface {
                return $request;
            };

            /** @var RequestMiddleware $middleware */
            foreach (array_reverse($this->requestMiddleware) as $middleware) {
                $prev = function (RequestInterface $request) use ($middleware, $prev): RequestInterface {
                    return $prev($middleware->processRequest($request));
                };
            }

            $this->cachedRequestMiddlewareStack = $prev;
        }

        return $this->cachedRequestMiddlewareStack;
    }

    public function getResponseMiddlewareStack(): callable
    {
        if (! $this->cachedResponseMiddlewareStack) {

            $prev = function (ResponseInterface $response, RequestInterface $request): ResponseInterface {
                return $response;
            };

            /** @var ResponseMiddleware $middleware */
            foreach (array_reverse($this->responseMiddleware) as $middleware) {
                $prev = function (ResponseInterface $response, RequestInterface $request) use ($middleware, $prev): ResponseInterface {
                    return $prev($middleware->processResponse($response, $request), $request);
                };
            }

            $this->cachedResponseMiddlewareStack = $prev;
        }

        return $this->cachedResponseMiddlewareStack;
    }
}
<?php

namespace Zstate\Crawler\Middleware;

use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Exception\InvalidTypeException;

class MiddlewareWrapper
{
    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * MiddlewareWrapper constructor.
     * @param Middleware $middleware
     */
    public function __construct(Middleware $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @param callable $delegate
     * @return \Closure
     */
    public function __invoke(callable $delegate)
    {
        return function (RequestInterface $request, array $options) use ($delegate) {
            try {
                $request = $this->middleware->processRequest($request, $options);

                $this->guardRequest($request);

                /** @var Promise $promise */
                $promise = $delegate($request, $options);
            } catch (\Exception $e) {
                return \GuzzleHttp\Promise\rejection_for($e);
            }

            return $promise->then(
                function ($response) use ($request) {

                    /** @var ResponseInterface $response */
                    $response = $this->middleware->processResponse($request, $response);

                    $this->guardResponse($response);

                    return $response;
                },
                function ($e) use ($request) {
                    //Just like try/catch, you can choose to propagate or not by returning an Exception or throwing an Exception.
                    $reason = $this->middleware->processFailure($request, $e);

                    $this->guardReason($reason);

                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            );
        };
    }

    /**
     * @param RequestInterface $request
     */
    private function guardRequest($request)
    {
        if (! $request instanceof RequestInterface) {
            throw new InvalidTypeException('The processRequest method must return RequestInterface.');
        }
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function guardResponse($response)
    {
        if (! $response instanceof ResponseInterface) {
            throw new InvalidTypeException('The processResponse method must return ResponseInterface.');
        }
    }

    /**
     * @param \Exception $reason
     * @return \Exception
     */
    private function guardReason($reason)
    {
        if (! $reason instanceof \Exception) {
            throw new InvalidTypeException('The processFailure method must return \Exception.');
        }
    }
}

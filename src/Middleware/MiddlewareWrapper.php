<?php
declare(strict_types=1);

namespace Zstate\Crawler\Middleware;

use Exception;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
    public function __invoke(callable $delegate): callable
    {
        return function (RequestInterface $request, array $options) use ($delegate): PromiseInterface {
            try {
                $request = $this->middleware->processRequest($request, $options);

                /** @var PromiseInterface $promise */
                $promise = $delegate($request, $options);
            } catch (Exception $e) {
                return \GuzzleHttp\Promise\rejection_for($e);
            }

            return $promise->then(
                function (ResponseInterface $response) use ($request): ResponseInterface {

                    $response = $this->middleware->processResponse($request, $response);

                    return $response;
                },
                function (Exception $e) use ($request): PromiseInterface {
                    //Just like try/catch, you can choose to propagate or not by returning an Exception or throwing an Exception.
                    $reason = $this->middleware->processFailure($request, $e);

                    return \GuzzleHttp\Promise\rejection_for($reason);
                }
            );
        };
    }
}

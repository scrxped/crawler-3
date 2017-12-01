<?php

namespace Zstate\Crawler\Middleware;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * @var array
     */
    private $authOptions;
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     * @param array $authOptions
     */
    public function __construct(ClientInterface $client, array $authOptions)
    {
        $this->authOptions = $authOptions;
        $this->client = $client;
    }

    /**
     * @inheritdoc
     */
    public function processRequest(RequestInterface $request, array $options)
    {
        $authOptions = $this->authOptions;
        $currentUri = (string) $request->getUri();

        if (strpos($currentUri, $authOptions['loginUri']) !== false && $request->getMethod() === 'GET') {
            $this->client->request('POST', $authOptions['loginUri'], [
                'form_params' => $authOptions['form_params']
            ]);
        }



        return $request;
    }
}

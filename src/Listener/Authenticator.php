<?php

namespace Zstate\Crawler\Listener;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Zstate\Crawler\Event\BeforeEngineStarted;

class Authenticator
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function beforeEngineStarted(BeforeEngineStarted $event): void
    {
        $config = $event->getConfig();

        if(empty($config['auth'])) {
            return;
        }

        /**
         * @param array $authOptions
         * [
         *   'loginUri' => 'http://site2.local/admin/login.php',
         *   'form_params' => ['username' => 'test', 'password' => 'password']
         * ]
         */
        $authOptions = $config['auth'];

        $body = http_build_query($authOptions['form_params'], '', '&');

        $request = new Request('POST', $authOptions['loginUri'], ['content-type' => 'application/x-www-form-urlencoded'], $body);

        $this->httpClient->send($request);
    }
}
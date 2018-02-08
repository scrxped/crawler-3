<?php

namespace Zstate\Crawler\Listener;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Event\ResponseReceived;
use function Zstate\Crawler\is_redirect;
use Zstate\Crawler\Service\AuthenticatorService;

class Authenticator implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $config;
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->config = $config;
        $this->client = $client;
    }

    public function authenticate(BeforeEngineStarted $event): void
    {
        $this->login($this->config);
    }

    public function reauthenticate(ResponseReceived $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if(! is_redirect($response)) {
            return;
        }

        $authUri = new Uri($this->config['auth']['loginUri']);

        $location = UriResolver::resolve(
            $request->getUri(),
            new Uri($response->getHeaderLine('Location'))
        );

        if(stripos((string)$location, (string)$authUri) !== false) {
            $this->login($this->config);
        }
    }

    private function login(array $config)
    {
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

        $this->client->send($request);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEngineStarted::class => 'authenticate',
            ResponseReceived::class => 'reauthenticate'
        ];
    }
}
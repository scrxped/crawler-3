<?php
declare(strict_types=1);

namespace Zstate\Crawler\Subscriber;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zstate\Crawler\Config\LoginOptions;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Event\ResponseReceived;
use function Zstate\Crawler\is_redirect;
use Zstate\Crawler\Service\AuthenticatorService;

class Authenticator implements EventSubscriberInterface
{
    /**
     * @var LoginOptions
     */
    private $config;
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client, LoginOptions $config)
    {
        $this->config = $config;
        $this->client = $client;
    }

    public function authenticate(BeforeEngineStarted $event): void
    {
        $this->login($this->config);
    }

    public function relogin(ResponseReceived $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if(! $this->config->relogin()) {
            return;
        }

        if(! is_redirect($response)) {
            return;
        }

        $authUri = new Uri($this->config->loginUri());

        $location = UriResolver::resolve(
            $request->getUri(),
            new Uri($response->getHeaderLine('Location'))
        );

        if(stripos((string)$location, (string)$authUri) !== false) {
            $this->login($this->config);
        }
    }

    private function login(LoginOptions $config)
    {
        /**
         * @param array $authOptions
         * [
         *   'loginUri' => 'http://site2.local/admin/login.php',
         *   'form_params' => ['username' => 'test', 'password' => 'password']
         * ]
         */

        $body = http_build_query($config->formParams(), '', '&');

        $request = new Request(
            'POST',
            $config->loginUri(),
            ['content-type' => 'application/x-www-form-urlencoded'],
            $body
        );

        $this->client->send($request);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEngineStarted::class => 'authenticate',
            ResponseReceived::class => 'relogin'
        ];
    }
}
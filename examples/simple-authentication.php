<?php

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zstate\Crawler\Client;
use Zstate\Crawler\Event\BeforeEngineStarted;
use Zstate\Crawler\Extension\Extension;
use Zstate\Crawler\Middleware\BaseMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

$config = [
    'start_uri' => ['http://localhost:8881/admin/'],
    'request_options' => [
        'debug' => false,
    ]
];


$client = new Client($config);

$loginUri = 'http://localhost:8881/admin/login.php';
$username = 'test';
$password = 'password';


$client->addExtension(new class($loginUri, $username, $password) extends Extension {

    private $loginUri;

    private $username;

    private $password;

    public function __construct(string $loginUri, string $username, string $password)
    {
        $this->loginUri = $loginUri;
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate(BeforeEngineStarted $event): void
    {
        $this->login($this->loginUri, $this->username, $this->password);
    }

    private function login(string $loginUri, string $username, string $password)
    {
        $formParams = ['username' => $username, 'password' => $password];

        $body = http_build_query($formParams, '', '&');

        $request = new Request(
            'POST',
            $loginUri,
            ['content-type' => 'application/x-www-form-urlencoded'],
            $body
        );

        $this->getHttpClient()->send($request);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEngineStarted::class => 'authenticate'
        ];
    }
});

$client->addMiddleware(new class extends BaseMiddleware {

    public function processRequest(RequestInterface $request, array $options): RequestInterface
    {
        printf("Process Request: %s; %s \n", $request->getUri(), $request->getBody());

        return $request;
    }

    public function processResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        printf("Process Response: %s %s \n", $request->getUri(), $response->getStatusCode());

        return $response;
    }
});

$client->run();
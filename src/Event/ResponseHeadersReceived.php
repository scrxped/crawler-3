<?php
declare(strict_types=1);


namespace Zstate\Crawler\Event;


use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\Event;

class ResponseHeadersReceived extends Event
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
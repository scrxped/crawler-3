<?php
declare(strict_types=1);


namespace Zstate\Crawler\Event;


use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\Event;

class AfterRequestSent extends Event
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
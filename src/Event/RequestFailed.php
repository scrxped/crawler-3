<?php
declare(strict_types=1);


namespace Zstate\Crawler\Event;


use Exception;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\Event;

class RequestFailed extends Event
{
    /**
     * @var Exception
     */
    private $reason;
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(Exception $reason, RequestInterface $request)
    {
        $this->reason = $reason;
        $this->request = $request;
    }

    /**
     * @return Exception
     */
    public function getReason(): Exception
    {
        return $this->reason;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
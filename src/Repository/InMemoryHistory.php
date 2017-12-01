<?php
namespace Zstate\Crawler\Repository;

use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;

class InMemoryHistory implements History
{
    private $history = [];

    /**
     * @param RequestInterface $request
     */
    public function add(RequestInterface $request)
    {
        $this->history[] = RequestFingerprint::calculate($request);
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function contains(RequestInterface $request)
    {
        return in_array(RequestFingerprint::calculate($request), $this->history);
    }
}

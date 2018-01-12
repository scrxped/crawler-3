<?php
namespace Zstate\Crawler;

use Psr\Http\Message\RequestInterface;
use Zstate\Crawler\Service\RequestFingerprint;

class InMemoryHistory implements History
{
    private $history = [];

    /**
     * @param RequestInterface $request
     */
    public function add(RequestInterface $request): void
    {
        $body = $request->getBody();

        $this->history[] = RequestFingerprint::calculate($request);

        $body->rewind();
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function contains(RequestInterface $request): bool
    {
        return in_array(RequestFingerprint::calculate($request), $this->history);
    }

    public function count(): int
    {
        return count($this->history);
    }
}

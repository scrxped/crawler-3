<?php
declare(strict_types=1);


namespace Zstate\Crawler;


use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class AbsoluteUri
{
    /**
     * @var Uri
     */
    private $uri;

    public function __construct(UriInterface $uri)
    {
        if(! Uri::isAbsolute($uri)) {
            throw new \InvalidArgumentException('URI must be absolute.');
        }

        $this->uri = $uri;
    }

    public static function fromString(string $uri): self
    {
        return new self(new Uri($uri));
    }

    public function getValue(): UriInterface
    {
        return $this->uri;
    }
}
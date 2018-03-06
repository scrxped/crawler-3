<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Psr\Http\Message\UriInterface;
use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;
use function Zstate\Crawler\is_uri_matched_pattern;

class AllowUri implements UriPolicy
{
    /**
     * @var FilterOptions
     */
    private $filterOptions;

    public function __construct(FilterOptions $filterOptions)
    {
        $this->filterOptions = $filterOptions;
    }

    public function isUriAllowed(AbsoluteUri $uri): bool
    {
        $allowedUriPatterns = $this->filterOptions->allow();

        if(empty($allowedUriPatterns)) {
            return true;
        }

        foreach ($allowedUriPatterns as $pattern) {
            if (is_uri_matched_pattern($uri->getValue(), $pattern)) {
                return true;
            }
        }

        return false;
    }
}
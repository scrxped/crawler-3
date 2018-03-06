<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;
use function Zstate\Crawler\is_uri_matched_pattern;

class DenyUri implements UriPolicy
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
        $deniedUriPatterns = $this->filterOptions->deny();

        foreach ($deniedUriPatterns as $pattern) {
            if (is_uri_matched_pattern($uri->getValue(), $pattern)) {
                return false;
            }
        }

        return true;
    }
}
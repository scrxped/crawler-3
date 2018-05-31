<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;

use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;

/**
 * @package Zstate\Crawler\Policy
 */
class AggregateUriPolicy implements UriPolicy
{
    /**
     * @var FilterOptions
     */
    private $filterOptions;

    /**
     * @var array
     */
    private $policies;

    /**
     * @param FilterOptions $filterOptions
     */
    public function __construct(FilterOptions $filterOptions)
    {
        $this->filterOptions = $filterOptions;
        $this->policies = [
            new DenyDomains($filterOptions),
            new AllowDomains($filterOptions),
            new DenyUri($filterOptions),
            new AllowUri($filterOptions)
        ];
    }

    /**
     * @param AbsoluteUri $uri
     * @return bool
     */
    public function isUriAllowed(AbsoluteUri $uri): bool
    {
        /** @var UriPolicy $policy */
        foreach ($this->policies as $policy) {
            if (! $policy->isUriAllowed($uri)) {
                return false;
            }
        }

        return true;
    }
}

<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;

class AggregateUriPolicy implements UriPolicy
{
    /**
     * @var FilterOptions
     */
    private $filterOptions;

    private $policies;

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

    public function isUriAllowed(AbsoluteUri $uri): bool
    {
        /** @var UriPolicy $policy */
        foreach ($this->policies as $policy) {
            if(! $policy->isUriAllowed($uri)) {
                return false;
            }
        }

        return true;
    }


}
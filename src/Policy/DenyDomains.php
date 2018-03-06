<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;

class DenyDomains implements UriPolicy
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
        $denyDomains = $this->filterOptions->denyDomains();

        if(in_array($uri->getValue()->getHost(), $denyDomains)) {
            return false;
        }

        return true;
    }
}
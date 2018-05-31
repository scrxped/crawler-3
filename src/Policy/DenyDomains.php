<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;

/**
 * @package Zstate\Crawler\Policy
 */
class DenyDomains implements UriPolicy
{
    /**
     * @var FilterOptions
     */
    private $filterOptions;

    /**
     * @param FilterOptions $filterOptions
     */
    public function __construct(FilterOptions $filterOptions)
    {
        $this->filterOptions = $filterOptions;
    }

    /**
     * @param AbsoluteUri $uri
     * @return bool
     */
    public function isUriAllowed(AbsoluteUri $uri): bool
    {
        $denyDomains = $this->filterOptions->denyDomains();

        if(in_array($uri->getValue()->getHost(), $denyDomains)) {
            return false;
        }

        return true;
    }
}
<?php
declare(strict_types=1);


namespace Zstate\Crawler\Policy;


use Zstate\Crawler\AbsoluteUri;
use Zstate\Crawler\Config\FilterOptions;

class AllowDomains implements UriPolicy
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
        $allowedDomains = $this->filterOptions->allowDomains();

        if(! empty($allowedDomains)) {
            return in_array($uri->getValue()->getHost(), $allowedDomains);
        }

        return true;
    }
}
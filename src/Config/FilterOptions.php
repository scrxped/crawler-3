<?php
declare(strict_types=1);

namespace Zstate\Crawler\Config;


/**
 * Class FilterOptions
 * @package Zstate\Crawler\Config
 */
class FilterOptions
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function allow(): array
    {
        return $this->get('allow');
    }

    public function allowDomains(): array
    {
        return $this->get('allow_domains');
    }

    public function denyDomains(): array
    {
        return $this->get('deny_domains');
    }

    public function deny(): array
    {
        return $this->get('deny');
    }

    private function get(string $name): array
    {
        return $this->options[$name] ?? [];
    }
}
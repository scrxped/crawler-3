<?php
declare(strict_types=1);

namespace Zstate\Crawler\Config;


use Symfony\Component\Config\Definition\Processor;

class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * Config constructor.
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function fromArray(array $config)
    {
        $config = ['crawler' => $config];
        $processor = new Processor();
        $configDefinition = new ConfigDefinition();
        $config = $processor->processConfiguration($configDefinition, $config);

        return new self($config);

    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    public function loginOptions(): ? LoginOptions
    {
        return isset($this->config['login']) ? new LoginOptions($this->config['login']) : null;
    }

    public function filterOptions(): ? FilterOptions
    {
        return isset($this->config['filter']) ? new FilterOptions($this->config['filter']) : null;
    }

    public function requestOptions(): array
    {
        return $this->config['request_options'];
    }

    public function startUri(): string
    {
        return $this->config['start_uri'];
    }

    public function concurrency(): int
    {
        return $this->config['concurrency'];
    }

    public function saveProgressIn(): string
    {
        return $this->config['save_progress_in'];
    }

    public function toArray()
    {
        return $this->config;
    }
}
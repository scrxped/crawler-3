<?php
declare(strict_types=1);

namespace Zstate\Crawler\Config;


/**
 * Class LoginOptions
 * @package Zstate\Crawler\Config
 * @internal
 */
class LoginOptions
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function loginUri(): string
    {
        return $this->options['login_uri'];
    }

    public function formParams(): array
    {
        return $this->options['form_params'];
    }

    public function relogin(): bool
    {
        return $this->options['relogin'];
    }
}
<?php

namespace Zstate\Crawler\Tests\Config;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Zstate\Crawler\Config\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{

    public function testDefaults()
    {
        $config = Config::fromArray([
            'start_uri' => 'http://test.com',
        ]);

        $expected = [
            'start_uri' => 'http://test.com',
            'concurrency' => 10,
            'save_progress_in' => 'memory',
            'request_options' => [
                'verify' => true,
                'cookies' => true,
                'allow_redirects' => false,
                'debug' => false,
                'connect_timeout' => 0,
                'timeout' => 0,
                'delay' => null
            ],

        ];

        $this->assertEquals($expected,$config->toArray());
    }

    public function testLoginConfigException()
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = Config::fromArray([
            'start_uri' => 'http://test.com',
            'login' => []
        ]);
    }

    public function testLoginConfigFormParamsException()
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = Config::fromArray([
            'start_uri' => 'http://test.com',
            'login' => [
                'login_uri' => 'http://test.com'
            ]
        ]);
    }

    public function testLoginConfig()
    {
        $config = Config::fromArray([
            'start_uri' => 'http://test.com',
            'login' => [
                'login_uri' => 'http://test.com',
                'form_params' => ['username' => 'test', 'password' => 'password']
            ]
        ]);

        $expected = [
            'start_uri' => 'http://test.com',
            'login' =>  [
                'login_uri' => 'http://test.com',
                'form_params' => [
                    'username' => 'test',
                    'password' => 'password',
                ],
                'relogin' => false,
            ],
            'request_options' => [
                'verify' => true,
                'cookies' => true,
                'allow_redirects' => false,
                'debug' => false,
                'connect_timeout' => 0,
                'timeout' => 0,
                'delay' => null

            ],
            'concurrency' => 10,
            'save_progress_in' => 'memory',
        ];


        $this->assertEquals($expected,$config->toArray());
    }

    public function testFullConfig()
    {
        $config = Config::fromArray([
            'start_uri' => 'http://test.com',
            'login' => [
                'login_uri' => 'http://test.com',
                'form_params' => ['username' => 'test', 'password' => 'password']
            ],
            'filter' => [
                'allow' => ['test','test1'],
                'allow_domains' => ['test.com','test1.com'],
                'deny_domains' => ['test2.com','test3.com'],
                'deny' => ['test2','test3'],
            ],
            'request_options' => [
                'verify' => false,
                'cookies' => true,
                'allow_redirects' => false,
                'debug' => true,
                'connect_timeout' => 0,
                'timeout' => 0,
                'delay' => 0
            ]
        ]);

        $expected = [
            'start_uri' => 'http://test.com',
            'login' => [
                'login_uri' => 'http://test.com',
                'form_params' => [
                    'username' => 'test',
                    'password' => 'password',
                ],
                'relogin' => false,
            ],
            'filter' => [
                'allow' => ['test','test1'],
                'allow_domains' => ['test.com','test1.com'],
                'deny_domains' => ['test2.com','test3.com'],
                'deny' => ['test2','test3'],
            ],
            'request_options' => [
                'verify' => false,
                'cookies' => true,
                'allow_redirects' => false,
                'debug' => true,
                'connect_timeout' => 0,
                'timeout' => 0,
                'delay' => 0
            ],
            'concurrency' => 10,
            'save_progress_in' => 'memory',
        ];
        $this->assertEquals($expected,$config->toArray());
    }


}
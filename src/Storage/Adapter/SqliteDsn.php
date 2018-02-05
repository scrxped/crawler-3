<?php

namespace Zstate\Crawler\Storage\Adapter;


class SqliteDsn
{
    /**
     * @var string
     */
    private $dsn;

    public function __construct(string $dsn = null)
    {
        $this->dsn = $dsn;
        if(null === $this->dsn) {
            $this->dsn = 'sqlite::memory:';
        }

        $this->guardDsn($this->dsn);
    }

    private function guardDsn(string $dsn): void
    {
        if(false === strpos($dsn, 'sqlite:')) {
            throw new \RuntimeException('The DSN must be valid SQLite DSN.');
        }
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->dsn;
    }
}
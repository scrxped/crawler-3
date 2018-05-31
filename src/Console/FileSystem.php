<?php
declare(strict_types=1);


namespace Zstate\Crawler\Console;


use RuntimeException;

/**
 * Simple wrapper for global file read write functions for testing purposes
 *
 * Class FileSystem
 * @package Zstate\Crawler\Console
 * @internal
 */
class FileSystem
{
    public function fileGetContent(string $path): string
    {
        if (false === $content = @file_get_contents($path)) {
            throw new RuntimeException(sprintf('Failed to read file "%s".', $path));
        }

        return $content;
    }

    public function filePutContents(string $path, string $content): void
    {
        if (false === @file_put_contents($path, $content)) {
            throw new RuntimeException(sprintf('Failed to write file "%s".', $path));
        }
    }
}
<?php
declare(strict_types=1);


namespace Zstate\Crawler\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zstate\Crawler\Console\FileSystem;

/**
 * @package Zstate\Crawler\Console\Command
 */
class InitCommand extends Command
{
    /**
     * @var FileSystem
     */
    private $filesystem;

    /**
     * InitCommand constructor.
     * @param null|FileSystem $filesystem
     */
    public function __construct(? FileSystem $filesystem = null)
    {
        parent::__construct();

        if (! $filesystem) {
            $filesystem = new FileSystem;
        }

        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Creates the default configuration file.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $path = './crawler.yml';

        $this->filesystem->filePutContents($path, $this->getYmlTemplate());

        $path = realpath($path);

        $output->writeln("<info>Created config file: " . $path . "</info>");
    }

    /**
     * @return string
     */
    public function getYmlTemplate(): string
    {
        $yml = <<<YML
start_uri:
 - http://test.com
 - http://mytest.com
concurrency: 10
save_progress_in: memory
request_options: 
  verify: true
  cookies: true
  allow_redirects: false
  debug: false

filter:
  robotstxt_obey: false
  allow:
    - testpage.html
    - testfolder
  allow_domains:
    - mydomain.com
    - yourdomain.com
  deny_domains:
    - otherdomain.com
  deny:
    - thatpage.html

auto_throttle:
  enabled: true
  min_delay: 0
  max_delay: 60
YML;

        return $yml;
    }
}

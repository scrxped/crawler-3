<?php
declare(strict_types=1);


namespace Zstate\Crawler\Console\Command;


use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Creates the default configuration file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = realpath('./crawler.yml');

        if(! is_writable(dirname($path))) {
            throw new RuntimeException('The current directory must be writable.');
        }

        file_put_contents($path, $this->getYmlTemplate());

        $output->writeln("<info>Created config file: " . $path . "</info>");
    }

    private function getYmlTemplate(): string
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
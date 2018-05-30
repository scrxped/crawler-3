<?php
declare(strict_types=1);


namespace Zstate\Crawler\Console\Command;


use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Zstate\Crawler\Client;
use Zstate\Crawler\Extension\ConsoleLogging;

class CrawlCommand extends Command
{
    protected function configure()
    {
        $this->setName('start')
            ->setDescription('Starts the crawler using provided configuration file.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED,
                'The path to the configuration file.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configPath = $input->getOption('config') ?? "./crawler.yml";

        $client = $this->getClient($configPath);

        $verbosityLevelMap = [
            LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::CRITICAL => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
        ];
        $client->addExtension(new ConsoleLogging(new ConsoleLogger($output, $verbosityLevelMap)));

        $client->run();
    }

    private function getConfigFromFile(string $path): array
    {
        $configPath = realpath($path);

        if(! is_readable($configPath)) {
            throw new RuntimeException("The config file doesn't exist or is not readable");
        }

        return Yaml::parse(file_get_contents($configPath));
    }

    private function getClient(string $configPath): Client
    {
        $config = $this->getConfigFromFile($configPath);

        return new Client($config);
    }
}
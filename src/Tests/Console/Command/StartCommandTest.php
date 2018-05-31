<?php

namespace Zstate\Crawler\Tests\Console\Command;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zstate\Crawler\Console\Command\StartCommand;

class StartCommandTest extends TestCase
{
    public function testStartCommand()
    {
        $application = new Application;

        $application->add(new StartCommand);

        $command = $application->find('start');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => __DIR__ . "/crawler.yml",
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertContains("[info] GET http://site1.local/page-with-link-to-500-error.html 200", $output);
        $this->assertContains("[info] GET http://site1.local 200", $output);
        $this->assertContains("[error] GET http://site1.local/500-error.php 500", $output);
        $this->assertContains("[info] GET http://site1.local/customers.html 200", $output);
        $this->assertContains("[info] GET http://site2.local 200", $output);
        $this->assertContains("[info] GET http://site2.local/service.html 200", $output);
        $this->assertContains("[info] GET http://site2.local/contacts.html 200", $output);
    }

}

<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Tests\Trait\GetService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;

use const PHP_EOL;

abstract class CommandTestCase extends KernelTestCase
{
    use Factories;
    use GetService;

    protected static function createCommandTester(string $commandName): CommandTester
    {
        if (!self::$booted) {
            self::bootKernel();
        }

        $application = new Application(self::$kernel);
        $command = $application->find($commandName);

        return new CommandTester($command);
    }

    /**
     * @return string[]
     */
    protected static function getCommandDisplayAsArray(CommandTester $tester): array
    {
        $display = [];
        foreach (explode(PHP_EOL, $tester->getDisplay()) as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $display[] = $line;
            }
        }

        return $display;
    }

    /**
     * @return string[]
     */
    protected static function questionOutputStrings(string $question): array
    {
        return ["$question:", '>'];
    }

    protected static function successOutputString(string $message): string
    {
        return "[OK] $message";
    }

    protected static function errorOutputString(string $message): string
    {
        return "[ERROR] $message";
    }
}

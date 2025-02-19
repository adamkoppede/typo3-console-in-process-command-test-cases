<?php

declare(strict_types=1);

namespace Example\Typo3ConsoleInProcessCommandTestCases;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Throwable;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @psalm-suppress UnusedClass - Registered in Configuration/Services.yaml
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class TestCommand extends Command
{
    private function testDatabaseImportWithInheritedOutput(OutputInterface $output): void
    {
        $sql = 'SELECT username from be_users where username="_cli_";';

        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $command = $commandRegistry->get('database:import');

        $input = new ArrayInput(['--connection' => 'Default']);
        $inputTextStream = fopen('php://temp', 'wb+');
        assert($inputTextStream !== false);
        $inputTextWritePos = 0;
        while ($inputTextWritePos < strlen($sql)) {
            $bytesWritten = fwrite($inputTextStream, substr($sql, $inputTextWritePos));
            assert($bytesWritten !== false);
            $inputTextWritePos += $bytesWritten;
        }
        assert(rewind($inputTextStream));
        $input->setStream($inputTextStream);

        $exitCode = $command->run($input, $output);
        assert($exitCode === Command::SUCCESS);

        assert(fclose($inputTextStream));
    }

    /**
     * @return Command::SUCCESS|Command::FAILURE|Command::INVALID
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $failed = false;
        $stderr = $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;

        try {
            $this->testDatabaseImportWithInheritedOutput($output);
        } catch (Throwable $exception) {
            $stderr->writeln("Exception in testDatabaseImportWithInheritedOutput: {$exception}");
            $failed = true;
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}

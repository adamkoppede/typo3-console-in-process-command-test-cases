<?php

declare(strict_types=1);

namespace Example\Typo3ConsoleInProcessCommandTestCases;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
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

    private function testDatabaseImportWithRedirectedOutput(): void
    {
        $sql = 'SELECT username from be_users where username="_cli_";';

        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $command = $commandRegistry->get('database:import');

        $input = new ArrayInput(['--connection' => 'Default']);
        $inputStream = fopen('php://temp', 'wb+');
        assert($inputStream !== false);
        $inputTextWritePos = 0;
        while ($inputTextWritePos < strlen($sql)) {
            $bytesWritten = fwrite($inputStream, substr($sql, $inputTextWritePos));
            assert($bytesWritten !== false);
            $inputTextWritePos += $bytesWritten;
        }
        assert(rewind($inputStream));
        $input->setStream($inputStream);

        $outputStream = fopen('php://temp', 'wb+');
        assert($outputStream !== false);

        $exitCode = $command->run($input, new StreamOutput($outputStream));
        assert($exitCode === Command::SUCCESS);
        $output = stream_get_contents($outputStream, null, 0);
        assert($output !== false);
        assert('_cli_' === trim($output));

        assert(fclose($inputStream));
        assert(fclose($outputStream));
    }

    private function testSymfonyStyleInjectionInDatabaseImportWithInheritedOutput(OutputInterface $output): void
    {
        $sql = 'SELECT "<error>I shouldn\'t be styled because I\'m user data</error>";';

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

    private function testSymfonyStyleInjectionInDatabaseImportWithRedirectedOutput(): void
    {
        $sql = 'SELECT "<error>I shouldn\'t be styled because I\'m user data</error>";';

        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $command = $commandRegistry->get('database:import');

        $input = new ArrayInput(['--connection' => 'Default']);
        $inputStream = fopen('php://temp', 'wb+');
        assert($inputStream !== false);
        $inputTextWritePos = 0;
        while ($inputTextWritePos < strlen($sql)) {
            $bytesWritten = fwrite($inputStream, substr($sql, $inputTextWritePos));
            assert($bytesWritten !== false);
            $inputTextWritePos += $bytesWritten;
        }
        assert(rewind($inputStream));
        $input->setStream($inputStream);

        $outputStream = fopen('php://temp', 'wb+');
        assert($outputStream !== false);

        $exitCode = $command->run($input, new StreamOutput($outputStream));
        assert($exitCode === Command::SUCCESS);
        $output = stream_get_contents($outputStream, null, 0);
        assert($output !== false);
        assert('<error>I shouldn\'t be styled because I\'m user data</error>' === trim($output));

        assert(fclose($inputStream));
        assert(fclose($outputStream));
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

        try {
            $this->testDatabaseImportWithRedirectedOutput();
        } catch (Throwable $exception) {
            $stderr->writeln("Exception in testDatabaseImportWithRedirectedOutput: {$exception}");
            $failed = true;
        }

        try {
            $this->testSymfonyStyleInjectionInDatabaseImportWithInheritedOutput($output);
        } catch (Throwable $exception) {
            $stderr->writeln("Exception in testSymfonyStyleInjectionInDatabaseImportWithInheritedOutput: {$exception}");
            $failed = true;
        }

        try {
            $this->testSymfonyStyleInjectionInDatabaseImportWithRedirectedOutput();
        } catch (Throwable $exception) {
            $stderr->writeln("Exception in testSymfonyStyleInjectionInDatabaseImportWithRedirectedOutput: {$exception}");
            $failed = true;
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}

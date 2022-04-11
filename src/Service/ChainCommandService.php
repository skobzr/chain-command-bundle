<?php

namespace skobzr\ChainCommandBundle\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChainCommandService
{
    private string $masterCommandName;

    /**
     * @param iterable<string> $links
     * @param LoggerInterface $logger
     */
    public function __construct(private iterable $links, private LoggerInterface $logger)
    {
        $this->masterCommandName = $links[0] ?? '';
    }

    /**
     * Run all dependent commands
     * @param Command|null $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    final public function runDependentChainCommand(?Command $command, InputInterface $input, OutputInterface $output): void
    {
        $chainLinks = [];

        if (!$this->isCommandBelongChain($command)) {
            return;
        }

        if (!$this->isMasterCommand($command)) {
            return;
        }

        if ($command->getApplication() === null) {
            return;
        }

        $commands = $this->getChainLinks();
        array_shift($commands);

        foreach ($commands as $commandName) {
            $chainLinks[] = $command->getApplication()->find($commandName);
        }

        foreach ($chainLinks as $chainLink) {
            $this->logger->info('Executing ' . $chainLink->getName() . ' chain members:');

            $chainLink->run($input, $output);
        }

        $this->logger->info('Execution of ' . $this->masterCommandName . ' chain completed.');
    }

    /**
     * Logs the start of a chain of commands
     * @param Command|null $command
     * @throws Exception
     */
    final public function checkAndlogRunChain(?Command $command): void
    {
        if ($this->isCommandBelongChain($command)) {
            $this->checkRunFirstDependentCommand($command);

            $this->addStartChainMessageToLog();
        }
    }

    /**
     * @return array<string>
     */
    private function getChainLinks(): array
    {
        return (array)$this->links;
    }

    /**
     * Checking that the dependent command is not run first
     * @param Command $command
     * @throws Exception
     */
    private function checkRunFirstDependentCommand(Command $command): void
    {
        if (!$this->isMasterCommand($command)) {
            throw new \Exception(
                sprintf(
                    'Error: %s command is a member of %s command chain and cannot be executed on its own.',
                    $command->getName(),
                    $this->masterCommandName
                )
            );
        }
    }

    /**
     * @param Command|null $command
     * @return bool
     */
    private function isCommandBelongChain(?Command $command): bool
    {
        return $this->getChainLinks() !== []
            && $command !== null
            && in_array($command->getName(), $this->getChainLinks(), true);
    }

    /**
     * @param Command|null $command
     * @return bool
     */
    private function isMasterCommand(?Command $command): bool
    {
        return $this->masterCommandName === $command->getName();
    }

    /**
     * Logging the beginning of the execution of a chain of commands
     */
    private function addStartChainMessageToLog(): void
    {
        $chainLinks = $this->getChainLinks();

        if ($chainLinks === []) {
            return;
        }

        $masterCommand = array_shift($chainLinks);

        $this->logger->info($masterCommand . ' is a master command of a command chain that has registered member commands');

        foreach ($chainLinks as $chainLink) {
            $this->logger->info($chainLink . ' registered as a member of ' . $masterCommand . ' command chain');
        }

        $this->logger->info('Executing ' . $masterCommand . ' command itself first:');
    }
}

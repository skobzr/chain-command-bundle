<?php

namespace skobzr\ChainCommandBundle\EventListener;

use skobzr\ChainCommandBundle\Service\ChainCommandService;
use Symfony\Component\Console\Event\ConsoleEvent;

class ChainCommandListener
{
    public function __construct(private ChainCommandService $chainService)
    {
    }

    /**
     * Listen the command event
     * @param \Symfony\Component\Console\Event\ConsoleEvent $event
     *
     * @throws \Exception
     */
    public function onConsoleCommand(ConsoleEvent $event): void
    {
        $this->chainService->checkAndlogRunChain($event->getCommand());
    }

    /**
     * After main command event
     * @param \Symfony\Component\Console\Event\ConsoleEvent $event
     * @throws \Exception
     */
    public function onConsoleTerminate(ConsoleEvent $event): void
    {
        $this->chainService->runDependentChainCommand($event->getCommand(), $event->getInput(), $event->getOutput());
    }
}

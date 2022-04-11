<?php

namespace skobzr\ChainCommandBundle\Tests\Unit;

use Exception;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use skobzr\ChainCommandBundle\Service\ChainCommandService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChainCommandServiceTest extends TestCase
{
    private array $logs = [];

    /**
     * @dataProvider dataProviderCheckAndlogRunChain
     * @param array $testCase
     */
    public function testCheckAndlogRunChain(array $testCase): void
    {
        $loggerMock = $this->getLoggerMock();

        $service = new ChainCommandService(
            $testCase['commandsInChain'],
            $loggerMock
        );

        $e = $testCase['exception'] ?? null;

        if ($e !== null) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($e);
        }

        $service->checkAndlogRunChain(new Command($testCase['runCommand']));

        if ($e === null) {
            $this->assertEquals($testCase['result'], $this->logs);
        }
    }

    public function dataProviderCheckAndlogRunChain(): array
    {
        $cases = [
            '0. Пустая цепочка' => [
                [
                    'commandsInChain' => [],
                    'runCommand' => 'foo:hello',
                    'result' => []
                ]
            ],
            '1. Команда главная в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'bar:hi',
                    'result' => [
                        "bar:hi is a master command of a command chain that has registered member commands",
                        "baz:yo registered as a member of bar:hi command chain",
                        "Executing bar:hi command itself first:"
                    ]
                ]
            ],
            '2. Команда не главная в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'baz:yo',
                    'exception' => 'Error: baz:yo command is a member of bar:hi command chain and cannot be executed on its own.'
                ]
            ],
            '3. Команды нет в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'bar:yo',
                    'result' => []
                ]
            ]
        ];

        return $cases;
    }

    /**
     * @dataProvider dataProviderRunDependentChainCommand
     * @param array $testCase
     */
    public function testRunDependentChainCommand(array $testCase): void
    {
        $loggerMock = $this->getLoggerMock();

        $service = new ChainCommandService($testCase['commandsInChain'], $loggerMock);

        $appMock = $this->getMockBuilder(Application::class)->disableOriginalConstructor()->getMock();

        $appMock->method('find')->willReturnCallback([$this, 'getCommandMock']);

        $commandMock = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->getMock();
        $commandMock->method('getName')->willReturn($testCase['runCommand']);
        $commandMock->method('getApplication')->willReturn($appMock);

        $service->runDependentChainCommand($commandMock, $this->createMock(InputInterface::class), $this->createMock(OutputInterface::class));

        $this->assertEquals($testCase['result'], $this->logs);
    }

    public function dataProviderRunDependentChainCommand(): array
    {
        $cases = [
            '0. Пустая цепочка' => [
                [
                    'commandsInChain' => [],
                    'runCommand' => 'foo:hello',
                    'result' => []
                ]
            ],
            '1. Команда главная в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'bar:hi',
                    'result' => [
                        'Executing baz:yo chain members:',
                        'Execution of bar:hi chain completed.'
                    ]
                ]
            ],
            '2. Команда не в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'bar:hihi',
                    'result' => []
                ]
            ],
            '3. Команда не главная в цепочке' => [
                [
                    'commandsInChain' => ['bar:hi', 'baz:yo'],
                    'runCommand' => 'baz:yo',
                    'result' => []
                ]
            ]
        ];

        return $cases;
    }

    public function getCommandMock(string $commandName, bool $isMaster = false): Command
    {
        $commandMock = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->getMock();

        $commandMock->method('getName')->willReturn($commandName);
        $commandMock->expects($isMaster ? $this->never() : $this->once())->method('run')->willReturn(1);

        return $commandMock;
    }

    public function getLoggerMock(): LoggerInterface
    {
        $this->logs = [];

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loggerMock
            ->method('info')
            ->willReturnCallback([$this, 'mockLog']);

        return $loggerMock;
    }

    public function mockLog(string $message): void
    {
        $this->logs[] = $message;
    }
}

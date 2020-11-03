<?php declare(strict_types=1);

namespace MeadSteve\MonoSnag\Tests;

use Bugsnag\Client as BugsnagClient;
use Bugsnag\Report as BugsnagReport;
use MeadSteve\MonoSnag\BugsnagHandler;
use Monolog\Logger;
use Monolog\Test\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\PhpUnit\ProphecyTestCase;

class BugsnagHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var BugsnagHandler
     */
    protected $testedHandler;

    /**
     * @var Logger
     */
    protected $monolog;

    protected $mockBugsnag;
    protected $mockReport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockBugsnag = $this->prophesize(BugsnagClient::class);
        $this->testedHandler = new BugsnagHandler($this->mockBugsnag->reveal());

        $this->monolog = new Logger('TestLogger');
        $this->monolog->pushHandler($this->testedHandler);
    }

    public function testHandlerDefaultsToErrorOnly(): void
    {
        $this->mockBugsnag->notifyException(Argument::any(), Argument::cetera())->shouldNotBeCalled();
        $this->mockBugsnag->notifyError(Argument::any(), Argument::cetera())->shouldNotBeCalled();
        $this->monolog->info('Hello World');
    }

    public function testNotifyIsCalledOnErrors(): void
    {
        $errorMessage = 'Oh no!';
        $this->mockBugsnag->notifyError($errorMessage, Argument::type('string'),
            Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->error($errorMessage);
    }

    public function testNotifyExceptionGetsCalledIfExceptionIsAvailable(): void
    {
        $sentException = new \Exception('Testing');
        $this->mockBugsnag->notifyException($sentException, Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->error('Oh no!', ['exception' => $sentException]);
    }

    /**
     * The three arguments below are provided by:
     * @dataProvider provideMappedSeverities
     *
     */
    public function testNotifyGetsPassedCorrectlyMappedSeverity($monologLevel, $expectedSeverity): void
    {
        // Update the tested handler to always send messages rather than just errors.
        $this->monolog->popHandler($this->testedHandler);
        $this->testedHandler = new BugsnagHandler($this->mockBugsnag->reveal(), Logger::DEBUG);
        $this->monolog->pushHandler($this->testedHandler);

        $errorMessage = 'Oh no!';
        $mockReport = $this->prophesize(BugsnagReport::class);
        $mockReport->setSeverity($expectedSeverity)->shouldBeCalledTimes(1);
        $mockReport->setMetaData(Argument::cetera())->shouldBeCalledTimes(1);
        $this->mockBugsnag->notifyError($errorMessage, Argument::type('string'), Argument::cetera())->will(
            function ($args) use ($mockReport) {
                if ($args[2]) {
                    $args[2]($mockReport->reveal());
                }
            }
        )->shouldBeCalledTimes(1);
        $this->monolog->log($monologLevel, $errorMessage);
    }

    public function provideMappedSeverities(): array
    {
        return [
            [Logger::DEBUG, 'info'],
            [Logger::INFO, 'info'],
            [Logger::NOTICE, 'info'],
            [Logger::WARNING, 'warning'],
            [Logger::ERROR, 'error'],
            [Logger::CRITICAL, 'error'],
            [Logger::ALERT, 'error'],
            [Logger::EMERGENCY, 'error']
        ];
    }
}

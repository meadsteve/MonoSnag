<?php

namespace MeadSteve\MonoSnag\Tests;

use MeadSteve\MonoSnag\BugsnagHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Promise\CallbackPromise;
use Prophecy\PhpUnit\ProphecyTestCase;

class BugsnagHandlerTest extends \PHPUnit_Framework_TestCase
{
      /**
     * @var BugsnagHandlerz
     */
    protected $testedHandler;

    /**
     * @var Logger
     */
    protected $monolog;

    protected $mockBugsnag;
    protected $mockReport;

    protected function setUp()
    {
        parent::setUp();
        $this->mockBugsnag = $this->prophesize('\Bugsnag\Client');
        $this->testedHandler = new BugsnagHandler($this->mockBugsnag->reveal());

        $this->monolog = new Logger("TestLogger");
        $this->monolog->pushHandler($this->testedHandler);
    }

    public function testHandlerDefaultsToErrorOnly()
    {
        $this->mockBugsnag->notifyException(Argument::any(), Argument::cetera())->shouldNotBeCalled();
        $this->mockBugsnag->notifyError(Argument::any(), Argument::cetera())->shouldNotBeCalled();
        $this->monolog->addInfo("Hello World");
    }

    public function testNotifyIsCalledOnErrors()
    {
        $errorMessage = "Oh no!";
        $this->mockBugsnag->notifyError($errorMessage, Argument::type('string'), Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->addError($errorMessage);
    }

    public function testNotifyExceptionGetsCalledIfExceptionIsAvailable()
    {
        $sentException = new \Exception("Testing");
        $this->mockBugsnag->notifyException($sentException, Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->addError("Oh no!", array("exception" => $sentException));
    }

    /**
     * The three arguments below are provided by:
     * @dataProvider provideMappedSeverities
     *
     */
    public function testNotifyGetsPassedCorrectlyMappedSeverity($monologLevel, $expectedSeverity)
    {
        // Update the tested handler to always send messages rather than just errors.
        $this->monolog->popHandler($this->testedHandler);
        $this->testedHandler = new BugsnagHandler($this->mockBugsnag->reveal(), Logger::DEBUG);
        $this->monolog->pushHandler($this->testedHandler);

        $errorMessage = "Oh no!";
        $mockReport = $this->prophesize('\Bugsnag\Report');
        $mockReport->setSeverity($expectedSeverity)->shouldBeCalledTimes(1);
        $mockReport->setMetaData(Argument::cetera())->shouldBeCalledTimes(1);
        $this->mockBugsnag->notifyError($errorMessage, Argument::type('string'), Argument::cetera())->will(function ($args) use ($mockReport) {
            if ($args[2]) {
                $args[2]($mockReport->reveal());
            }
        })->shouldBeCalledTimes(1);
        $this->monolog->log($monologLevel, $errorMessage);
    }

    public function provideMappedSeverities()
    {
        return array(
            array(Logger::DEBUG,     "info"),
            array(Logger::INFO,      "info"),
            array(Logger::NOTICE,    "info"),
            array(Logger::WARNING,   "warning"),
            array(Logger::ERROR,     "error"),
            array(Logger::CRITICAL,  "error"),
            array(Logger::ALERT,     "error"),
            array(Logger::EMERGENCY, "error")
        );
    }
}

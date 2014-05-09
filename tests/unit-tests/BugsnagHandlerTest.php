<?php

namespace MeadSteve\MonoSnag\Tests;

use MeadSteve\MonoSnag\BugsnagHandler;
use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTestCase;

class BugsnagHandlerTest extends ProphecyTestCase
{
      /**
     * @var BugsnagHandler
     */
    protected $testedHandler;

    /**
     * @var Logger
     */
    protected $monolog;

    protected $mockBugsnag;

    protected function setUp()
    {
        parent::setUp();
        $this->mockBugsnag = $this->prophesize('\Bugsnag_Client');
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
        $this->mockBugsnag->notifyError(Argument::any(), $errorMessage, Argument::cetera())->shouldBeCalledTimes(1);
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
        $this->mockBugsnag->notifyError($expectedSeverity, Argument::any(), Argument::any(), $expectedSeverity)->shouldBeCalledTimes(1);
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
            array(Logger::ALERT,     "fatal"),
            array(Logger::EMERGENCY, "fatal")
        );
    }
}

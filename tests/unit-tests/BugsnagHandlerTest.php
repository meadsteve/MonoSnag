<?php

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
        $this->mockBugsnag->notify(Argument::any(), Argument::cetera())->shouldNotBeCalled();
        $this->monolog->addInfo("Hello World");
    }

    public function testNotifyIsCalledOnErrors()
    {
        $errorMessage = "Oh no!";
        $this->mockBugsnag->notify($errorMessage, Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->addError($errorMessage);
    }

    public function testNotifyExceptionGetsCalledIfExceptionIsAvailable()
    {
        $sentException = new \Exception("Testing");
        $this->mockBugsnag->notifyException($sentException, Argument::cetera())->shouldBeCalledTimes(1);
        $this->monolog->addError("Oh no!", array("exception" => $sentException));
    }

}
 
<?php

namespace MeadSteve\MonoSnag;

use \Monolog\Handler\AbstractProcessingHandler;
use \Monolog\Logger;

class BugsnagHandler extends AbstractProcessingHandler
{
    /**
     * monolog error codes mapped on to bugSnag severities.
     * @var string[]
     */
    protected $severityMapping = array(
        Logger::DEBUG     => 'info',
        Logger::INFO      => 'info',
        Logger::NOTICE    => 'info',
        Logger::WARNING   => 'warning',
        Logger::ERROR     => 'error',
        Logger::CRITICAL  => 'error',
        Logger::ALERT     => 'fatal',
        Logger::EMERGENCY => 'fatal'
    );

    /**
     * @var \Bugsnag_Client
     */
    protected $client;

    function __construct(\Bugsnag_Client $client, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
    }


    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record) {
        $severity = $this->getSeverity($record['level']);
        if (isset($record['context']['exception'])) {
            $this->client->notifyException(
                $record['context']['exception'],
                $record,
                $severity
            );
        } else {
            $this->client->notify(
                (string) $record['message'],
                $record,
                $severity
            );
        }
    }

    /**
     * Returns the Bugsnag severiry from a monolog error code.
     * @param int $errorCode - one of the Logger:: constants.
     * @return string
     */
    protected function getSeverity($errorCode)
    {
        if (isset($this->severityMapping[$errorCode])) {
            return $this->severityMapping[$errorCode];
        }
        else {
            return $this->severityMapping[Logger::ERROR];
        }
    }
}

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
        Logger::ALERT     => 'error',
        Logger::EMERGENCY => 'error'
    );

    /**
     * @var \Bugsnag\Client
     */
    protected $client;

    public function __construct(\Bugsnag\Client $client, $level = Logger::ERROR, $bubble = true)
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
    protected function write(array $record)
    {
        $severity = $this->getSeverity($record['level']);
        if (isset($record['context']['exception'])) {
            $this->client->notifyException(
                $record['context']['exception'],
                function (\Bugsnag\Report $report) use ($record, $severity) {
                    $report->setSeverity($severity);
                    if (isset($record['extra'])) {
                        $report->setMetaData($record['extra']);
                    }
                }
            );
        } else {
            $this->client->notifyError(
                (string) $record['message'],
                (string) $record['formatted'],
                function (\Bugsnag\Report $report) use ($record, $severity) {
                    $report->setSeverity($severity);
                    if (isset($record['extra'])) {
                        $report->setMetaData($record['extra']);
                    }
                }
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
        } else {
            return $this->severityMapping[Logger::ERROR];
        }
    }
}

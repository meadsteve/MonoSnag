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

    public function __construct(\Bugsnag\Client $client, $level = Logger::ERROR, $bubble = true) {
        parent::__construct($level, $bubble);
        $this->client = $client;

        $client->registerCallback(function ($report) {
            $stacktrace = $report->getStacktrace();

            // Monolog uses MonoSnag for logs, and bugsnag handler logs directly
            $isAMonologHandledLog = $stacktrace->getFrames()[0]['method'] === 'MeadSteve\MonoSnag\BugsnagHandler::write';

            if (!$isAMonologHandledLog) {
                // Do nothing
                return;
            }

            // Remove The first frame
            $stacktrace->removeFrame(0);

            // Remove all the trace about Monolog as it's not interesting
            while(substr($stacktrace->getFrames()[0]['method'], 0, 8) === 'Monolog\\') {
                $stacktrace->removeFrame(0);
            }

        });
    }

    public function getClient() {
        return $this->client;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record) {

        $context = isset($record['context']) ? $record['context'] : array();
        $isPhpError = isset($context['code']) && !empty($context['message']) && !empty($context['file']) && isset($context['line']);
        $isUncaughtException = !empty($context['exception']) && strpos($record['message'], 'Uncaught Exception') === 0;

        $extra = isset($record['extra']) ? $record['extra'] : array();
        $context = isset($record['context']) ? $record['context'] : array();
        $extraAndContext = $extra + $context;

        if ($isUncaughtException) {
            $report = \Bugsnag\Report::fromPHPThrowable(
                $this->client->getConfig(),
                $context['exception']
            );
            $report->setUnhandled(true);
            $report->setSeverity('error');
            $report->setSeverityReason([
                'type' => 'unhandledException',
            ]);
            unset($extraAndContext['exception']);
            $report->setMetaData($extraAndContext);
            $this->client->notify($report);
            return;
        }

        if ($isPhpError) {
            $isFatal = strpos($record['message'], 'Fatal Error') === 0;
            $report = \Bugsnag\Report::fromPHPError(
                $this->client->getConfig(),
                $context['code'],
                $context['message'],
                $context['file'],
                $context['line'],
                $isFatal
            );
            if ($isFatal) {
                $report->setSeverity('error');
                $report->setSeverityReason([
                    'type' => 'unhandledException',
                ]);
            } else {
                $report->setSeverityReason([
                    'type' => 'unhandledError',
                    'attributes' => [
                        'errorType' => \Bugsnag\ErrorTypes::getName($context['code']),
                    ],
                ]);
            }
            unset($extraAndContext['code'], $extraAndContext['message'], $extraAndContext['file'], $extraAndContext['line']);
            $report->setUnhandled(true);
            $report->setMetaData($extraAndContext);

            $this->client->notify($report);
            return;
        }

        $severity = $this->getSeverity($record['level']);

        if (isset($record['context']['exception'])) {
            $this->client->notifyException(
                $record['context']['exception'],
                function (\Bugsnag\Report $report) use ($record, $severity, $extraAndContext) {
                    $report->setSeverity($severity);
                    $report->setMetaData($extraAndContext);
                }
            );
        } else {
            $this->client->notifyError(
                (string) $record['message'],
                (string) $record['formatted'],
                function (\Bugsnag\Report $report) use ($record, $severity, $extraAndContext) {
                    $report->setSeverity($severity);
                    $report->setMetaData($extraAndContext);
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

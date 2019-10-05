<?php declare(strict_types=1);

namespace MeadSteve\MonoSnag;

use Bugsnag\Client as BugsnagClient;
use Bugsnag\Report as BugsnagReport;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class BugsnagHandler extends AbstractProcessingHandler
{
    /**
     * monolog error codes mapped on to bugSnag severities.
     * @var string[]
     */
    protected const SEVERITY_MAPPING = [
        Logger::DEBUG => 'info',
        Logger::INFO => 'info',
        Logger::NOTICE => 'info',
        Logger::WARNING => 'warning',
        Logger::ERROR => 'error',
        Logger::CRITICAL => 'error',
        Logger::ALERT => 'error',
        Logger::EMERGENCY => 'error'
    ];

    /**
     * @var BugsnagClient
     */
    protected $client;

    public function __construct(BugsnagClient $client, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
    }

    /**
     * Writes the record down to the log of the implementing handler
     */
    protected function write(array $record): void
    {
        $severity = $this->getSeverity($record['level']);

        if (isset($record['context']['exception'])) {
            $this->client->notifyException(
                $record['context']['exception'],
                static function (BugsnagReport $report) use ($record, $severity) {
                    $report->setSeverity($severity);
                    if (isset($record['extra'])) {
                        $report->setMetaData($record['extra']);
                    }
                }
            );

            return;
        }

        $this->client->notifyError(
            (string)$record['message'],
            (string)$record['formatted'],
            static function (BugsnagReport $report) use ($record, $severity) {
                $report->setSeverity($severity);
                if (isset($record['extra'])) {
                    $report->setMetaData($record['extra']);
                }
            }
        );
    }

    /**
     * Returns the Bugsnag severiry from a monolog error code.
     */
    protected function getSeverity(int $errorCode): string
    {
        return self::SEVERITY_MAPPING[$errorCode] ?? self::SEVERITY_MAPPING[Logger::ERROR];
    }
}

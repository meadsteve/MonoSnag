MonoSnag
==========
[![Build Status](https://travis-ci.org/meadsteve/MonoSnag.png?branch=master)](https://travis-ci.org/meadsteve/MonoSnag)
[![Coverage Status](https://coveralls.io/repos/meadsteve/MonoSnag/badge.png?branch=master)](https://coveralls.io/r/meadsteve/MonoSnag?branch=master)
[![Latest Stable Version](https://poser.pugx.org/mead-steve/mono-snag/v/stable.svg)](https://packagist.org/packages/mead-steve/mono-snag)
[![License](https://poser.pugx.org/mead-steve/mono-snag/license.svg)](https://packagist.org/packages/mead-steve/mono-snag)
[![Monthly Downloads](https://poser.pugx.org/mead-steve/mono-snag/d/monthly.png)](https://packagist.org/packages/mead-steve/mono-snag)

Monolog Handler connection to [Bugsnag](http://bugsnag.com)

Installation
------------
Via Composer using
```shell
composer require mead-steve/mono-snag:^3.0.0
```
you should already have bugsnag and monolog required so your composer.json should look like:
```js
  "require": {
        ...,
        "monolog/monolog": "____",
        "bugsnag/bugsnag": "____",
        ...,
        "mead-steve/mono-snag": "^3.0.0"
    }
```


Usage
------------

A handler is provided that wraps up a Bugsnag client. By default the handler will grab anything at
Logger::ERROR and above and send it to Bugsnag.

```php

$logger  = new Monolog\Logger("Example");

$bugsnagClient = new Bugsnag\Client('YOUR-BUGSNAG-API-KEY-HERE');
//... bugsnag specific config goes here.
$bugsnagHandler = new \MeadSteve\MonoSnag\BugsnagHandler($bugsnagClient);

$logger->pushHandler($bugsnagHandler);

// The following error will get sent automatically to Bugsnag
$logger->addError("oh no!", array('exception' => new \Exception("ohnoception")));

```

Avoid Handler in stacktrace
---------------------------

```
        $bugsnagClient->registerCallback(function ($report) {
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
```

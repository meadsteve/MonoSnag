MonoSnag
==========
[![Build Status](https://travis-ci.org/meadsteve/MonoSnag.png?branch=master)](https://travis-ci.org/meadsteve/MonoSnag)

Monolog Handler connection to [Bugsnag](bugsnag.com)

Installation
------------
Via Composer:
Add the following to your composer.json:
```js
  "require": {
        "mead-steve/mono-snag": "1.*"
    }
```

Usage
------------

A handler is provided that wraps up a Bugsnag client. By default the handler will grab anything at
Logger::ERROR and above and send it to Bugsnag.

```php

$logger  = new Monolog\Logger("Example");

$bugsnagClient = new Bugsnag_Client('YOUR-BUGSNAG-API-KEY-HERE');
//... bugsnag specific config goes here.
$bugsnagHandler = new \MeadSteve\MonoSnag\BugsnagHandler($bugsnagClient);

$logger->pushHandler($bugsnagHandler);

// The following error will get sent automatically to Bugsnag
$logger->addError("oh no!", array('exception' => new \Exception("ohnoception")));

```

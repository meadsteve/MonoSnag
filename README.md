MonoSnag
==========
[![Test PHP 7 & 8](https://github.com/meadsteve/MonoSnag/actions/workflows/php.yml/badge.svg)](https://github.com/meadsteve/MonoSnag/actions/workflows/php.yml)
[![Latest Stable Version](https://poser.pugx.org/mead-steve/mono-snag/v/stable.svg)](https://packagist.org/packages/mead-steve/mono-snag)
[![License](https://poser.pugx.org/mead-steve/mono-snag/license.svg)](https://packagist.org/packages/mead-steve/mono-snag)
[![Monthly Downloads](https://poser.pugx.org/mead-steve/mono-snag/d/monthly.png)](https://packagist.org/packages/mead-steve/mono-snag)

[Monolog](https://seldaek.github.io/monolog/) Handler connection to [Bugsnag](http://bugsnag.com)

Installation
------------
Via Composer using
```shell
composer require mead-steve/mono-snag
```


Usage
------------

A handler is provided that wraps up a Bugsnag client. By default, the handler will grab anything at
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

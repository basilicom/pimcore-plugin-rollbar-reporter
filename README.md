RollbarReporter Pimcore Plugin
================================================

Developer info: [Pimcore at basilicom](http://basilicom.de/en/pimcore)

## Synopsis

This Pimcore http://www.pimcore.org plugin simplifies using
and configuring the Rollbar reporting service: https://rollbar.com/

## Code Example / Method of Operation

If installed and enabled, the following Rollbar properties
can be configured via the website/var/config/rollbar.xml file:



## Motivation

This plugin simplifies using the Rollbar service by adding it
automatically on startup and using an easy XML based configuration
file accessable via the Pimcore Plugin management system.

## Installation

Add "basilicom-pimcore-plugin/rollbar-reporter" as a requirement to the
composer.json in the toplevel directory of your Pimcore installation.

Example:

    {
        "require": {
            "basilicom-pimcore-plugin/rollbar-reporter": "~1.0"
        }
    }
    
Install the plugin via the Pimcore Extension Manager. Press the "Configure" button of the
RollbarReporter plugin from within the Extension Manager and set the "accessToken" property
accordingly.

In order to transmit info to the Rollbar servers, set the "enabled" property to "1", too.

If you want to include errors/traces from backend requests, too: Set the
"excludeBackend" property in the xml file to "0".

If you want to include/trace CLI requests, too: Set the "excludeCli" property in
the xml file to "0" (this is needed for tracing maintenance.php runs).

## API Reference

The following static methods are provided as a wrapper for the original
Rollbar functions (and respect the enabled state of the plugin):
 
* \RollbarReporter\Plugin::exception(Exception $exception)
* \RollbarReporter\Plugin::message($message, $level, $extra)
* \RollbarReporter\Plugin::flush(Exception $exception)

## Tests

* none

## Todo

* add more Rollbar configuration options

## Contributors

* Christoph Luehr <christoph.luehr@basilicom.de>

## License

* GPLv3

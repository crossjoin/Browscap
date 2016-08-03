# Browscap Parsing Class

[![Author](http://img.shields.io/badge/author-@cziegenberg-blue.svg?style=flat-square)](https://twitter.com/cziegenberg)
[![Quality Score](https://img.shields.io/scrutinizer/g/crossjoin/Browscap/2.x.svg?style=flat-square)](https://scrutinizer-ci.com/g/crossjoin/Browscap)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/crossjoin/Browscap.svg?style=flat-square)](https://packagist.org/packages/crossjoin/Browscap)
[![Total Downloads](https://img.shields.io/packagist/dt/crossjoin/Browscap.svg?style=flat-square)](https://packagist.org/packages/crossjoin/Browscap)
[![Build](https://img.shields.io/travis/crossjoin/Browscap/2.x.svg)](https://travis-ci.org/crossjoin/Browscap)

## Introduction
Crossjoin\Browscap allows to check for browser settings based on the user agent string, using the data from
the [Browser Capabilities Project](browscap.org).

Although PHP has the native [`get_browser()`](http://php.net/get_browser) function to do this, this implementation
offers some advantages:
- The PHP function requires to set the path of the browscap.ini file in the php.ini directive
[`browscap`](http://www.php.net/manual/en/misc.configuration.php#ini.browscap), which is flagged as `PHP_INI_SYSTEM`
(so it can only be set in php.ini or httpd.conf, which isn't allowed in many cases, e.g. in shared hosting
environments).
- It's much faster than the PHP function (several hundred times, depending on the PHP version, the searched user agent
and other factors)
- It allows automatic updates of the Browscap source data, so you're always up-to-date.

Compared to other PHP Browscap parsers, this implementation offers the following advantages:
- The default parser is very fast due to optimized storage in an internal SQLite database.
- It supports the PHP versions 5.6.x to 7.x and uses newest available features for best performance.
- It has a very low memory consumption (for parsing and generating parser data).
- All components are extensible - use your own source, parser (writer and reader) or formatter.
- Use property filters to remove unnecessary Browscap properties from the parser data and/or the output.
- Either use the auto-update feature or run updates via command-line instead.

You can also switch the type of data set to use:
- The `standard` data set (standard set of browser properties)
- The `lite` data set (reduced set of browser properties)
- The `full` data set (additional browser properties)
- Tip: Use the smallest possible data set for best performance, because the more detailed the data, the more user agents needs to be compared.

## Requirements
- PHP >= 5.6 (support for older versions see below)
- The 'pdo_sqlite' or 'sqlite3' extension (please not that this is not checked on composer install/update,
because only one of these extension is required and composer doesn't support this type of requirement).
- For best performance the SQlite library version should be >= 3.8.3 (to use recursive queries).
- For updates via download: cURL extension, `allow_url_fopen` enabled in php.ini (for more details see the [GuzzleHttp documentation](http://docs.guzzlephp.org/en/latest/))

### Releases for older PHP Versions
- For older PHP versions see [Crossjoin\Browscap 1.x](https://github.com/crossjoin/Browscap/tree/1.x)

## Package installation
Crossjoin\Browscap is provided as a [Composer](https://getcomposer.org) package which can be installed by adding the package to your composer.json
file:
```php
{
    "require": {
        "crossjoin/browscap": "~2.0.0"
    }
}
```

## Basic Usage

### Simple example

Normally you can directly use the Browscap parser. Missing data for the parser will be created automatically
if possible (trying several available options).

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get current browser details (taken from $_SERVER['HTTP_USER_AGENT'])
$settings = $browscap->getBrowser();
 
// or explicitly set the user agent to check
$settings = $browscap->getBrowser('user agent string');
```

### Automatic updates

Although missing data are created automatically, the update automatic update is disabled by default (which is different
from version 1.x). To activate automatic updates, you must set the update probability.

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Activate auto-updates
// Value: Percentage of getBrowser calls that will trigger the update check
$browscap->setAutoUpdateProbability(1);
 
// Get current browser details (taken from $_SERVER['HTTP_USER_AGENT'])
$settings = $browscap->getBrowser();
```

### Manual updates

Manual updates can be run using a script...

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
$forceUpdate = false; // If you do not force an update, it will only be done if required
 
// Run update
$browscap->update($forceUpdate);
```

or via the command-line interface (you will find the 'browscap' or 'browscap.bat' in Composers bin directory,
normally `vendor/bin`):
```
browscap update [--force]
```

## Formatters

### Replacement for the PHP get_browser() function

By default the returned settings are formatted like the result of the PHP get_browser() function. So you will get an
standard PHP object, with a special property/property value format. As with get_browser(), you can also get an array
as return value by modifying the formatter:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get standard object
$settings = $browscap->getBrowser();
 
// Get array
$arrayFormatter = new \Crossjoin\Browscap\Formatter\PhpGetBrowser(true);
$browscap->setFormatter($arrayFormatter);
$settings = $browscap->getBrowser();
```

Alternatively you can use the Browscap object as a function, with the same arguments like PHP get_browser() function,
so is't much easier to use it as a replacement:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get standard object
$settings = $browscap('user agent string');
 
// Get array
$settings = $browscap('user agent string', true);
```

### Optimized formatter

The standard format isn't always optimal, the new `Optimized` formatter is often the better option. It doesn't change
the property names, returns all values with correct types (if valid for all possible property values) and replaces
the 'unknown' strings with NULL values. It also removes no more used properties from the result set (like 'AolVersion').

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get optimized result
$optimizedFormatter = new \Crossjoin\Browscap\Formatter\Optimized();
$browscap->setFormatter($optimizedFormatter);
$settings = $browscap->getBrowser();
```

### Custom formatters

Of course you can also create your own formatter, either by using the general formatter
`\Crossjoin\Browscap\Formatter\Formatter` and setting the required options (see below), or by creating a new one that
extends `\Crossjoin\Browscap\Formatter\FormatterInterface`:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get customized result
$formatter = new \Crossjoin\Browscap\Formatter\Formatter(
    \Crossjoin\Browscap\Formatter\Formatter::RETURN_ARRAY |
    \Crossjoin\Browscap\Formatter\Formatter::KEY_LOWER |
    \Crossjoin\Browscap\Formatter\Formatter::VALUE_TYPED
);
$browscap->setFormatter($formatter);
$settings = $browscap->getBrowser();
 
// Use custom formatter tah extends \Crossjoin\Browscap\Formatter\FormatterInterface
$formatter = new \My\Formatter();
$browscap->setFormatter($formatter);
$settings = $browscap->getBrowser();
```

## Property Filters

As mentioned before, the `Optimized` formatter removes properties from the returned data. This is done by a filter,
which is a new feature in version 2.x/3.x.

### Filter the output

You can define individual property filters for the formatter:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set list of allowed properties
$filter = new \Crossjoin\Browscap\PropertyFilter\Allowed();
$filter->setProperties(['Version', 'Browser', 'isMobileDevice']);
$browscap->getFormatter()->setPropertyFilter($filter);
 
// Only the allowed properties will be returned...
$settings = $browscap->getBrowser();
 
// Set list of disallowed properties
// IMPORTANT: The new property filter will replace the previous one!
$filter = new \Crossjoin\Browscap\PropertyFilter\Disallowed();
$filter->addProperty('Comment');
$filter->addProperty('browser_name_pattern');
$filter->addProperty('browser_name_regex');
 
// Properties except the filtered ones will be returned...
$settings = $browscap->getBrowser();
 
// Remove the filter by setting it to the default filter
$filter = new \Crossjoin\Browscap\PropertyFilter\None();
$browscap->getFormatter()->setPropertyFilter($filter);
 
// All properties will be returned...
$settings = $browscap->getBrowser();
```

### Filter the parser data

No only the output can be filtered. You can also filter the data at a higher level, when creating his data set
from the source, which can reduce the size of the generated database by up to 50%:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set list of allowed properties
$filter = new \Crossjoin\Browscap\PropertyFilter\Allowed();
$filter->setProperties(['Version', 'Browser', 'isMobileDevice']);
$browscap->getParser()->setPropertyFilter($filter);
 
// Only the filtered properties are returned...
$settings = $browscap->getBrowser();
 
// Of course you can still define additional property filters for the formatter
// to further reduce the number of properties.
$filter = new \Crossjoin\Browscap\PropertyFilter\Disallowed(['isMobileDevice']);
$browscap->getFormatter()->setPropertyFilter($filter);
 
// Properties are now reduced to 'Version' and 'Browser'...
// NOTE: New parser property filters will trigger an update of the parser data!
$settings = $browscap->getBrowser();
```

You can also set filters for the parser when using the command-line interface:
```
browscap update --filter-allowed Version,Browser,isMobileDevice
```
```
browscap update --filter-disallowed Version,Browser,isMobileDevice
```

## Sources

By default, the current browscap (PHP ini variant) source is downloaded automatically (`standard` type).

### Change the downloaded source type

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set the 'standard' source (medium data set, with standard properties)
$type = \Crossjoin\Browscap\Type::STANDARD;
$source = new \Crossjoin\Browscap\Source\Ini\BrowscapOrg($type);
$browscap->getParser()->setSource($source);
 
// Set the 'lite' source (smallest data set, with the most important properties)
$type = \Crossjoin\Browscap\Type::LITE;
$source = new \Crossjoin\Browscap\Source\Ini\BrowscapOrg($type);
$browscap->getParser()->setSource($source);
 
// Set the 'full' source (largest data set, with additional properties)
$type = \Crossjoin\Browscap\Type::FULL;
$source = new \Crossjoin\Browscap\Source\Ini\BrowscapOrg($type);
$browscap->getParser()->setSource($source);
 
// Get properties...
// NOTE: New parser sources will trigger an update of the parser data!
$settings = $browscap->getBrowser();
```

You can also set the source type when using the command-line interface:
```
browscap update --ini-load standard
```
```
browscap update --ini-load lite
```
```
browscap update --ini-load full
```

### Use the source file defined in the `browscap` PHP directive

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Use the browscap file defined in the PHP settings (e.g. in php.ini)
$source = new \Crossjoin\Browscap\Source\Ini\PhpSetting();
$browscap->getParser()->setSource($source);
 
// Get properties...
// NOTE: New parser sources will trigger an update of the parser data!
$settings = $browscap->getBrowser();
```

You can also switch to this source when using the command-line interface:
```
browscap update --ini-php
```

### Use a custom source file

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set a custom file as source
$source = new \Crossjoin\Browscap\Source\Ini\File('path/to/browscap.ini');
$browscap->getParser()->setSource($source);
 
// Get properties...
// NOTE: New parser sources will trigger an update of the parser data!
$settings = $browscap->getBrowser();
```

Setting the source file is also possible when using the command-line interface:
```
browscap update --ini-file path/to/browscap.ini
```

## Misc settings

### Data directory

The parser data are saved in the temporary directory of the system, but you can define an own one:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set a custom data directory
$parser = new \Crossjoin\Browscap\Parser\Sqlite\Parser('path/to/data/directory');
$browscap->setParser($parser);
 
// Get properties...
// NOTE: A new parser data directory will trigger an update of the parser data!
$settings = $browscap->getBrowser();
```

You can also set the data directory when using the command-line interface:
```
browscap update --dir path/to/data/directory
```

### Client settings for the source download

If you download the source, you perhaps want to use a proxy or other settings for the client. You can do so by
providing the settings for the GuzzleHttp client (see the [GuzzleHttp documentation](http://docs.guzzlephp.org/en/latest/)):

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set a custom data directory
$type = \Crossjoin\Browscap\Type::STANDARD;
$clientSettings = ['proxy' => 'tcp://localhost:8125'];
$source = new \Crossjoin\Browscap\Source\Ini\BrowscapOrg($type, $clientSettings);
$browscap->getParser()->setSource($source);
 
// Get properties...
$settings = $browscap->getBrowser();
```

Please note: Currently this is not possible when using the command-line interface.

## Performance tips

### Always use the smallest data set

The Browscap data are available in three versions - `lite`, `standard` and `full` - with a different number
of properties for the browsers. Of course also the database size for the parser increases with the number of
properties contained in the source data - but this has only a small influence on the performance, it only
affects some request when the database is not cached by PHP.

More important is the number of patterns that increases with the number of properties, because the checks
have to become more detailed to differ them. This takes the most time when parsing, because pre-filtering patterns is
very difficult and often multiple patterns match the given user agent string - so in some cases several thousand
patterns are checked for a single user agent string.

So you should always use the smallest possible source type, because it contains the lowest number of patterns. Also
property filters wouldn't help here - they help to reduce the database size, but not the number of patterns. For example
if you use the `full` source type and set a filter to get ony the properties of the `lite` source type, you will get
the same result for both types, but it will take about thrice the time for the `full` type.

## Issues and feature requests

Please report your issues and ask for new features on the GitHub Issue Tracker: 
https://github.com/crossjoin/browscap/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini file to Browscap: 
https://github.com/browscap/browscap/issues

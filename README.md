# Browscap Parsing Class

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
- It includes automatic updates of the Browscap source data

Compared to other PHP Browscap parsers, this implementation offers the following advantages
- The default parser very fast due to optimized storage in an internal SQLite database
- It supports the PHP versions 5.6.x to 7.0.x and uses newest available features for best performance
- It has a very low memory consumption (for parsing and generating parser data)
- All components are extensible - use your own source, parser (writer and reader) or formatter
- Use property filters to remove unnecessary Browscap properties from the parser data or the output.
- Either use the auto-update feature or run updates via command-line instead.

You can also switch the type of data set to use:
- The `lite` data set (with the most important user agent patterns only and the default properties)
- The `standard` data set (containing all known user agent patterns and the default properties)
- The `full` data set (with all known user agent patterns and additional properties)
- The parsing time increases with the number of user agent patterns contained in the source, but it's fast for all
versions.

## Requirements
- PHP 7.x (support for older versions see below)
- The 'pdo_sqlite' or 'sqlite3' extension (please not that this is not checked on composer install/update,
because only one of these extension is required and composer doesn't support this).
- For updates via download: cURL extension, `allow_url_fopen` enabled in php.ini (for more details see the [GuzzleHttp documentation](http://docs.guzzlephp.org/en/latest/))

### Releases for older PHP Versions
- For PHP 5.6.x please use [Crossjoin\Browscap 2.x](https://github.com/crossjoin/Browscap/tree/2.x) (coming soon!)
- For older PHP versions see [Crossjoin\Browscap 1.x](https://github.com/crossjoin/Browscap/tree/1.x).)

## Package installation
Crossjoin\Browscap is provided as a Composer package which can be installed by adding the package to your composer.json 
file:
```php
{
    "require": {
        "crossjoin/browscap": "~3.0.0"
    }
}
```

## Basic Usage

### Simple example

You can directly use the Browscap parser. If the data for the parser are missing, they will be created automatically
if possible (trying several available options).

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get current browser details (taken from $_SERVER['HTTP_USER_AGENT'])
$settings = $browscap->getBrowser();
```

### Automatic updates

Although missing data are created automatically, automatic updates are disabled by default (which is different
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

or via the command-line interface (normally you will find 'browscap' or 'browscap.bat' in composers 'vendor/bin/'):
```
browscap update [--force]
```

## Formatters

### Replacement for the PHP get_browser() function

The returned setting are by default formatted like the result of the PHP get_browser() function (an default object
with values in a special format). You can also get an array as return value, by modifying the formatter:

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Get standard object
$settings = $browscap->getBrowser();
 
// Get array
$arrayFormatter = new \Crossjoin\Browscap\Formatter\PhpGetBrowser(
    \Crossjoin\Browscap\Formatter\PhpGetBrowser::RETURN_ARRAY
);
$browscap->setFormatter($arrayFormatter);
$settings = $browscap->getBrowser();
```

Alternatively you can use the Browscap object as function, with the same arguments like PHPs get_browser():

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
$userAgent = $_SERVER['HTTP_USER_AGENT'];
 
// Get standard object
$settings = $browscap($userAgent);
 
// Get array
$settings = $browscap($userAgent, true);
```

### Optimized formatter

If you want to get a better result, you should use the `Optimized` formatter. It doesn't change the keys, returns
all values with correct types (if valid for all possible property values) and replaces 'unknown' strings with NULL
values. It also removes no more used properties from the result (e.g. 'AolVersion').

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
extends the `\Crossjoin\Browscap\Formatter\FormatterInterface`:

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

As mentioned before, the `Optimized` formatter removes properties from the returned data. This
is done by a filter, which is a new feature from version 2.x/3.x.

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
from the source (which can reduce the size of the generated data by up to 50%):

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

By default, the current browscap (PHP ini) source is downloaded automatically (`standard` type).

### Change the downloaded source type

```php
<?php
// Include composer auto-loader
require_once '../vendor/autoload.php';
 
// Init
$browscap = new \Crossjoin\Browscap\Browscap();
 
// Set the 'standard' source (medium data set, with default properties)
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

## Misc

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

If you download the source (default), you perhaps want to use a proxy or other settings for
the client. You can do so by providing the settings for the GuzzleHttp client (see the [GuzzleHttp documentation](http://docs.guzzlephp.org/en/latest/)):

This is currently not possible when using the command line.

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
$browscap->setParser($parser);
 
// Get properties...
$settings = $browscap->getBrowser();
```

## Issues and feature requests

Please report your issues and ask for new features on the GitHub Issue Tracker: 
https://github.com/crossjoin/browscap/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini file to Browscap: 
https://github.com/browscap/browscap/issues
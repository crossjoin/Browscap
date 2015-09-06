# Browscap Parsing Class

## Introduction
Crossjoin\Browscap allows to check for browser settings based on the user agent string, using the data from Browscap 
(the [Browser Capabilities Project](browscap.org)). 

Although PHP has the native [`get_browser()`](http://php.net/get_browser) function to do this, this implementation 
offers some advantages:
- The PHP function requires to set the path of the browscap.ini file in the php.ini directive 
[`browscap`](http://www.php.net/manual/en/misc.configuration.php#ini.browscap), which is flagged as `PHP_INI_SYSTEM` 
(so it can only be set in php.ini or httpd.conf, which isn't allowed in many cases, e.g. in shared hosting 
environments).
- It's much faster than the PHP function (about 500 times, depending on the PHP version, the searched user agent and 
other factors)
- It includes automatic updates of the Browscap source

Compared to other PHP Browscap parsers, this implementation offers the following advantages
- It's very fast due to optimized caching of the Browscap data, for example it's 
[much faster](https://github.com/browscap/browscap-php/issues/20#issuecomment-137993153) than  
[Browscap-PHP](https://github.com/browscap/browscap-php)
- It supports all PHP versions from 5.3.x to 7.0.x and uses newest available features for best performance
- It has a very low memory consumption (for parsing and generating cache data)
- All components are extensible - use your own parser, updater, formatter or cache functionality

You can also switch the type of data set to use - small, medium (default) or large:
- The default data set (containing all known browsers and the default properties)
- The small data set (with the most important browser only and the default properties)
- The large data set (with all known browsers and additional properties)
- The parsing time is fast for all versions, it mainly affects the time and memory consumption for the cache data 
preparation.

## Requirements
- PHP 5.3+ (it has been successfully tested with PHP 5.3.28 - PHP 7.0.0RC2, perhaps also older versions still work)

### Suggestions
- PHP 5.5+ recommended (to be able to use generators, which reduces memory consumption a lot)
- PHP 7.0+ for best performance (again much faster than PHP 5.x)
- For automatic updates: cURL extension, `allow_url_fopen` enabled in php.ini, or local Browscap file `browscap` set in php.ini

## Package installation
Crossjoin\Browscap is provided as a Composer package which can be installed by adding the package to your composer.json 
file:
```php
{
    "require": {
        "crossjoin/browscap": "1.0.*"
    }
}
```

You can also install it manually and use a [PSR-0-compliant](http://www.php-fig.org/psr/psr-0/) autoloader (e.g. from 
the [Zend Framework](http://framework.zend.com/manual/2.3/en/modules/zend.loader.standard-autoloader.html) or a 
[standalone class](https://gist.github.com/lisachenko/1335891)).

## Usage

### Simple example

You can directly use the Browscap parser. The Browscap data are automatically downloaded, updated and prepared when 
required.

```php
<?php
// Include Composer autoloader
require_once '../vendor/autoload.php';
  
// Get browser details
$browscap = new \Crossjoin\Browscap\Browscap();
$settings = $browscap->getBrowser()->getData();
```

### Recommended usage in production

In production you will prefer to update the data in the background. Therefore we deactivate the automatic updates when 
creating the `\Crossjoin\Browscap\Browscap` instance...

```php
<?php
// Include Composer autoloader
require_once '../vendor/autoload.php';
  
// Get browser details
$browscap = new \Crossjoin\Browscap\Browscap(false); // disables automatic updates
$settings = $browscap->getBrowser()->getData();
```

...and manually update the data using a second script (e.g. called via a cron job):

```php
<?php
// Include Composer autoloader
require_once '../vendor/autoload.php';
  
// Get browser details
$browscap = new \Crossjoin\Browscap\Browscap(false);
  
// By default the version is checked every 5 days. When you use a cron job, you
// probably want to controll this interval in your cron job configuration. To do
// so, set the interval to zero here to check for a new version every time you
// call the cron job.
$browscap->getUpdater()->setInterval(0);
  
// Run the browscap data update and preparation
$browscap->update();
```

### Advanced usage

#### Switch Browscap data set

Browscap data are available in multiple versions. A very small data set with the most important browsers and search 
engines only, a medium data set (default) with all browsers and search engines and the same properties as returned by 
the native PHP function `get_browser()`, and a large data set with additional properties.

```php
// Set the data set type to use.
//
// Possible values:
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_DEFAULT
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_SMALL
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_LARGE
\Crossjoin\Browscap\Browscap::setDatasetType(\Crossjoin\Browscap\Browscap::DATASET_TYPE_LARGE);
```

#### Set cache directory

By default the system temp directory is used to cache the Browscap data. Of course you can also define a different one:

```php
// Set an own cache directory (otherwise the system temp directory is used)
\Crossjoin\Browscap\Cache\File::setCacheDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'tmp');
```

#### Switch the updater

The library automatically checks which method can be used to load the update data. If the cURL extension is available, 
this one is used to load the Browscap data (`\Crossjoin\Browscap\Updater\Curl`). Otherwise `file_get_contents` is used 
(`\Crossjoin\Browscap\Updater\FileGetContents`), if the 
[php.ini setting `allow_url_fopen`](http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) is 
enabled. 

If no method is available to load the data from the Browscap servers, the 
[php.ini setting `browscap`](http://php.net/manual/en/misc.configuration.php#ini.browscap) is used to load a local 
Browscap version (`\Crossjoin\Browscap\Updater\Local`). If no Browscap file is configured, an empty updater is set 
(`\Crossjoin\Browscap\Updater\None`), which won't update anything.

If you prefer to set the updater manually, you can do it as follows:

```php
// Set the local updater and use your own Browscap data file
$updater = new \Crossjoin\Browscap\Updater\Local();
$updater->setOption('LocalFile', __DIR__ . DIRECTORY_SEPARATOR . 'browscap.ini');
\Crossjoin\Browscap\Browscap::setUpdater($updater);
```

#### Proxy configuration

You can also configure a proxy server for loading the Browscap data.

```php
// Get the updater instance
$updater = \Crossjoin\Browscap\Browscap::getUpdater();
  
// Set HTTP proxy server (without authentication)
$updater->setOptions(array(
    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTP,
    'ProxyHost'     => '12.34.56.78',
    'ProxyPort'     => '8080',
));
  
// Set HTTPS proxy server (with HTTP Basic authentication, the default mode.
// This HAS NOT BEEN TESTED YET, please report problems!
//$updater->setOptions(array(
//    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTPS,
//    'ProxyHost'     => '23.23.74.33',
//    'ProxyPort'     => '80',
//    'ProxyUser'     => 'user',
//    'ProxyPassword' => 'p4ssw0rd',
//));
  
// Set HTTPS proxy server (with NTLM authentication, for cURL updater only.
// This HAS NOT BEEN TESTED YET, please report problems!
//$updater->setOptions(array(
//    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTPS,
//    'ProxyHost'     => '23.23.74.33',
//    'ProxyPort'     => '80',
//    'ProxyAuth'     => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_AUTH_NTLM,
//    'ProxyUser'     => 'user',
//    'ProxyPassword' => 'p4ssw0rd',
//));
```

#### Format the result

By default, the returned result is formatted like the result of the native PHP function `get_browser()`, but you can 
use your own formatter to adjust the result:

```php
// Set an own formatter that extends \Crossjoin\Browscap\Formatter\AbstractFormatter
//$formatter = new \My\Browscap\Formatter\Extended();
//\Crossjoin\Browscap\Browscap::setFormatter($formatter);
```

#### Change the parser

You want to implement your own parser? Why not!

```php
// Set an own parser implementation that extends \Crossjoin\Browscap\Parser\AbstractParser 
// (also for other formats than INI)
$parser = new \My\Browscap\Parser\Ini();
\Crossjoin\Browscap\Browscap::setParser($parser);
```

## Issues and feature requests

Please report your issues and ask for new features on the GitHub Issue Tracker: 
https://github.com/crossjoin/browscap/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini file to Browscap: 
https://github.com/browscap/browscap/issues
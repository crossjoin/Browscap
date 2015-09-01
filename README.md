Browscap Parsing Class
==============

Introduction
--------------

Crossjoin\Browscap allows to check for browser settings based on the user agent string, using the data from Browscap 
(the [Browser Capabilities Project](browscap.org)). 

Although PHP has the native [`get_browser()`](http://php.net/get_browser) function to do this, this implementation offers some advantages:
- The PHP function requires to set the path of the browscap.ini file in the php.ini directive [`browscap`](http://www.php.net/manual/en/misc.configuration.php#ini.browscap), which is flagged as `PHP_INI_SYSTEM` (so it can only be set in php.ini or httpd.conf, which isn't allowed in many cases, e.g. in shared hosting environments).
- It's much faster than the PHP function (about 500 times, depending on the PHP version, the searched user agent and other factors)
- It includes automatic updates of the Browscap source

Compared to other PHP Browscap parsers, this implementation offers the following advantages
- It's very fast due to optimized caching of the Browscap data, for example it's [more than 13 times performant](https://github.com/crossjoin/Browscap/issues/12#issuecomment-135963430) compared to [Browscap-PHP](https://github.com/browscap/browscap-php)
- It supports all PHP versions from 5.3.x to 7.0.x and uses newest available features for best performance
- It has a very low memory consumption (for parsing and generating cache data)
- All components are extensible - use your own parser, updater, formatter or cache functionality

You can also switch the type of data set to use - small, medium (default) or large:
- The default data set (containing all known browsers and the default properties)
- The small data set (with the most important browser only and the default properties)
- The large data set (with all known browsers and additional properties)
- The parsing time is fast for all versions, it mainly affects the time and memory consumption for the cache data preparation.

Requirements
--------------
- PHP 5.3+ (it has been successfully tested with PHP 5.3.28 - PHP 7.0.0RC1, perhaps also older versions still work)

Suggestions
--------------
- PHP 5.5+ recommended (to be able to use generators, which reduces memory consumption a lot)
- PHP 7.0+ for best performance (50%-60% faster than PHP 5.5+)
- For automatic updates: cURL extension or `allow_url_fopen` enabled in php.ini

Package installation
--------------
Crossjoin\Browscap is provided as a Composer package which can be installed by adding the package to your composer.json file:
```
{
    "require": {
        "crossjoin/browscap": "1.0.*"
    }
}
```

You can also install it manually and use a [PSR-0-compliant](http://www.php-fig.org/psr/psr-0/) autoloader (e.g. from the [Zend Framwork](http://framework.zend.com/manual/2.3/en/modules/zend.loader.standard-autoloader.html) or a [standalone class](https://gist.github.com/lisachenko/1335891)).


Simple example
--------------
```
<?php
// include Composer autoloader
require_once '../vendor/autoload.php';

// get browser details
$browscap = new \Crossjoin\Browscap\Browscap();
$settings = $browscap->getBrowser()->getData();
```


Advanced example
--------------
```
<?php
// include Composer autoloader
require_once '../vendor/autoload.php';

// set an own cache directory (otherwise the system temp directory is used)
\Crossjoin\Browscap\Cache\File::setCacheDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'tmp');

// set the data set type to use
// possible values:
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_DEFAULT
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_SMALL
// - \Crossjoin\Browscap\Browscap::DATASET_TYPE_LARGE
\Crossjoin\Browscap\Browscap::setDatasetType(\Crossjoin\Browscap\Browscap::DATASET_TYPE_SMALL);

// disable automatic updates
$updater = new \Crossjoin\Browscap\Updater\None();
\Crossjoin\Browscap\Browscap::setUpdater($updater);

// set local updater that extends \Crossjoin\Browscap\Updater\AbstractUpdater or 
// \Crossjoin\Browscap\Updater\AbstractUpdaterRemote
$updater = new \Crossjoin\Browscap\Updater\Local();
$updater->setOption('LocalFile', __DIR__ . DIRECTORY_SEPARATOR . 'browscap.ini');
\Crossjoin\Browscap\Browscap::setUpdater($updater);

// set HTTP proxy server (without authentication)
//$updater->setOptions(array(
//    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTP,
//    'ProxyHost'     => '79.143.82.242',
//    'ProxyPort'     => '8080',
//));

// set HTTPS proxy server (with HTTP Basic authentication, the default mode - NOT TESTED YET!)
//$updater->setOptions(array(
//    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTPS,
//    'ProxyHost'     => '23.23.74.33',
//    'ProxyPort'     => '80',
//    'ProxyUser'     => 'user',
//    'ProxyPassword' => 'p4ssw0rd',
//));

// set HTTPS proxy server (with NTLM authentication, for cURL updater only - NOT TESTED YET!)
//$updater->setOptions(array(
//    'ProxyProtocol' => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_PROTOCOL_HTTPS,
//    'ProxyHost'     => '23.23.74.33',
//    'ProxyPort'     => '80',
//    'ProxyAuth'     => \Crossjoin\Browscap\Updater\AbstractUpdaterRemote::PROXY_AUTH_NTLM,
//    'ProxyUser'     => 'user',
//    'ProxyPassword' => 'p4ssw0rd',
//));

// set an own parser implementation that extends \Crossjoin\Browscap\Parser\AbstractParser 
// (also for other formats than INI)
//$parser = new \My\Browscap\Parser\Ini();
//\Crossjoin\Browscap\Browscap::setParser($parser);

// set an own formatter that extends \Crossjoin\Browscap\Formatter\AbstractFormatter
//$formatter = new \My\Browscap\Formatter\Extended();
//\Crossjoin\Browscap\Browscap::setFormatter($formatter);

$browscap = new \Crossjoin\Browscap\Browscap();
$settings = $browscap->getBrowser()->getData();
```


Background
--------------

I created Crossjoin\Browscap because I searched for a good Browscap parser class. 

First I worked with the [Browscap-PHP](https://github.com/browscap/browscap-php) class, but it required a lot of memory and wasn't really extensible. After contributing some optimizations to the project, I realized that I need to build the parser from scratch, to be able to further optimize it.

This is the result, published as a separate project as it's not compatible to Browscap-PHP:
- No more direct access to object properties
- New default format (to be a real replacement of PHP get_browser(): object instead of an array, lower-case keys by default
- Some missing features (see below)


Things to do...
--------------
- Update via fsockopen isn't possible (possible in [Browscap-PHP](https://github.com/browscap/browscap-php))


Issues and feature requests
---------------------------

Please report your issues and ask for new features on the GitHub Issue Tracker: 
https://github.com/crossjoin/browscap/issues

Please report incorrectly identified User Agents and browser detect in the browscap.ini file to Browscap: 
https://github.com/browscap/browscap/issues
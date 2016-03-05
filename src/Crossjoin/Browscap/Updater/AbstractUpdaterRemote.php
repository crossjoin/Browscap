<?php
namespace Crossjoin\Browscap\Updater;

use Crossjoin\Browscap\Browscap;

/**
 * Abstract updater class (for remote sources)
 *
 * With class extends the abstract updater with methods that are required
 * or remote sources.
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
abstract class AbstractUpdaterRemote extends AbstractUpdater
{
    const PROXY_PROTOCOL_HTTP  = 'http';
    const PROXY_PROTOCOL_HTTPS = 'https';

    const PROXY_AUTH_BASIC     = 'basic';
    const PROXY_AUTH_NTLM      = 'ntlm';

    /**
     * The URL to get the current Browscap data (in the configured format)
     *
     * @var string
     */
    protected $browscapSourceUrl = 'http://browscap.org/stream?q=%t';

    /**
     * The URL to detect the current Browscap version
     * (time string like 'Thu, 08 May 2014 07:17:44 +0000' that is converted to a time stamp)
     *
     * @var string
     */
    protected $browscapVersionUrl = 'http://browscap.org/version';

    /**
     * The URL to detect the current Browscap version number
     *
     * @var string
     */
    protected $browscapVersionNumberUrl = 'http://browscap.org/version-number';

    /**
     * The user agent to include in the requests made by the class during the
     * update process. (Based on the user agent in the official Browscap-PHP class)
     *
     * @var string
     */
    protected $userAgent = 'Browser Capabilities Project - Crossjoin Browscap/%v %m';

    /**
     * AbstractUpdaterRemote constructor.
     *
     * @param array|null $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        // add additional options
        $this->options['ProxyProtocol'] = null;
        $this->options['ProxyHost']     = null;
        $this->options['ProxyPort']     = null;
        $this->options['ProxyAuth']     = null;
        $this->options['ProxyUser']     = null;
        $this->options['ProxyPassword'] = null;
    }

    /**
     * Gets the current browscap version (time stamp)
     *
     * @return int
     */
    public function getBrowscapVersion()
    {
        return (int)strtotime($this->getRemoteData($this->getBrowscapVersionUrl()));
    }

    /**
     * Gets the URL for requesting the current browscap version (time string)
     *
     * @return string
     */
    protected function getBrowscapVersionUrl()
    {
        return $this->browscapVersionUrl;
    }

    /**
     * Gets the current browscap version number
     *
     * @return int
     */
    public function getBrowscapVersionNumber()
    {
        return (int)$this->getRemoteData($this->getBrowscapVersionNumberUrl());
    }

    /**
     * Gets the URL for requesting the current browscap version number
     *
     * @return string
     */
    protected function getBrowscapVersionNumberUrl()
    {
        return $this->browscapVersionNumberUrl;
    }

    /**
     * Gets the browscap data of the used source type
     *
     * @return string
     */
    public function getBrowscapSource()
    {
        $type = Browscap::getParser()->getSourceType();
        $url  = str_replace('%t', urlencode($type), $this->getBrowscapSourceUrl());

        return $this->getRemoteData($url);
    }

    /**
     * Gets the URL for requesting the browscap data
     *
     * @return string
     */
    protected function getBrowscapSourceUrl()
    {
        return $this->browscapSourceUrl;
    }

    /**
     * Format the user agent string to be used in the remote requests made by the
     * class during the update process
     *
     * @return string
     */
    protected function getUserAgent()
    {
        return str_replace(
            array('%v', '%m'),
            array(Browscap::VERSION, $this->getUpdateMethod()),
            $this->userAgent
        );
    }

    /**
     * Gets the exception to throw if the given HTTP status code is an error code (4xx or 5xx)
     *
     * @param int $httpCode
     * @param bool $throwException
     * @return \RuntimeException|null
     * @throws \RuntimeException
     */
    protected function getHttpErrorException($httpCode, $throwException = false)
    {
        $httpCode = (int)$httpCode;

        $result = null;
        if ($httpCode >= 400) {
            switch ($httpCode) {
                case 401:
                    $result = new \RuntimeException('HTTP client error 401: Unauthorized');
                    break;
                case 403:
                    $result = new \RuntimeException('HTTP client error 403: Forbidden');
                    break;
                case 404:
                    // wrong browscap source url
                    $result = new \RuntimeException('HTTP client error 404: Not Found');
                    break;
                case 429:
                    // rate limit has been exceeded
                    $result = new \RuntimeException('HTTP client error 429: Too many request');
                    break;
                case 500:
                    $result = new \RuntimeException('HTTP server error 500: Internal Server Error');
                    break;
                default:
                    if ($httpCode >= 500) {
                        $result = new \RuntimeException("HTTP server error $httpCode");
                    } else {
                        $result = new \RuntimeException("HTTP client error $httpCode");
                    }
            }
        } else {
            $throwException = false;
        }

        if ($throwException === true) {
            /** @var \RuntimeException $result */
            throw $result;
        } else {
            return $result;
        }
    }

    /**
     * Gets the data from a given URL (or false on failure)
     *
     * @param string $url
     * @return string|boolean
     */
    abstract protected function getRemoteData($url);
}

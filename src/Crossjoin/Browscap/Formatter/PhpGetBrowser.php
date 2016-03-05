<?php
namespace Crossjoin\Browscap\Formatter;

/**
 * PhpGetBrowser formatter class
 *
 * This formatter modifies the basic data, so that you get the same result
 * as with the PHP get_browser() function (an object, and all keys lower case).
 *
 * @note There is one difference: The wrong encoded character used in
 * "browser_name_regex" of the standard PHP get_browser() result has been
 * replaced. The regular expression itself is the same.
 * @see https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=612364
 *
 * @package Crossjoin\Browscap
 * @author Christoph Ziegenberg <christoph@ziegenberg.com>
 * @link https://github.com/crossjoin/browscap
 */
class PhpGetBrowser extends AbstractFormatter
{
    /**
     * PhpGetBrowser constructor.
     */
    public function __construct()
    {
        $this->settings = new \stdClass();
    }

    /**
     * Sets the data (done by the parser)
     *
     * @param array $settings
     */
    public function setData(array $settings)
    {
        $this->settings = new \stdClass();
        foreach ($settings as $key => $value) {
            $key = strtolower($key);
            $this->settings->$key = $value;
        }
    }

    /**
     * Gets the data (in the preferred format)
     *
     * @return \stdClass
     */
    public function getData()
    {
        return $this->settings;
    }
}

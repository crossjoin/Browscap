<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Formatter;

use Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait;

/**
 * Class Formatter
 *
 * @package Crossjoin\Browscap\Formatter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Formatter implements FormatterInterface
{
    use PropertyFilterTrait;

    const KEY_LOWER = 1;
    const KEY_UPPER = 2;

    const VALUE_TYPED = 4;
    const VALUE_UNKNOWN_TO_NULL = 8;
    const VALUE_BOOLEAN_TO_STRING = 16;
    const VALUE_REG_EXP_LOWER = 32;

    const RETURN_OBJECT = 64;
    const RETURN_ARRAY = 128;

    /**
     * @var int
     */
    protected $options;

    /**
     * Formatter constructor.
     *
     * @param int $options
     */
    public function __construct(int $options = 0)
    {
        $this->setOptions($options);
    }

    /**
     * @return int
     */
    protected function getOptions() : int
    {
        return $this->options;
    }

    /**
     * @param int $options
     */
    protected function setOptions(int $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritdoc
     *
     * @return array|\stdClass
     */
    public function format(array $browscapData)
    {
        $data = new \stdClass();

        foreach ($browscapData as $key => $value) {
            if ($this->getPropertyFilter()->isFiltered($key)) {
                continue;
            }
            $key = $this->modifyKey($key);
            $value = $this->modifyValue($key, $value);
            $data->{$key} = $value;
        }

        if (($this->getOptions() & self::RETURN_ARRAY) > 0) {
            $data = (array)$data;
        }

        return $data;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function modifyKey(string $key)
    {
        $newKey = $key;
        if (($this->getOptions() & self::KEY_LOWER) > 0) {
            $newKey = strtolower($key);
        } elseif (($this->getOptions() & self::KEY_UPPER) > 0) {
            $newKey = strtoupper($key);
        }

        return $newKey;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    protected function modifyValue(string $key, string $value)
    {
        $newValue = $value;
        if (($this->getOptions() & self::VALUE_TYPED) > 0) {
            if ($this->hasBooleanValue($key)) {
                $newValue = (strtolower($newValue) === 'true');
            } elseif ($this->hasIntegerValue($key)) {
                $newValue = (int)$newValue;
            } elseif ($this->hasFloatValue($key)) {
                $newValue = (float)$newValue;
            }
        }

        if (is_string($newValue) &&
            ($this->getOptions() & self::VALUE_UNKNOWN_TO_NULL) > 0 &&
            strtolower($newValue) === 'unknown'
        ) {
            $newValue = null;
        }

        if (($this->getOptions() & self::VALUE_BOOLEAN_TO_STRING) > 0 && $this->hasBooleanValue($key)) {
            $newValue = ($newValue === true || strtolower($newValue) === 'true');
            $newValue = ($newValue ? '1' : '');
        }

        if (strtolower($key) === 'browser_name_regex' && ($this->getOptions() & self::VALUE_REG_EXP_LOWER) > 0) {
            $newValue = strtolower($newValue);
        }

        return $newValue;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function hasBooleanValue(string $key) : bool
    {
        return in_array(strtolower($key), [
            'activexcontrols',
            'alpha',
            'backgroundsounds',
            'beta',
            'cookies',
            'crawler',
            'frames',
            'iframes',
            'tables',
            'javaapplets',
            'javascript',
            'isanonymized',
            'isfake',
            'ismobiledevice',
            'ismodified',
            'issyndicationreader',
            'istablet',
            'vbscript',
            'win16',
            'win32',
            'win64',
        ], true);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function hasIntegerValue(string $key) : bool
    {
        // Notes:
        // - "aolversion" is only contained in the default properties and never set
        // - "cssversion" is only set as integers in the browscap data, so version 2.1 should be returned as 2
        return in_array(strtolower($key), [
            'aolversion',
            'browser_bits',
            'cssversion',
            'majorver',
            'minorver',
            'platform_bits'
        ], true);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function hasFloatValue(string $key) : bool
    {
        // Notes:
        // - "platform_version" is a float in most cases, but can also be "ME"
        // - "renderingengine_version" is a float in most cases, but also a version string like "1.9.2"
        return (strtolower($key) === 'version');
    }
}

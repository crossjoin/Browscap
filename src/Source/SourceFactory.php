<?php
namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Exception\BrowscapException;
use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;

/**
 * Class SourceFactory
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class SourceFactory
{
    const DEFAULT_CLASSES = [
        '\Crossjoin\Browscap\Source\Ini\BrowscapOrg',
        '\Crossjoin\Browscap\Source\Ini\PhpSetting',
    ];

    protected static $sourceClasses = self::DEFAULT_CLASSES;

    /**
     * @return SourceInterface
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public static function getInstance()
    {
        foreach (static::$sourceClasses as $className) {
            $instance = static::getInstanceByClassName($className);
            if ($instance !== null) {
                return $instance;
            }
        }

        return new None();
    }

    /**
     * @param array $fullyQualifiedClassNames
     *
     * @throws InvalidArgumentException
     */
    public static function setSourceClasses(array $fullyQualifiedClassNames)
    {
        $rememberClasses = self::$sourceClasses;

        self::$sourceClasses = [];
        foreach ($fullyQualifiedClassNames as $className) {
            if (is_string($className)) {
                self::$sourceClasses[] = $className;
            } else {
                // Reset to previous value on error
                self::$sourceClasses = $rememberClasses;

                throw new InvalidArgumentException(
                    "A value in the class name array is of type '" . gettype($className) . "'. String expected."
                );
            }
        }
    }

    /**
     * @param string $className
     *
     * @return SourceInterface|null
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    protected static function getInstanceByClassName($className)
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($className) . "' for argument 'className'."
            );
        }

        if (class_exists($className)) {
            $interface = '\Crossjoin\Browscap\Source\SourceFactoryInterface';
            $interfaces = class_implements($className);
            if (array_key_exists(ltrim($interface, '\\'), $interfaces)) {
                try {
                    return new $className();
                } catch (BrowscapException $e) {
                    // Ignore exception, because we just return NULL on failure
                }
            } else {
                throw new UnexpectedValueException(
                    "Class '$className' has to implement the interface '$interface'.",
                    1459069587
                );
            }
        } else {
            throw new UnexpectedValueException("Class '$className' doesn't exist.", 1459069588);
        }

        return null;
    }
}

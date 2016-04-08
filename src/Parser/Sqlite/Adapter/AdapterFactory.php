<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\BrowscapException;
use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;

/**
 * Class AdapterFactory
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class AdapterFactory
{
    const DEFAULT_CLASSES = [
        '\Crossjoin\Browscap\Parser\Sqlite\Adapter\Pdo',
        '\Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3',
    ];

    protected static $adapterClasses = self::DEFAULT_CLASSES;

    /**
     * @param string $fileName
     *
     * @return AdapterInterface
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public static function getInstance($fileName)
    {
        if (!is_string($fileName)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($fileName) . "' for argument 'fileName'."
            );
        }

        foreach (static::$adapterClasses as $className) {
            $instance = static::getInstanceByClassName($className, $fileName);
            if ($instance !== null) {
                return $instance;
            }
        }

        throw new ParserConditionNotSatisfiedException(
            "No Sqlite extension found. Either 'pdo_sqlite' or 'sqlite3' extension required."
        );
    }

    /**
     * @param array $fullyQualifiedClassNames
     *
     * @throws InvalidArgumentException
     */
    public static function setAdapterClasses(array $fullyQualifiedClassNames)
    {
        $rememberClasses = self::$adapterClasses;

        self::$adapterClasses = [];
        foreach ($fullyQualifiedClassNames as $className) {
            if (is_string($className)) {
                self::$adapterClasses[] = $className;
            } else {
                // Reset to previous value on error
                self::$adapterClasses = $rememberClasses;

                throw new InvalidArgumentException(
                    "A value in the class name array is of type '" . gettype($className) . "'. String expected."
                );
            }
        }
    }

    /**
     * @param string $className
     * @param string $fileName
     *
     * @return AdapterInterface|null
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    protected static function getInstanceByClassName($className, $fileName)
    {
        if (!is_string($className)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($className) . "' for argument 'className'."
            );
        }
        if (!is_string($fileName)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($fileName) . "' for argument 'fileName'."
            );
        }

        if (class_exists($className)) {
            $interface = '\Crossjoin\Browscap\Parser\Sqlite\Adapter\AdapterFactoryInterface';
            $interfaces = class_implements($className);
            if (array_key_exists(ltrim($interface, '\\'), $interfaces)) {
                try {
                    return new $className($fileName);
                } catch (BrowscapException $e) {
                    // Ignore exception, because we just return NULL on failure
                }
            } else {
                throw new UnexpectedValueException(
                    "Class '$className' has to implement the interface '$interface'.",
                    1459000689
                );
            }
        } else {
            throw new UnexpectedValueException("Class '$className' doesn't exist.", 1459000690);
        }

        return null;
    }
}

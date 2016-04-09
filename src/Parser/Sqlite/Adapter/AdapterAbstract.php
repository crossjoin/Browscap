<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;

/**
 * Class AdapterAbstract
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
abstract class AdapterAbstract
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * PdoSqlite constructor.
     *
     * @param string $fileName
     *
     * @throws InvalidArgumentException
     */
    public function __construct($fileName)
    {
        $this->setFileName($fileName);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @throws InvalidArgumentException
     */
    protected function setFileName($fileName)
    {
        if (!is_string($fileName)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($fileName) . "' for argument 'fileName'."
            );
        }

        $this->fileName = $fileName;
    }
}

<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

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
     */
    public function __construct(string $fileName)
    {
        $this->setFileName($fileName);
    }

    /**
     * @return string
     */
    protected function getFileName() : string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    protected function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }
}

<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Exception\SourceUnavailableException;

/**
 * Class FileAbstract
 *
 * @package Crossjoin\Browscap\Source
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
abstract class FileAbstract
{
    /**
     * @var string
     */
    protected $file;

    /**
     * File constructor.
     *
     * @param string $file
     *
     * @throws SourceUnavailableException
     */
    public function __construct(string $file)
    {
        $this->setFilePath($file);
    }

    /**
     * @return string
     */
    protected function getFilePath() : string
    {
        return $this->file;
    }

    /**
     * @param string $file
     *
     * @throws SourceUnavailableException
     */
    protected function setFilePath(string $file)
    {
        if (!$this->isFileReadable($file)) {
            if (!file_exists($file)) {
                throw new SourceUnavailableException("File '$file' does not exist.", 1458977223);
            } else {
                throw new SourceUnavailableException("File '$file' exists but is not readable.", 1458977224);
            }
        }
        $this->file = $file;
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    protected function isFileReadable(string $file) : bool
    {
        return is_readable($file);
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore All covered, but analysis does't work with Generators.
     *
     * @throws SourceUnavailableException
     */
    public function getContent() : \Generator
    {
        $file = $this->getFilePath();

        $handle = @fopen($file, 'r');
        if ($handle !== false) {
            while (!feof($handle)) {
                yield fread($handle, 4096);
            }
            fclose($handle);
        } else {
            throw new SourceUnavailableException("Could not open file '$file' for reading.", 1458977225);
        }
    }
}

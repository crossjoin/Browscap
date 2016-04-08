<?php
namespace Crossjoin\Browscap\Source;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
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
     * @throws InvalidArgumentException
     * @throws SourceUnavailableException
     */
    public function __construct($file)
    {
        if (!is_string($file)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($file) . "' for argument 'file'."
            );
        }

        $this->setFilePath($file);
    }

    /**
     * @return string
     */
    protected function getFilePath()
    {
        return $this->file;
    }

    /**
     * @param string $file
     *
     * @throws InvalidArgumentException
     * @throws SourceUnavailableException
     */
    protected function setFilePath($file)
    {
        if (!is_string($file)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($file) . "' for argument 'file'."
            );
        }

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
     * @throws InvalidArgumentException
     */
    protected function isFileReadable($file)
    {
        if (!is_string($file)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($file) . "' for argument 'file'."
            );
        }

        return is_readable($file);
    }

    /**
     * @inheritdoc
     *
     * @codeCoverageIgnore All covered, but analysis does't work with Generators.
     *
     * @throws SourceUnavailableException
     */
    public function getContent()
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

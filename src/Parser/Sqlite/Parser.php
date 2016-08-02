<?php
namespace Crossjoin\Browscap\Parser\Sqlite;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Parser\ParserInterface;
use Crossjoin\Browscap\Parser\ReaderInterface;
use Crossjoin\Browscap\Parser\WriterInterface;
use Crossjoin\Browscap\PropertyFilter\PropertyFilterInterface;
use Crossjoin\Browscap\PropertyFilter\PropertyFilterTrait;
use Crossjoin\Browscap\Source\SourceFactory;
use Crossjoin\Browscap\Source\SourceInterface;

/**
 * Class Parser
 *
 * @package Crossjoin\Browscap\Parser\Sqlite
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Parser implements ParserInterface
{
    use PropertyFilterTrait;

    const SUB_DIRECTORY = 'browscap' . DIRECTORY_SEPARATOR . 'sqlite';
    const LINK_FILENAME = 'browscap.link';

    /**
     * Version that is saved in the generated data. Has to be increased
     * to trigger the invalidation of the data.
     */
    const VERSION = '1.0.0';

    /**
     * @var SourceInterface
     */
    protected $source;

    /**
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var string
     */
    protected $dataDirectory;

    /**
     * Parser constructor.
     *
     * @param string|null $dataDirectory
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     */
    public function __construct($dataDirectory = null)
    {
        if (!is_string($dataDirectory) && $dataDirectory !== null) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($dataDirectory) . "' for argument 'dataDirectory'."
            );
        }

        if ($dataDirectory !== null) {
            $this->setDataDirectory($dataDirectory);
        }
    }

    /**
     * @return SourceInterface
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function getSource()
    {
        if ($this->source === null) {
            $this->setSource(SourceFactory::getInstance());
        }

        return $this->source;
    }

    /**
     * @param SourceInterface $source
     *
     * @throws InvalidArgumentException
     */
    public function setSource(SourceInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     */
    public function getDataDirectory()
    {
        if ($this->dataDirectory === null) {
            $this->setDataDirectory(sys_get_temp_dir());
        }

        return $this->dataDirectory;
    }

    /**
     * @param string $directory
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     */
    public function setDataDirectory($directory)
    {
        if (!is_string($directory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($directory) . "' for argument 'directory'."
            );
        }

        $directory = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory), DIRECTORY_SEPARATOR);

        // Check main data directory
        $this->checkDirectory($directory, false);

        // Check/create sub directory
        $directory = $directory . DIRECTORY_SEPARATOR . self::SUB_DIRECTORY;
        $this->checkDirectory($directory, true);

        $this->dataDirectory = $directory;
    }

    /**
     * @param string $directory
     * @param bool $create
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     */
    protected function checkDirectory($directory, $create = false)
    {
        if (!file_exists($directory) &&
            ($create === false || (!@mkdir($directory, 0777, true) && !is_dir($directory)))
        ) {
            throw new ParserConfigurationException(
                "Directory '$directory' does not exist and could not be created.",
                1458974127
            );
        }
    }

    /**
     * @param string $directory
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function isDirectoryReadable($directory)
    {
        if (!is_string($directory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($directory) . "' for argument 'directory'."
            );
        }

        return is_readable($directory);
    }

    /**
     * @param string $directory
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function isDirectoryWritable($directory)
    {
        if (!is_string($directory)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($directory) . "' for argument 'directory'."
            );
        }

        return is_writable($directory);
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function setPropertyFilter(PropertyFilterInterface $filter)
    {
        $this->propertyFilter = $filter;

        $this->getReader()->setPropertyFilter($filter);
        $this->getWriter()->setPropertyFilter($filter);

        $dataVersionHash = $this->getDataVersionHash();
        $this->getReader()->setDataVersionHash($dataVersionHash);
        $this->getWriter()->setDataVersionHash($dataVersionHash);
    }

    /**
     * Generates a hash from relevant information that influence the
     * validity of
     *
     * @return string
     */
    protected function getDataVersionHash()
    {
        $filter = $this->getPropertyFilter();
        $properties = $filter->getProperties();
        sort($properties);
        
        return sha1(
            static::class . '|' .
            static::VERSION . '|' .
            get_class($filter) . '|' .
            implode(',', $properties)
        );
    }

    /**
     * @inheritdoc
     *
     * @return Reader
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     */
    public function getReader($reInitiate = false)
    {
        if ($reInitiate === true || $this->reader === null) {
            $directory = $this->getDataDirectory();

            if (!$this->isDirectoryReadable($directory)) {
                throw new ParserConfigurationException("Directory '$directory' is not readable.", 1458974128);
            }

            $this->reader = new Reader($directory);
            $this->reader->setPropertyFilter($this->getPropertyFilter());
            $this->reader->setDataVersionHash($this->getDataVersionHash());
        }

        return $this->reader;
    }

    /**
     * @inheritdoc
     *
     * @return Writer
     *
     * @throws ParserConditionNotSatisfiedException
     * @throws ParserConfigurationException
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function getWriter()
    {
        if ($this->writer === null) {
            $directory = $this->getDataDirectory();

            if (!$this->isDirectoryWritable($directory)) {
                throw new ParserConfigurationException("Directory '$directory' is not writable.", 1458974129);
            }

            $this->writer = new Writer($directory, $this->getSource());
            $this->writer->setPropertyFilter($this->getPropertyFilter());
            $this->writer->setDataVersionHash($this->getDataVersionHash());
        }

        return $this->writer;
    }
}

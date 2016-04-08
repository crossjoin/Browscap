<?php
namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Source\FileAbstract;
use Crossjoin\Browscap\Source\SourceInterface;
use Crossjoin\Browscap\Type;

/**
 * Class File
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class File extends FileAbstract implements SourceInterface
{
    use ParseHeaderSectionTrait;
    use DataSetsFromContentTrait { getContent as private getContentIgnore; }

    const HEADER_PATTERN = 'GJK_Browscap_Version';

    /**
     * @var int
     */
    protected $releaseTime;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var int
     */
    protected $type;

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function getReleaseTime()
    {
        if ($this->releaseTime === null) {
            $this->extractHeaderData();
        }

        return $this->releaseTime;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function getVersion()
    {
        if ($this->version === null) {
            $this->extractHeaderData();
        }

        return $this->version;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function getType()
    {
        if ($this->type === null) {
            $this->extractHeaderData();
        }

        return $this->type;
    }

    /**
     * Extracts the header data from the file. This is typically the first block in the file
     * ofter some lines of comments, with a special section name (defined in self::HEADER_PATTERN).
     *
     * @throws InvalidArgumentException
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    protected function extractHeaderData()
    {
        // Set defaults
        $this->version = 0;
        $this->type = Type::UNKNOWN;
        $this->releaseTime = 0;

        // Parse the beginning of the file
        foreach ($this->getDataSets() as $dataSet) {
            if ($dataSet->getPattern() === self::HEADER_PATTERN) {
                $result = $this->parseHeaderDataSet($dataSet);
                $this->version = $result['version'];
                $this->releaseTime = $result['released'];
                $this->type = $result['type'];
                break;
            }
        }
    }

    /**
     * Needs to be defined again, because the trait used here replaces
     * the method of the parent class.
     *
     * @inheritdoc
     */
    public function getContent()
    {
        return parent::getContent();
    }
}

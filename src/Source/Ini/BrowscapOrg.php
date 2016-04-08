<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Exception\UnexpectedValueException;
use Crossjoin\Browscap\Source\DownloadAbstract;
use Crossjoin\Browscap\Source\SourceFactoryInterface;
use Crossjoin\Browscap\Source\SourceInterface;
use Crossjoin\Browscap\Type;

/**
 * Class BrowscapOrg
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class BrowscapOrg extends DownloadAbstract implements SourceInterface, SourceFactoryInterface
{
    use DataSetsFromContentTrait { getContent as private getContentIgnore; }

    const BASE_URI = 'http://browscap.org';

    /**
     * @var int
     */
    protected $type;

    /**
     * BrowscapOrg constructor.
     *
     * @param int $type
     * @param array $clientOptions
     *
     * @throws SourceConditionNotSatisfiedException
     * @throws UnexpectedValueException
     */
    public function __construct(int $type = Type::STANDARD, array $clientOptions = [])
    {
        $this->setType($type);
        parent::__construct($this->getSourceUri(), $clientOptions);
    }

    /**
     * @inheritdoc
     *
     * @throws SourceUnavailableException
     * @throws \RuntimeException
     */
    public function getReleaseTime() : int
    {
        $stream = $this->loadContent(self::BASE_URI . '/version');

        if (!$stream->isReadable()) {
            throw new SourceUnavailableException('Source stream is not readable.', 1459162265);
        }

        return max(0, strtotime($stream->getContents()));
    }

    /**
     * @inheritdoc
     *
     * @throws SourceUnavailableException
     * @throws \RuntimeException
     */
    public function getVersion() : int
    {
        $stream = $this->loadContent(self::BASE_URI . '/version-number');

        if (!$stream->isReadable()) {
            throw new SourceUnavailableException('Source stream is not readable.', 1459162266);
        }

        return (int)$stream->getContents();
    }

    /**
     * @param int $type
     *
     * @throws UnexpectedValueException
     */
    protected function setType(int $type)
    {
        switch ($type) {
            case Type::LITE:
                $sourceType = 'Lite_PHP_BrowscapINI';
                break;
            case Type::FULL:
                $sourceType = 'Full_PHP_BrowscapINI';
                break;
            case Type::STANDARD:
                $sourceType = 'PHP_BrowscapINI';
                break;
            default:
                throw new UnexpectedValueException("Invalid value '$type' for argument 'type'.");
        }

        $this->type = $type;
        $this->setSourceUri(self::BASE_URI . '/stream?q=' . $sourceType);
    }

    /**
     * @inheritdoc
     */
    public function getType() : int
    {
        return $this->type;
    }

    /**
     * Needs to be defined again, because the trait used here replaces
     * the method of the parent class.
     *
     * @inheritdoc
     */
    public function getContent() : \Generator
    {
        return parent::getContent();
    }
}

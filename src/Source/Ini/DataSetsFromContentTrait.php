<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Source\DataSet;

/**
 * Trait DataSetsFromContentTrait
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait DataSetsFromContentTrait
{
    /**
     * To be overwritten and filled with content by the class using this trait!
     *
     * @return \Generator
     */
    public function getContent() : \Generator
    {
        yield '';
    }

    /**
     * @inheritdoc
     *
     * @throws ParserRuntimeException
     * @throws SourceUnavailableException
     */
    public function getDataSets() : \Iterator
    {
        $unprocessedBytes = '';
        foreach ($this->getContent() as $bytes) {
            $combinedBytes = $unprocessedBytes . $bytes;
            $patternStart = strpos($combinedBytes, '[');
            if ($patternStart !== false) {
                while (($nextPatternStart = strpos($combinedBytes, '[', $patternStart + 1)) !== false) {
                    $dataSet = substr($combinedBytes, $patternStart, $nextPatternStart - $patternStart);
                    yield $this->getDataSetFromString($dataSet);
                    $patternStart = $nextPatternStart;
                    $unprocessedBytes = substr($combinedBytes, $nextPatternStart);
                }
            } else {
                $unprocessedBytes = $combinedBytes;
            }
        }
        if (trim($unprocessedBytes) !== '') {
            yield $this->getDataSetFromString($unprocessedBytes);
        }
    }

    /**
     * @param string $data
     *
     * @return DataSet
     * @throws ParserRuntimeException
     */
    protected function getDataSetFromString(string $data) : DataSet
    {
        if (strpos($data, "\n") === false) {
            throw new ParserRuntimeException('The data could not be parsed (no pattern found).', 1459589758);
        }

        // Prepare the data from the data set
        list($pattern, $properties) = explode("\n", $data, 2);
        $pattern = substr($pattern, 1, -1);

        $properties = @parse_ini_string($properties);
        if ($properties === false) {
            throw new ParserRuntimeException(
                "The data could not be parsed (invalid properties for pattern '$pattern').",
                1459589759
            );
        }

        $dataSet = new DataSet($pattern);
        $dataSet->setProperties($properties);

        return $dataSet;
    }
}

<?php
namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Source\DataSet;
use Crossjoin\Browscap\Type;

/**
 * Trait ParseHeaderSectionTrait
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
trait ParseHeaderSectionTrait
{
    /**
     * @param DataSet $dataSet
     *
     * @return array
     */
    protected function parseHeaderDataSet(DataSet $dataSet)
    {
        $result = ['version' => 0, 'released' => 0, 'type' => Type::UNKNOWN];

        $properties = $dataSet->getProperties();

        if ($properties !== false) {
            if (array_key_exists('Version', $properties) && ctype_digit($properties['Version'])) {
                $result['version'] = (int)$properties['Version'];
            }
            if (array_key_exists('Released', $properties)) {
                $result['released'] = (int)strtotime($properties['Released']);
            }
            if (array_key_exists('Type', $properties)) {
                switch (strtolower($properties['Type'])) {
                    case 'full':
                        $result['type'] = Type::FULL;
                        break;
                    case 'lite':
                        $result['type'] = Type::LITE;
                        break;
                    case '':
                        $result['type'] = Type::STANDARD;
                        break;
                }
            }
        }

        return $result;
    }
}

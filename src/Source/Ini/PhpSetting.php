<?php
namespace Crossjoin\Browscap\Source\Ini;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
use Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\SourceUnavailableException;
use Crossjoin\Browscap\Source\SourceFactoryInterface;

/**
 * Class PhpSetting
 *
 * @package Crossjoin\Browscap\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class PhpSetting extends File implements SourceFactoryInterface
{
    /**
     * PhpBrowscapIni constructor.
     *
     * @throws InvalidArgumentException
     * @throws SourceConditionNotSatisfiedException
     * @throws SourceUnavailableException
     */
    public function __construct()
    {
        $iniPath = $this->getIniPath();
        if ($iniPath !== '') {
            parent::__construct($iniPath);
        } else {
            throw new SourceConditionNotSatisfiedException(
                "Browscap file not configured in php configuration (see 'browscap' directive).",
                1458977060
            );
        }
    }

    /**
     * @return string
     */
    protected function getIniPath()
    {
        return (string)ini_get('browscap');
    }
}

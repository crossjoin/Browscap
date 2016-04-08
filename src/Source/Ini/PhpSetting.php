<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Source\Ini;

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
    protected function getIniPath() : string
    {
        return (string)ini_get('browscap');
    }
}

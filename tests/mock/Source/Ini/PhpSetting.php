<?php
namespace Crossjoin\Browscap\Tests\Mock\Source\Ini;

/**
 * Class PhpSetting
 *
 * @package Crossjoin\Browscap\Tests\Mock\Source\Ini
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class PhpSetting extends \Crossjoin\Browscap\Source\Ini\PhpSetting
{
    /**
     * @var string
     */
    protected $browscapSetting;

    public function __construct($browscapSetting)
    {
        $this->browscapSetting = $browscapSetting;
        parent::__construct();
    }

    /**
     * @return string
     */
    protected function getIniPath()
    {
        return $this->browscapSetting;
    }
}

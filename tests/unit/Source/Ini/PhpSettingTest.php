<?php
namespace Crossjoin\Browscap\Tests\Unit\Source\Ini;

use Crossjoin\Browscap\Tests\Mock\Source\Ini\PhpSetting;

/**
 * Class PhpSettingTest
 *
 * @package Crossjoin\Browscap\Test\Source\Init
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Source\Ini\PhpSetting
 */
class PhpSettingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getIniPath
     */
    public function testIniPathValid()
    {
        $iniPath = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'source' . DIRECTORY_SEPARATOR . 'lite_php_browscap.ini';
        $source = new PhpSetting($iniPath);

        static::assertInstanceOf('\Crossjoin\Browscap\Source\Ini\PhpSetting', $source);
    }

    /**
     * @expectedException \Crossjoin\Browscap\Exception\SourceConditionNotSatisfiedException
     *
     * @covers ::__construct
     * @covers ::getIniPath
     */
    public function testIniPathEmpty()
    {
        new PhpSetting('');
    }

    /**
     * @covers ::getIniPath
     */
    public function testGetIniPath()
    {
        $mock = $this->getMockBuilder('\Crossjoin\Browscap\Source\Ini\PhpSetting')
            ->disableOriginalConstructor()
            ->getMock();

        $class = new \ReflectionClass('\Crossjoin\Browscap\Source\Ini\PhpSetting');
        $method = $class->getMethod('getIniPath');
        $method->setAccessible(true);

        static::assertInternalType('string', $method->invoke($mock));
    }
}

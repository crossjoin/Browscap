<?php
namespace Crossjoin\Browscap\Tests\Unit\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;
use Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3;

/**
 * Class Sqlite3PreparedStatementTest
 *
 * @package Crossjoin\Browscap\Test\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 *
 * @coversDefaultClass \Crossjoin\Browscap\Parser\Sqlite\Adapter\Sqlite3PreparedStatement
 */
class Sqlite3PreparedStatementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $tempFile;

    /**
     * @var Sqlite3
     */
    protected $adapterInstance;

    public function setUp()
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'cb-');
        $this->adapterInstance = new Sqlite3($this->tempFile);
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->adapterInstance);
        @unlink($this->tempFile);
    }

    /**
     * @covers ::__construct
     * @covers ::setStatement
     * @covers ::getStatement
     * @covers ::execute
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function testValidPrepare()
    {
        $this->adapterInstance->exec('CREATE TABLE IF NOT EXISTS dummy (foo INTEGER)');
        $this->adapterInstance->exec('INSERT INTO dummy VALUES (123),(234),(345)');
        $preparedStatement = $this->adapterInstance->prepare('SELECT COUNT(*) AS cnt FROM dummy WHERE foo >= :value');
        $result = $preparedStatement->execute(['value' => 234]);
        $count = 0;
        if (array_key_exists(0, $result) && array_key_exists('cnt', $result[0])) {
            $count = (int)$result[0]['cnt'];
        }

        static::assertSame(2, $count);
    }
}

<?php
declare(strict_types=1);

namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\ParserConditionNotSatisfiedException;
use Crossjoin\Browscap\Exception\ParserConfigurationException;
use Crossjoin\Browscap\Exception\ParserRuntimeException;

/**
 * Class Sqlite3
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Sqlite3 extends AdapterAbstract implements AdapterInterface, AdapterFactoryInterface
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * Pdo constructor.
     *
     * @inheritdoc
     *
     * @throws ParserConditionNotSatisfiedException
     */
    public function __construct(string $fileName)
    {
        if (!$this->checkConditions()) {
            throw new ParserConditionNotSatisfiedException('Sqlite3 extension missing.');
        }

        parent::__construct($fileName);
    }

    /**
     * @return bool
     */
    protected function checkConditions() : bool
    {
        return class_exists('\SQLite3');
    }

    /**
     * @return \SQLite3
     * @throws ParserConfigurationException
     */
    protected function getConnection() : \SQLite3
    {
        if ($this->connection === null) {
            try {
                $this->connection = new \SQLite3($this->getFileName());
            } catch (\Exception $e) {
                throw new ParserConfigurationException(
                    "Could not connect to database '" . $this->getFileName() . "'.", 0, $e
                );
            }
        }

        return $this->connection;
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function beginTransaction() : bool
    {
        $result = @$this->getConnection()->exec('BEGIN TRANSACTION');

        if ($result === false) {
            throw new ParserRuntimeException('Transaction could not be started.', 0);
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function commitTransaction() : bool
    {
        $result = @$this->getConnection()->exec('COMMIT TRANSACTION');

        if ($result === false) {
            throw new ParserRuntimeException('Transaction could not be committed.', 0);
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function query(string $statement) : array
    {
        $result = @$this->getConnection()->query($statement);

        if ($result === false) {
            throw new ParserRuntimeException('Statement could not be executed.', 0);
        }

        $results = [];
        while ($row = $result->fetchArray(\SQLITE3_ASSOC)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function prepare(string $statement) : PreparedStatementInterface
    {
        $preparedStatement = @$this->getConnection()->prepare($statement);

        if ($preparedStatement === false) {
            throw new ParserRuntimeException('Statement could not be prepared.', 0);
        }

        return new Sqlite3PreparedStatement($preparedStatement);
    }

    /**
     * @inheritdoc
     *
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function exec(string $statement) : bool
    {
        $result = @$this->getConnection()->exec($statement);

        if ($result === false) {
            throw new ParserRuntimeException('Statement could not be executed.', 0);
        }

        return $result;
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}

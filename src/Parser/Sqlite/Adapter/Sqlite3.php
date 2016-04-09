<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

use Crossjoin\Browscap\Exception\InvalidArgumentException;
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
     * @var \SQLite3
     */
    protected $connection;

    /**
     * Sqlite3 constructor.
     *
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConditionNotSatisfiedException
     */
    public function __construct($fileName)
    {
        if (!$this->checkConditions()) {
            throw new ParserConditionNotSatisfiedException('Sqlite3 extension missing.');
        }

        parent::__construct($fileName);
    }

    /**
     * @return bool
     */
    protected function checkConditions()
    {
        return class_exists('\SQLite3');
    }

    /**
     * @return \SQLite3
     * @throws ParserConfigurationException
     */
    protected function getConnection()
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
    public function beginTransaction()
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
    public function commitTransaction()
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
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function query($statement)
    {
        if (!is_string($statement)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($statement) . "' for argument 'statement'."
            );
        }

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
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function prepare($statement)
    {
        if (!is_string($statement)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($statement) . "' for argument 'statement'."
            );
        }
        
        $preparedStatement = @$this->getConnection()->prepare($statement);

        if ($preparedStatement === false) {
            throw new ParserRuntimeException('Statement could not be prepared.', 0);
        }

        return new Sqlite3PreparedStatement($preparedStatement);
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws ParserConfigurationException
     * @throws ParserRuntimeException
     */
    public function exec($statement)
    {
        if (!is_string($statement)) {
            throw new InvalidArgumentException(
                "Invalid type '" . gettype($statement) . "' for argument 'statement'."
            );
        }
        
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

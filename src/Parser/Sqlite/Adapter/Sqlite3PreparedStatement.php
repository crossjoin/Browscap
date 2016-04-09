<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

/**
 * Class Sqlite3PreparedStatement
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class Sqlite3PreparedStatement implements PreparedStatementInterface
{
    /**
     * @var \SQLite3Stmt
     */
    protected $statement;

    /**
     * Sqlite3PreparedStatement constructor.
     *
     * @param \SQLite3Stmt $statement
     */
    public function __construct(\SQLite3Stmt $statement)
    {
        $this->setStatement($statement);
    }

    /**
     * @return \SQLite3Stmt
     */
    protected function getStatement()
    {
        return $this->statement;
    }

    /**
     * @param \SQLite3Stmt $statement
     */
    protected function setStatement(\SQLite3Stmt $statement)
    {
        $this->statement = $statement;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function execute(array $params = [])
    {
        foreach ($params as $paramName => $paramValue) {
            $type = is_int($paramValue) ? \SQLITE3_INTEGER : \SQLITE3_TEXT;
            $this->getStatement()->bindValue($paramName, $paramValue, $type);
        }
        $result = $this->getStatement()->execute();

        $results = [];
        while ($row = $result->fetchArray(\SQLITE3_ASSOC)) {
            $results[] = $row;
        }

        return $results;
    }
}

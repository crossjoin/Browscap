<?php
namespace Crossjoin\Browscap\Parser\Sqlite\Adapter;

/**
 * Class PdoPreparedStatement
 *
 * @package Crossjoin\Browscap\Parser\Sqlite\Adapter
 * @author Christoph Ziegenberg <ziegenberg@crossjoin.com>
 * @link https://github.com/crossjoin/browscap
 */
class PdoPreparedStatement implements PreparedStatementInterface
{
    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * PdoPreparedStatement constructor.
     *
     * @param \PDOStatement $statement
     */
    public function __construct(\PDOStatement $statement)
    {
        $this->setStatement($statement);
    }

    /**
     * @return \PDOStatement
     */
    protected function getStatement()
    {
        return $this->statement;
    }

    /**
     * @param \PDOStatement $statement
     */
    protected function setStatement(\PDOStatement $statement)
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
            $type = is_int($paramValue) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $this->getStatement()->bindValue($paramName, $paramValue, $type);
        }
        $this->getStatement()->execute();

        return $this->getStatement()->fetchAll(\PDO::FETCH_ASSOC);
    }
}

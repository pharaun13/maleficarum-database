<?php
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Mssql;

/**
 * Microsoft SQL Server Connection
 *
 * It's using UTF-8 charset by default.
 *
 * Some workaround had to be implemented to handle SQL Server limitations.
 *
 * For Known Limitations (at least some of the most frequent) see the consts `KNOWN_LIMITATION_*`.
 *
 * @link https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server
 * @link https://docs.microsoft.com/en-us/sql/sql-server/maximum-capacity-specifications-for-sql-server
 * @link http://cgit.drupalcode.org/sqlsrv/tree/sqlsrv/database.inc?h=7.x-2.x
 */
class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ AbstractConnection methods START --------------------------- */

    /**
     * How many params can be bound using \PDOStatement::bindValue
     * It's set to 2000 to leave some "space" for some rare cases.
     * @see KNOWN_LIMITATION_2100
     */
    const STATEMENT_PARAMS_LIMIT = 2000;

    /**
     * @link https://docs.microsoft.com/en-us/sql/sql-server/maximum-capacity-specifications-for-sql-server
     */
    const KNOWN_LIMITATION_2100 = 'KNOWN_LIMITATION_2100';

    /**
     * You can not put enormous amount of values in `IN` clause.
     * See "Remarks" in manual linked below
     * @link https://docs.microsoft.com/en-us/sql/t-sql/language-elements/in-transact-sql
     */
    const KNOWN_LIMITATION_8623 = 'KNOWN_LIMITATION_8623';

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::connect()
     */
    public function connect(): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $connection = parent::connect();

        $pdo = $this->connection;
        $pdo->exec('SET ANSI_WARNINGS ON');
        $pdo->exec('SET ANSI_PADDING ON');
        $pdo->exec('SET ANSI_NULLS ON');
        $pdo->exec('SET QUOTED_IDENTIFIER ON');
        $pdo->exec('SET CONCAT_NULL_YIELDS_NULL ON');
        // Don't return all columns as strings
        // @see https://docs.microsoft.com/en-us/sql/connect/php/constants-microsoft-drivers-for-php-for-sql-server
        $pdo->setAttribute(\PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);

        return $connection;
    }

    /* ------------------------------------ AbstractConnection methods END ----------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Sets proper driver name
     */
    public function __construct()
    {
        parent::__construct('sqlsrv', self::STATEMENT_PARAMS_LIMIT);
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams(): array {
        return [
            $this->getDriverName() . ':Server=' . $this->getHost() . ',' . $this->getPort() . ';Database=' . $this->getDbname(),
            $this->getUsername(),
            $this->getPassword()
        ];
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::lockTable()
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        throw new \RuntimeException('Not implemented yet.');
    }

    /**
     * To handle MS SQL limit described in getDriverOptions even better we put all integer params directly into query
     *
     * @inheritdoc
     * @see \Maleficarum\Database\Shard\Connection\Mssql\Connection::KNOWN_LIMITATION_8623
     */
    public function prepareStatement(string $query, array $queryParams): \PDOStatement
    {
        $optimizedQuery = $query;
        $optimizedQueryParams = $queryParams;
        if (count($queryParams) > self::STATEMENT_PARAMS_LIMIT) {
            /**
             * If there's more than 2100 params we try to put all integers directly into query to reduce the number
             * of parameters that need to be bound using \PDOStatement::bindValue
             *
             * @see \Maleficarum\Database\Shard\Connection\Mssql\Connection::KNOWN_LIMITATION_8623
             */
            $optimizedQueryParams = [];
            $paramNames = array_keys($queryParams);
            // sorting and reversing so we don't replace for example 'par11' when replacing 'par1'
            natsort($paramNames);
            $paramNames = array_reverse($paramNames);
            // check all params to find and hardcoded integers
            foreach ($paramNames as $paramName) {
                $value = $queryParams[$paramName];
                if (is_int($value)) {
                    // is an integer so put directly into query
                    $optimizedQuery = str_replace($paramName, $value, $optimizedQuery);
                } else {
                    // keep that param to be bound
                    $optimizedQueryParams[$paramName] = $value;
                }
            }
        }

        return parent::prepareStatement($optimizedQuery, $optimizedQueryParams);
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

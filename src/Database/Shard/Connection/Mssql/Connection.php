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
 * @link https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server
 * @link https://docs.microsoft.com/en-us/sql/sql-server/maximum-capacity-specifications-for-sql-server
 * @link http://cgit.drupalcode.org/sqlsrv/tree/sqlsrv/database.inc?h=7.x-2.x
 */
class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ AbstractConnection methods START --------------------------- */

    /**
     * How many params can be bound using \PDOStatement::bindValue
     *
     * @link https://docs.microsoft.com/en-us/sql/sql-server/maximum-capacity-specifications-for-sql-server
     */
    const PDO_PARAMS_LIMIT = 2100;

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
        parent::__construct('sqlsrv');
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
     */
    public function prepareStatement(string $query, array $queryParams): \PDOStatement
    {
        $optimizedQuery = $query;
        $optimizedQueryParams = $queryParams;
        if (count($queryParams) > self::PDO_PARAMS_LIMIT) {
            /**
             * If there's more than 2100 params we try to put all integers directly into query to reduce the number
             * of parameters that need to be bound using \PDOStatement::bindValue
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

    /**
     * @inheritdoc
     */
    protected function getDriverOptions(string $query, array $queryParams = []): array
    {
        /**
         * Since MS SQL allows up to 2100 parameters being bound using \PDOStatement::bindValue
         * we enable prepared statements emulation to disable that limit.
         * This could decrease security level regarding SQL injection so we do that only if all parameters that
         * are about to be set are integers.
         */
        $driverOptions = parent::getDriverOptions($query, $queryParams);
        if (count($queryParams) > self::PDO_PARAMS_LIMIT) {
            $nonIntValues = array_filter(
                $queryParams,
                function ($element) {
                    return !is_int($element);
                }
            );
            if (count($nonIntValues) === 0) {
                // Making sure all params are integers so it's safe to emulate prepares
                $driverOptions[\PDO::ATTR_EMULATE_PREPARES] = true;
            }
        }

        return $driverOptions;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

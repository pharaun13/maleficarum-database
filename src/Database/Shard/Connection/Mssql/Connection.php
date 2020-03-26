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
     *
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
     *
     * @link https://docs.microsoft.com/en-us/sql/t-sql/language-elements/in-transact-sql
     */
    const KNOWN_LIMITATION_8623 = 'KNOWN_LIMITATION_8623';

    /**
     * Default query timeout
     */
    const QUERY_TIMEOUT_IN_SEC = 60;

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

        // Set query timeout
        $pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT, self::QUERY_TIMEOUT_IN_SEC);

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
    public function __construct() {
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
        throw new \Maleficarum\Database\Exception\Exception('Not implemented yet.');
    }

    protected function getConnectionErrorCodes(): array
    {
        return [
            '08001',
            '08S01',
            '08S02',
            'IMC06'
        ];
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

<?php
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Mssql;

/**
 * Microsoft SQL Server Connection
 *
 * It's using UTF-8 charset by default.
 *
 * @see https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server
 */
class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ AbstractConnection methods START --------------------------- */

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
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // Don't return all columns as strings
        // @see https://docs.microsoft.com/en-us/sql/connect/php/constants-microsoft-drivers-for-php-for-sql-server
        $pdo->setAttribute(\PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);

        return $connection;
    }

    /* ------------------------------------ AbstractConnection methods END ----------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams(): array {
        return ['sqlsrv:Server=' . $this->getHost() . ',' . $this->getPort() . ';Database=' . $this->getDbname(), $this->getUsername(), $this->getPassword()];
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::lockTable()
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        throw new \RuntimeException('Not implemented yet.');

        return $this;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

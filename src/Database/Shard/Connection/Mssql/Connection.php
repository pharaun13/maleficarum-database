<?php
/**
 * This abstract class defines functionality common to all database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Mssql;

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

        return $connection;
    }

    /* ------------------------------------ AbstractConnection methods END ----------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams(): array {
        return ['dblib:host=' . $this->getHost() . ':' . $this->getPort() . ';dbname=' . $this->getDbname() . ';charset=' . $this->getCharset(), $this->getUsername(), $this->getPassword()];
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

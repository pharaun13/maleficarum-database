<?php

/**
 * This abstract class defines functionality common to all database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Mssql;

class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams() : array {
        return ['dblib:host=' . $this->getHost() . ':' . $this->getPort() . ';dbname=' . $this->getDbname() . ';charset=' . $this->getCharset(), $this->getUsername(), $this->getPassword()];
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::lockTable()
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE') : \Maleficarum\Database\Shard\Connection\AbstractConnection {
        throw new \RuntimeException('Not implemented yet.');

        return $this;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

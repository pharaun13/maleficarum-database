<?php
/**
 * This abstract class defines functionality common to all database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Pgsql;

class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams(): array {
        return ['pgsql:host=' . $this->getHost() . ';port=' . $this->getPort() . ';dbname=' . $this->getDbname() . ';user=' . $this->getUsername() . ';password=' . $this->getPassword()];
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::lockTable()
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        if (is_null($this->connection)) {
            throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::lockTable()', static::class));
        }

        if (!$this->inTransaction()) {
            throw new \RuntimeException(sprintf('No active transaction - cannot lock a table outside of a transaction scope. \%s::lockTable()', static::class));
        }

        $this->query('LOCK "' . $table . '" IN ' . $mode . ' MODE');

        return $this;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

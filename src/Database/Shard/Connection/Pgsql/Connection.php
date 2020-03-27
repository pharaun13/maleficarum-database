<?php
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection\Pgsql;

/**
 * PostgreSQL Server Connection
 *
 * It's using UTF-8 charset by default.
 */
class Connection extends \Maleficarum\Database\Shard\Connection\AbstractConnection {
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     */
    public function __construct() {
        parent::__construct('pgsql');
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::getConnectionParams()
     */
    protected function getConnectionParams(): array {
        return [
            $this->getDriverName() . ':host=' . $this->getHost() . ';port=' . $this->getPort() . ';dbname=' . $this->getDbname(),
            $this->getUsername(),
            $this->getPassword()
        ];
    }

    /**
     * @see \Maleficarum\Database\Shard\Connection\AbstractConnection::lockTable()
     */
    public function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        if (is_null($this->connection)) {
            throw new \Maleficarum\Database\Exception\Exception(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::lockTable()', static::class));
        }

        if (!$this->inTransaction()) {
            throw new \Maleficarum\Database\Exception\Exception(sprintf('No active transaction - cannot lock a table outside of a transaction scope. \%s::lockTable()', static::class));
        }

        $this->query('LOCK "' . $table . '" IN ' . $mode . ' MODE');

        return $this;
    }

    protected function getConnectionErrorCodes(): array
    {
        return [];
    }
    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

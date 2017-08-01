<?php
/**
 * This abstract class defines functionality common to all database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection;

abstract class AbstractConnection {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal storage for the PDO connection.
     *
     * @var \PDO
     */
    protected $connection = null;

    /**
     * Internal storage for the connections host.
     *
     * @var string
     */
    protected $host = null;

    /**
     * Internal storage for the connections TCP port.
     *
     * @var int
     */
    protected $port = null;

    /**
     * Internal storage for the connections database.
     *
     * @var string
     */
    protected $dbname = null;

    /**
     * Internal storage for the connections username.
     *
     * @var string
     */
    protected $username = null;

    /**
     * Internal storage for the connections password.
     *
     * @var string
     */
    protected $password = null;

    /**
     * Internal storage for the connections charset.
     *
     * @var string
     */
    protected $charset = null;

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Magic methods START ---------------------------------------- */

    /**
     * Method call delegation to the wrapped PDO instance
     *
     * @param string $name
     * @param array $args
     *
     * @return mixed
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __call(string $name, array $args) {
        if (is_null($this->connection)) {
            throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::__call()', static::class));
        }

        if (!method_exists($this->connection, $name)) {
            throw new \InvalidArgumentException(sprintf('Method %s unsupported by PDO. \%s::__call()', $name, static::class));
        }

        return call_user_func_array([$this->connection, $name], $args);
    }

    /* ------------------------------------ Magic methods END ------------------------------------------ */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Connect this instance to a database engine.
     *
     * @return \Maleficarum\Database\Shard\Connection\AbstractConnection
     */
    public function connect(): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->connection = \Maleficarum\Ioc\Container::get('PDO', ['parameters' => $this->getConnectionParams()]);

        return $this;
    }

    /**
     * Check if this wrapper is connected to a database engine.
     *
     * @returns bool
     */
    public function isConnected(): bool {
        return !is_null($this->connection);
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Abstract methods START ------------------------------------- */

    /**
     * Fetch a database connection parameters
     *
     * @return array
     */
    abstract protected function getConnectionParams(): array;

    /**
     * Lock the specified table.
     *
     * @param string $table
     * @param string $mode
     *
     * @return \Maleficarum\Database\Shard\Connection\AbstractConnection
     */
    abstract protected function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE'): \Maleficarum\Database\Shard\Connection\AbstractConnection;

    /* ------------------------------------ Abstract methods END --------------------------------------- */

    /* ------------------------------------ Setters & Getters START ------------------------------------ */

    public function getHost(): ?string {
        return $this->host;
    }

    public function setHost(string $host): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->host = $host;

        return $this;
    }

    public function getPort(): ?int {
        return $this->port;
    }

    public function setPort(int $port): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->port = $port;

        return $this;
    }

    public function getDbname(): ?string {
        return $this->dbname;
    }

    public function setDbname(string $dbname): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->dbname = $dbname;

        return $this;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function setUsername(string $username): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function setPassword(string $password): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->password = $password;

        return $this;
    }

    public function getCharset(): ?string {
        return $this->charset;
    }

    public function setCharset(string $charset): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        $this->charset = $charset;

        return $this;
    }

    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}

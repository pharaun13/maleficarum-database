<?php
/**
 * This abstract class defines functionality common to all database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard\Connection;

use Maleficarum\Database\Exception\Exception;

/**
 * Wrapper for plain \PDO that unifies various databases even more
 *
 * @method bool inTransaction()
 * @method \PDOStatement|false query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
 */
abstract class AbstractConnection {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * PDO driver name, eg. 'pgsql'
     * @var string
     */
    protected $driverName = 'abstract';

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

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /**
     * @param string $driverName eg. pgsql
     */
    public function __construct(string $driverName)
    {
        $this->setDriverName($driverName);
    }

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
     * @throws Exception
     */
    public function connect(): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        try {
            $this->connection = \Maleficarum\Ioc\Container::get('PDO', $this->getConnectionParams());
        } catch (\PDOException $pex) {
            throw Exception::fromPDOException($pex, $this);
        }

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

    /**
     * @param       $statement
     * @param array $driver_options
     *
     * @deprecated Please use 'prepareStatement' instead as it is more reliable.
     */
    public function prepare($statement, array $driver_options = [])
    {
        throw new \LogicException("Please use 'prepareStatement' instead as it is more reliable.");
    }

    /**
     * Returns prepared statement that has all parameters bound and necessary driver options set
     *
     * This method should be preferred over direct use of AbstractConnection::prepare / \PDO::prepare
     * as it makes sure everything will work properly with various databases.
     *
     * @param string $query eg. SELECT * FROM users WHERE id = :id OR name = :name
     * @param array  $queryParams eg. [':id' => 123, ':name' => 'David']
     *
     * @return \PDOStatement
     */
    public function prepareStatement(string $query, array $queryParams): \PDOStatement
    {
        // making sure everything will work the same way for all databases
        $driverOptions = $this->getDriverOptions($query, $queryParams);
        $statement = $this->connection->prepare($query, $driverOptions);

        // bind parameters
        foreach ($queryParams as $key => $val) {
            $type = is_bool($val) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR;
            $statement->bindValue($key, $val, $type);
        }

        return $statement;
    }

    /**
     * Returns driver options for \PDO::prepare that are appropriate for given query and dto (parameters)
     *
     * @param string $query
     * @param array  $queryParams params that will be bound to prepared statement using \PDOStatement::bindValue
     *
     * @return array eg. [PDO::ATTR_EMULATE_PREPARES => true]
     *
     * @link http://php.net/manual/en/pdo.setattribute.php
     */
    protected function getDriverOptions(string $query, array $queryParams = []): array
    {
        return []; // no driver options needed by default
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

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return $this->driverName;
    }

    /**
     * @param string $driverName
     *
     * @return $this
     */
    private function setDriverName(string $driverName): AbstractConnection
    {
        if (empty($driverName)) {
            throw new \InvalidArgumentException('Database driver name must be provided.');
        }
        $this->driverName = $driverName;

        return $this;
    }

    /* ------------------------------------ Setters & Getters END -------------------------------------- */
}

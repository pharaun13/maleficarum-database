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

	/* ------------------------------------ Class Property END ----------------------------------------- */

	/* ------------------------------------ Magic methods START ---------------------------------------- */
	
	/**
	 * Method call delegation to the wrapped PDO instance
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function __call(string $name, array $args) {
		if (is_null($this->connection)) {
			throw new \RuntimeException(sprintf('Cannot execute DB methods prior to establishing a connection. \%s::_call()', static::class));
		}

		if (!method_exists($this->connection, $name)) {
			throw new \InvalidArgumentException(sprintf('Method %s unsupported by PDO. \%s::_call()', $name, static::class));
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
	public function connect() : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->connection = \Maleficarum\Ioc\Container::get('PDO', ['dsn' => $this->getDSN()]);

		return $this;
	}

	/**
	 * Check if this wrapper is connected to a database engine.
	 *
	 * @returns bool
	 */
	public function isConnected() : bool {
		return !is_null($this->connection);
	}
	
	/* ------------------------------------ Class Methods END ------------------------------------------ */

	/* ------------------------------------ Abstract methods START ------------------------------------- */
	
	/**
	 * Fetch a database specific DSN to create a connection.
	 *
	 * @return string
	 */
	abstract protected function getDSN() : string;

	/**
	 * Lock the specified table.
	 *
	 * @param string $table
	 * @param string $mode
	 * @return \Maleficarum\Database\Shard\Connection\AbstractConnection
	 */
	abstract protected function lockTable(string $table, string $mode = 'ACCESS EXCLUSIVE') : \Maleficarum\Database\Shard\Connection\AbstractConnection;
	
	/* ------------------------------------ Abstract methods END --------------------------------------- */

	/* ------------------------------------ Setters & Getters START ------------------------------------ */

	public function getHost() {
		return $this->host;
	}
	
	public function setHost(string $host) : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->host = $host;
		return $this;
	}

	public function getPort() {
		return $this->port;
	}
	
	public function setPort(int $port) : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->port = $port;
		return $this;
	}
	
	public function getDbname() {
		return $this->dbname;
	}
	
	public function setDbname(string $dbname) : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->dbname = $dbname;
		return $this;
	}
	
	public function getUsername() {
		return $this->username;
	}
	
	public function setUsername(string $username) : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->username = $username;
		return $this;
	}
	
	public function getPassword() {
		return $this->password;
	}
	
	public function setPassword(string $password) : \Maleficarum\Database\Shard\Connection\AbstractConnection {
		$this->password = $password;
		return $this;
	}
	
	/* ------------------------------------ Setters & Getters END -------------------------------------- */
}
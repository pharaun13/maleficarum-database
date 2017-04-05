<?php

/**
 * This class provides CRUD implementation specific to MSSQL database.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Model\Mssql;
         
abstract class Model extends \Maleficarum\Database\Data\Model\AbstractModel {

	/* ------------------------------------ Database\AbstractModel START ------------------------------- */

	/**
	 * @see \Maleficarum\Database\Data\Model\AbstractModel.create()
	 */
	public function create() : \Maleficarum\Database\Data\Model\AbstractModel {
		// connect to shard if necessary
		$shard = $this->getDb()->fetchShard($this->getShardRoute());
		$shard->isConnected() or $shard->connect();

		// fetch DB DTO object
		$data = $this->getDbDTO();

		// build the query
		$query = 'INSERT INTO "' . $this->getTable() . '" (';

		// attach column names
		$temp = [];
		foreach ($data as $el) $temp[] = $el['column'];
		count($temp) and $query .= '"' . implode('", "', $temp) . '"';

		// attach query transitional segment
		$query .= ') OUTPUT inserted.* VALUES (';

		// attach parameter names
		$temp = [];
		foreach ($data as $el) $temp[] = $el['param'];
		count($temp) and $query .= implode(', ', $temp);

		// conclude query building
		$query .= ')';

		// prepare the statement if necessary
		array_key_exists(static::class . '::' . __FUNCTION__, self::$st) or self::$st[static::class . '::' . __FUNCTION__] = $shard->prepare($query);

		// bind parameters
		foreach ($data as $el) {
			$type = is_bool($el['value']) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR;
			self::$st[static::class . '::' . __FUNCTION__]->bindValue($el['param'], $el['value'], $type);
		}

		// execute the query
		self::$st[static::class . '::' . __FUNCTION__]->execute();

		// set new model ID if possible
		$this->merge(self::$st[static::class . '::' . __FUNCTION__]->fetch());

		return $this;
	}

	/**
	 * @see \Maleficarum\Database\Data\Model\AbstractModel.read()
	 */
	public function read() : \Maleficarum\Database\Data\Model\AbstractModel {
		// connect to shard if necessary
		$shard = $this->getDb()->fetchShard($this->getShardRoute());
		$shard->isConnected() or $shard->connect();

		// build the query
		$query = 'SELECT * FROM "' . $this->getTable() . '" WHERE "' . $this->getIdColumn() . '" = :id';
		array_key_exists(static::class . '::' . __FUNCTION__, self::$st) or self::$st[static::class . '::' . __FUNCTION__] = $shard->prepare($query);

		// bind query params
		self::$st[static::class . '::' . __FUNCTION__]->bindValue(":id", $this->getId());
		if (!self::$st[static::class . '::' . __FUNCTION__]->execute() || count($result = self::$st[static::class . '::' . __FUNCTION__]->fetch()) === 0) {
			throw new \RuntimeException('No entity found - ID: ' . $this->getId() . '. ' . static::class . '::read()');
		}

		// fetch results and merge them into this object
		$this->merge($result);

		return $this;
	}

	/**
	 * @see \Maleficarum\Database\Data\Model\AbstractModel.update()
	 */
	public function update() : \Maleficarum\Database\Data\Model\AbstractModel {
		// connect to shard if necessary
		$shard = $this->getDb()->fetchShard($this->getShardRoute());
		$shard->isConnected() or $shard->connect();

		// fetch DB DTO object
		$data = $this->getDbDTO();

		// build the query
		$query = 'UPDATE "' . $this->getTable() . '" SET ';

		// attach data definition
		$temp = [];
		foreach ($data as $el) $temp[] = '"' . $el['column'] . '" = ' . $el['param'];
		$query .= implode(", ", $temp) . " ";

		// conclude query building
		$query .= 'OUTPUT inserted.* WHERE "' . $this->getIdColumn() . '" = :id';

		// prepare the statement if necessary
		array_key_exists(static::class . '::' . __FUNCTION__, self::$st) or self::$st[static::class . '::' . __FUNCTION__] = $shard->prepare($query);

		// bind parameters
		foreach ($data as $el) {
			$type = is_bool($el['value']) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR;
			self::$st[static::class . '::' . __FUNCTION__]->bindValue($el['param'], $el['value'], $type);
		}

		// bind ID and execute
		self::$st[static::class . '::' . __FUNCTION__]->bindValue(":id", $this->getId());
		self::$st[static::class . '::' . __FUNCTION__]->execute();

		// refresh current data with data returned from the database
		$this->merge(self::$st[static::class . '::' . __FUNCTION__]->fetch());

		return $this;
	}

	/**
	 * @see \Maleficarum\Database\Data\Model\AbstractModel.delete()
	 */
	public function delete() : \Maleficarum\Database\Data\Model\AbstractModel {
		// connect to shard if necessary
		$shard = $this->getDb()->fetchShard($this->getShardRoute());
		$shard->isConnected() or $shard->connect();

		// build the query
		$query = 'DELETE FROM "' . $this->getTable() . '" WHERE "' . $this->getIdColumn() . '" = :id';
		array_key_exists(static::class . '::' . __FUNCTION__, self::$st) or self::$st[static::class . '::' . __FUNCTION__] = $shard->prepare($query);

		// bind ID and execute
		self::$st[static::class . '::' . __FUNCTION__]->bindValue(":id", $this->getId());
		self::$st[static::class . '::' . __FUNCTION__]->execute();

		return $this;
	}
	
	/* ------------------------------------ Database\AbstractModel END --------------------------------- */
	
}

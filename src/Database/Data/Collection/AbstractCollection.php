<?php

/**
 * This class provides a basis for all persistent collection classes.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Collection;

abstract class AbstractCollection extends \Maleficarum\Data\Collection\AbstractCollection {
	
	/* ------------------------------------ Class Traits START ----------------------------------------- */

	/**
	 * \Maleficarum\Api\Database\Dependant
	 */
	use \Maleficarum\Database\Dependant;
	
	/* ------------------------------------ Class Traits END ------------------------------------------- */

	/* ------------------------------------ Class Property START --------------------------------------- */

	/**
	 * Internal storage for special param tokens to skip during query generation
	 *
	 * @var array
	 */
	protected static $specialTokens = [
		'__sorting',                    // sort/order config
		'__subset',                     // limit/subset config
		'__distinct',                   // add DISTINCT to generated query
		'__count',                      // add COUNT(_first_provided_value_) to generated query and GROUP BY by _remaining_provided_values_
		'__sum',                        // add SUM(_first_provided_value_) to generated query and GROUP BY by _remaining_provided_values_
		'__lock',                       // add FOR UPDATE to generated query - since locking works inside a transaction, using this will trigger an Exception when used in an atomic operation
	];
	
	/* ------------------------------------ Class Property END ----------------------------------------- */
	
	/* ------------------------------------ Class Methods START ---------------------------------------- */

	/**
	 * Populate this collection with data based on filters and options provided in the input parameter.
	 *
	 * @param array $data
	 * @return \Maleficarum\Data\Collection\AbstractCollection
	 */
	public function populate(array $data = []) : \Maleficarum\Data\Collection\AbstractCollection {
		// apply initial tests
		$this->populate_testDb()->populate_testLock($data)->populate_testSorting($data)->populate_testSubset($data);

		// create the DTO transfer object
		$dto = (object)['params' => [], 'data' => $data];

		// initialize query prepend section
		$query = $this->populate_prependSection($dto);

		// initial query definition
		$query = $this->populate_basicQuery($query, $dto);

		// attach filters
		$query = $this->populate_attachFilter($query, $dto);

		// blanket SQL statement
		$query = $this->populate_blanketSQL($query);

		// attach grouping info
		(array_key_exists('__count', $data) || array_key_exists('__sum', $data)) and $query = $this->populate_grouping($query, $dto);

		// attach sorting info
		array_key_exists('__sorting', $data) and $query = $this->populate_sorting($query, $dto);

		// attach subset info
		array_key_exists('__subset', $data) and $query = $this->populate_subset($query, $dto);

		// attach lock directive (caution - this can cause deadlocks when used incorrectly)
		array_key_exists('__lock', $data) and $query = $this->populate_lock($query, $dto);

		// fetch data from storage
		$this->populate_fetchData($query, $dto);

		// format all data entries
		$this->format();

		return $this;
	}

	/**
	 * Test database connection.
	 *
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 */
	protected function populate_testDb() : \Maleficarum\Database\Data\Collection\AbstractCollection {
		if (is_null($this->getDb())) {
			throw new \RuntimeException(sprintf('Cannot populate this collection with data prior to injecting a database shard manager. \%s::populate()', static::class));
		}

		return $this;
	}

	/**
	 * Test the provided data object for existence and format of sorting directives.
	 *
	 * @param array $data
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 * @throws \InvalidArgumentException
	 */
	protected function populate_testSorting(array $data) : \Maleficarum\Database\Data\Collection\AbstractCollection {
		if (array_key_exists('__sorting', $data)) {
			is_array($data['__sorting']) && count($data['__sorting']) or $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');

			foreach ($data['__sorting'] as $val) {
				// check structure and sort type
				!is_array($val) || count($val) !== 2 || ($val[1] !== 'ASC' && $val[1] !== 'DESC') and $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');

				// check column validity
				in_array($val[0], $this->getSortColumns()) or $this->respondToInvalidArgument('Incorrect sorting data. \%s::populate()');
			}
		}

		return $this;
	}

	/**
	 * Test the provided data object for existence and format of limit/offset directives.
	 *
	 * @param array $data
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 * @throws \InvalidArgumentException
	 */
	protected function populate_testSubset(array $data) : \Maleficarum\Database\Data\Collection\AbstractCollection {
		if (array_key_exists('__subset', $data)) {
			is_array($data['__subset']) or $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
			!isset($data['__subset']['limit']) || !is_int($data['__subset']['limit']) || $data['__subset']['limit'] < 1 and $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
			!isset($data['__subset']['offset']) || !is_int($data['__subset']['offset']) || $data['__subset']['offset'] < 0 and $this->respondToInvalidArgument('Incorrect subset data. \%s::populate()');
		}

		return $this;
	}

	/**
	 * Test provided data object for existence of the __lock directive and check if it can be applied in current connection state.
	 *
	 * @param array $data
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 * @throws \InvalidArgumentException
	 */
	protected function populate_testLock(array $data) : \Maleficarum\Database\Data\Collection\AbstractCollection {
		if (array_key_exists('__lock', $data)) {
			$this->getDb()->fetchShard($this->getShardRoute())->isConnected() or $this->respondToInvalidArgument('Cannot lock table outside of a transaction. \%s::populate()');
			$this->getDb()->fetchShard($this->getShardRoute())->inTransaction() or $this->respondToInvalidArgument('Cannot lock table outside of a transaction. \%s::populate()');
		}

		return $this;
	}

	/**
	 * Fetch the blanket conditional SQL query segment.
	 *
	 * @param string $query
	 * @return string
	 */
	protected function populate_blanketSQL(string $query) : string {
		return $query . '1=1 ';
	}

	/**
	 * Fetch data based on query and dto->params from the storage.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 */
	protected function populate_fetchData(string $query, \stdClass $dto) : \Maleficarum\Database\Data\Collection\AbstractCollection {
		// fetch a shard connection
		$shard = $this->getDb()->fetchShard($this->getShardRoute());

		// lazy connections - establish a connection if necessary
		$shard->isConnected() or $shard->connect();

		// prepare statement
		$st = $shard->prepare($query);

		// bind parameters
		foreach ($dto->params as $key => $val) $st->bindValue($key, $val, is_bool($val) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR);

		$st->execute();
		$this->data = $st->fetchAll(\PDO::FETCH_ASSOC);

		return $this;
	}

	/**
	 * Fetch current data set as a prepared set used by modification methods (insertAll(), deleteAll()).
	 * This method should be overridden in collections that need to behave differently than using a 1:1 mapping of the main data container.
	 *
	 * @param string $mode
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function prepareElements(string $mode) : array {
		($mode === 'INSERT' || $mode === 'DELETE') or $this->respondToInvalidArgument('Incorrect preparation mode. \%s::prepareElements()');

		return $this->data;
	}

	/**
	 * Iterate over all current data entries and perform any data formatting necessary. This method should be overloaded in any inheriting collection object
	 * that requires any specific data decoding such as JSON de-serialization or date formatting.
	 *
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 */
	protected function format() : \Maleficarum\Database\Data\Collection\AbstractCollection {
		return $this;
	}
	
	/* ------------------------------------ Class Methods END ------------------------------------------ */

	/* ------------------------------------ Abstract methods START ------------------------------------- */
	
	/**
	 * Initialize the query with a proper prepend section. By default the prepend section is empty and should be overloaded when necessary.
	 *
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_prependSection(\stdClass $dto) : string;

	/**
	 * Fetch initial populate query segment.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_basicQuery(string $query, \stdClass $dto) : string;

	/**
	 * Fetch a query with filter syntax attached.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_attachFilter(string $query, \stdClass $dto) : string;

	/**
	 * Fetch the grouping query segment.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_grouping(string $query, \stdClass $dto) : string;

	/**
	 * Attach the locking directive to the query.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_lock(string $query, \stdClass $dto) : string;

	/**
	 * Fetch the sorting query segment.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_sorting(string $query, \stdClass $dto) : string;

	/**
	 * Fetch the subset query segment.
	 *
	 * @param string $query
	 * @param \stdClass $dto
	 * @return string
	 */
	abstract protected function populate_subset(string $query, \stdClass $dto) : string;

	/**
	 * Insert all entries in this collection to it's storage.
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 */
	abstract public function insertAll() : \Maleficarum\Database\Data\Collection\AbstractCollection;

	/**
	 * Delete all entries in this collection from it's storage.
	 * @return \Maleficarum\Database\Data\Collection\AbstractCollection
	 */
	abstract public function deleteAll() : \Maleficarum\Database\Data\Collection\AbstractCollection;
	
	/**
	 * Fetch the name of current shard.
	 *
	 * @return string
	 */
	abstract public function getShardRoute() : string;

	/**
	 * Fetch the name of order column - should return null on collections without order data.
	 *
	 * @return null|string
	 */
	abstract protected function getOrderColumn();

	/**
	 * Fetch the name of main ID column - should return null on collections with no or multi-column primary keys.
	 *
	 * @return null|string
	 */
	abstract protected function getIdColumn();

	/**
	 * Fetch the name of db table used as data source for this collection.
	 *
	 * @return string
	 */
	abstract protected function getTable() : string;

	/**
	 * Return a list of column names that are allowed to be used for sorting.
	 *
	 * @return array
	 */
	abstract protected function getSortColumns() : array;
	
	/* ------------------------------------ Abstract methods END --------------------------------------- */
	
}
<?php

/**
 * This class extends PDOStatement functionality to allow for query execution profiling.
 */
declare (strict_types=1);

namespace Maleficarum\Database\PDO\Statement;

class Profiled extends \PDOStatement {
	
	/* ------------------------------------ Class Property START --------------------------------------- */

	/**
	 * Internal storage for the attached PDO object.
	 *
	 * @var \PDO
	 */
	protected $pdo = null;

	/**
	 * Internal storage for bound parameters. Used for profiling only.
	 *
	 * @var array
	 */
	protected $boundParams = [];

	/**
	 * Internal storage for a database profiler to use when executing queries.
	 *
	 * @var \Closure
	 */
	protected $profiler = null;
	
	/* ------------------------------------ Class Property END ----------------------------------------- */

	/* ------------------------------------ Magic methods START ---------------------------------------- */
	
	/**
	 * Initialize a new Statement instance and allow for trailer injection.
	 *
	 * @param \PDO $pdo
	 * @param \Closure $profiler
	 */
	protected function __construct(\PDO $pdo, \Closure $profiler = null) {
		$this->pdo = $pdo;
		$this->profiler = $profiler;
	}
	
	/* ------------------------------------ Magic methods END ------------------------------------------ */
	
	/* ------------------------------------ Class Methods START ---------------------------------------- */

	/**
	 * Binds a new value to the statement and stores it in an internal storage array for future use.
	 *
	 * @param mixed $parameter
	 * @param mixed $value
	 * @param int $dataType
	 * @return bool
	 */
	public function bindValue($parameter, $value, $dataType = \PDO::PARAM_STR) {
		$this->boundParams[$parameter] = $value;

		return parent::bindValue($parameter, $value, $dataType);
	}
	
	/**
	 * By default fetch assoc.
	 * 
	 * @param int $how
	 * @param int $orientation
	 * @param int $offset
	 * @return mixed
	 */
	public function fetch($how = \PDO::FETCH_ASSOC, $orientation = \PDO::FETCH_ORI_NEXT, $offset = 0) {
		return parent::fetch($how, $orientation, $offset);
	}
	
	/**
	 * By default fetch assoc.
	 * 
	 * @param int $fetch_style
	 * @param int $fetch_argument
	 * @param array $ctor_args
	 * @return mixed
	 */
	public function fetchAll($fetch_style = \PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = null) {
		return parent::fetchAll($fetch_style);
	}
	
	/**
	 * Execute the statement and profile it's behaviour if a profiler has been registered. 
	 *
	 * @param array $args
	 * @return bool
	 */
	public function execute($args = null) {
		// handle incoming bound params
		if (is_array($args)) foreach ($args as $key => $val) $this->boundParams[$key] = $val;

		// execute statement
		$start = microtime(true);
		$result = parent::execute($args);
		$end = microtime(true);

		// add profile data
		$profiler = $this->profiler;
		is_null($profiler) or $profiler([
			'start' => $start, 
			'end' => $end, 
			'queryString' => $this->queryString, 
			'params' => $this->boundParams
		]);

		return $result;
	}
	
	/* ------------------------------------ Class Methods END ------------------------------------------ */
	
}
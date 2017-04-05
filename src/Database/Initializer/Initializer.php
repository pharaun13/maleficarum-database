<?php

/**
 * This class carries ioc initialization functionality used by this component.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Initializer;

class Initializer {
	/**
	 * This will setup all IOC definitions specific to this component.
	 * @return string
	 */
	static public function initialize(array $opts = []) : string {
		// load default builder if skip not requested
		$builders = $opts['builders'] ?? [];
		is_array($builders) or $builders = [];
		
		if (!isset($builders['database']['skip'])) {
			\Maleficarum\Ioc\Container::register('Maleficarum\Database\Shard\Manager', function ($dep) {
				$manager = new \Maleficarum\Database\Shard\Manager();

				// check shard list
				if (!isset($dep['Maleficarum\Config']['database']['shards']) || !count($dep['Maleficarum\Config']['database']['shards'])) {
					throw new \RuntimeException('Cannot set up database access - no shards are defined.');
				}

				// create shard connections
				$shards = [];
				foreach ($dep['Maleficarum\Config']['database']['shards'] as $shard) {
					if (!isset($dep['Maleficarum\Config']['database_shards'][$shard]) || !count($dep['Maleficarum\Config']['database_shards'][$shard])) {
						throw new \RuntimeException('No config defined for the shard: ' . $shard);
					}
					$cfg            = $dep['Maleficarum\Config']['database_shards'][$shard];
					$charset        = $cfg['charset'] ?? null;

                    $shards[$shard] = \Maleficarum\Ioc\Container::get('Maleficarum\Database\Shard\Connection\\' . $cfg['driver'] . '\Connection');
					$shards[$shard]->setHost($cfg['host'])
					               ->setPort((int) $cfg['port'])
					               ->setDbname($cfg['dbName'])
					               ->setUsername($cfg['user'])
					               ->setPassword($cfg['password'])
                                   ->setCharset($charset);
				}

				// check routes
				if (!isset($dep['Maleficarum\Config']['database']['routes']) || !count($dep['Maleficarum\Config']['database']['routes'])) {
					throw new \RuntimeException('Cannot set up database access - no shard routes are defined.');
				}
				if (!array_key_exists(\Maleficarum\Database\Shard\Manager::DEFAULT_ROUTE, $dep['Maleficarum\Config']['database']['routes'])) {
					throw new \RuntimeException('Cannot set up database access - default route is not defined.');
				}

				// attach shards to routes
				foreach ($dep['Maleficarum\Config']['database']['routes'] as $route => $shard) {
					$manager->attachShard($shards[$shard], $route);
				}

				return $manager;
			});

			\Maleficarum\Ioc\Container::register('PDO', function ($dep, $opts) {
				$pdo = new \PDO($opts['dsn']);

				$args = [$pdo];
				if (isset($dep['Maleficarum\Profiler\Database'])) {
					$profiler = $dep['Maleficarum\Profiler\Database'];
					array_push($args, function (array $data) use ($profiler) {
						$profiler->addQuery($data['start'], $data['end'], $data['queryString'], $data['params']);
					});
				}

				$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Maleficarum\Database\PDO\Statement\Profiled', $args]);

				return $pdo;
			});
		}
		
		$shards = \Maleficarum\Ioc\Container::get('Maleficarum\Database\Shard\Manager');
		\Maleficarum\Ioc\Container::registerDependency('Maleficarum\Database', $shards);
		
		return __METHOD__;
	}
}

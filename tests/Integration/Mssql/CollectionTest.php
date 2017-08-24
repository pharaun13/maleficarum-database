<?php
declare(strict_types=1);

namespace Maleficarum\Database\Tests\Integration\Mssql;

use Maleficarum\Config\Ini\Config;
use Maleficarum\Database\Initializer\Initializer;
use Maleficarum\Database\Shard\Connection\Mssql\Connection;
use Maleficarum\Ioc\Container;
use PHPUnit\Framework\TestCase;

/**
 * Making sure we can use Collection with MSSQL
 */
class CollectionTest extends TestCase
{
    /**
     * @var \Maleficarum\Config\AbstractConfig
     */
    private $config;

    /**
     * @var \Maleficarum\Database\Shard\Manager
     */
    private $database;

    public function setUp()
    {
        parent::setUp();
        $this->initConfig();
        $this->initDatabase();
    }

    public function testFetchCollectionOfLessThanMssqlParameterCountLimit()
    {
        $shard = $this->config['SomeCollection']['shard'];
        $table = $this->config['SomeCollection']['table'];
        $idColumn = $this->config['SomeCollection']['idColumn'];
        $collection = new SomeCollection($shard, $table, $idColumn);
        $collection->setDb($this->database);

        // try to fetch over 2100 items
        $collection->populate(['product_id' => range(1, 20)]);

        self::assertGreaterThan(0, $collection->count(), 'It should be possible to ask for less items than MS SQL limit.');
    }

    public function testFetchCollectionOfMoreThanMssqlParameterCountLimit()
    {
        $shard = $this->config['SomeCollection']['shard'];
        $table = $this->config['SomeCollection']['table'];
        $idColumn = $this->config['SomeCollection']['idColumn'];
        $collection = new SomeCollection($shard, $table, $idColumn);
        $collection->setDb($this->database);

        // try to fetch over 2100 items
        $collection->populate(['product_id' => range(1, 2*Connection::PDO_PARAMS_LIMIT)]);

        self::assertGreaterThan(0, $collection->count(), 'It should be possible to ask for more items than MS SQL limit.');
    }

    /**
     * @return void
     */
    private function initDatabase()
    {
        try {
            Container::registerDependency('Maleficarum\Config', $this->config);
            Initializer::initialize();

            $this->database = Container::getDependency('Maleficarum\Database');
        } catch (\Throwable $tex) {
            self::markTestSkipped(
                'Could not initialize database connection. Please make sure config has been properly configured.'
                . '; Exception message: ' . $tex->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    private function initConfig()
    {
        try {
            $this->config = new Config(__DIR__ . '/../config.ini');
        } catch (\Throwable $tex) {
            self::markTestSkipped(
                'Could not initialize config. Please make sure config.ini has been properly created.'
                . '; Exception message: ' . $tex->getMessage()
            );
        }
    }
}
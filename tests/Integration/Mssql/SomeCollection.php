<?php
declare(strict_types=1);

namespace Maleficarum\Database\Tests\Integration\Mssql;

use Maleficarum\Database\Data\Collection\Mssql\Collection;

class SomeCollection extends Collection {
    /**
     * @var string
     */
    private $shardRoute;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $idColumn;

    /**
     * @param string $shardRoute eg. 'dk'
     * @param string $table      eg. 'products'
     * @param string $idColumn   eg. 'product_id'
     */
    public function __construct(string $shardRoute, string $table, string $idColumn) {
        $this->shardRoute = $shardRoute;
        $this->table = $table;
        $this->idColumn = $idColumn;
    }

    /**
     * Fetch the name of current shard.
     *
     * @return string
     */
    public function getShardRoute(): string {
        return $this->shardRoute;
    }

    /**
     * Fetch the name of db table used as data source for this collection.
     *
     * @return string
     */
    protected function getTable(): string {
        return $this->table;
    }

    /**
     * Fetch the name of main ID column - should return null on collections with no or multi-column primary keys.
     *
     * @return null|string
     */
    protected function getIdColumn(): ?string {
        return $this->idColumn;
    }

    /**
     * Fetch the name of order column - should return null on collections without order data.
     *
     * @return null|string
     */
    protected function getOrderColumn(): ?string {
        return null;
    }

    /**
     * Return a list of column names that are allowed to be used for sorting.
     *
     * @return array
     */
    protected function getSortColumns(): array {
        return [];
    }
}
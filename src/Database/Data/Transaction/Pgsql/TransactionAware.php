<?php
declare(strict_types=1);

namespace Maleficarum\Database\Data\Transaction\Pgsql;

/**
 * This trait defines a set of helper methods for all objects that require transactional database access.
 */
trait TransactionAware {

    /**
     * Helper method to establish if a shard is in a transaction
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     *
     * @return bool
     */
    protected function isInTransaction($object) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $shard->isConnected() or $shard->connect();

        return $shard->inTransaction();
    }

    /**
     * Helper method to establish a transaction on a shard.
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     *
     * @return $this
     */
    protected function beginTransaction($object) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $shard->isConnected() or $shard->connect();
        $shard->inTransaction() or $shard->beginTransaction();

        return $this;
    }

    /**
     * Helper method to commit a transaction on a shard.
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     *
     * @return $this
     */
    protected function commitTransaction($object) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $shard->inTransaction() and $shard->commit();

        return $this;
    }

    /**
     * Helper method to rollback a transaction on a shard.
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     *
     * @return $this
     *
     * @throws \PDOException
     */
    protected function rollbackTransaction($object) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $shard->rollback();

        return $this;
    }

    /**
     * Create an Advisory Lock
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     * @param string           $key
     *
     * @return bool
     */
    protected function createAdvisoryLock($object, $key) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $query = 'SELECT pg_try_advisory_xact_lock(' . crc32($key) . ');';

        return $shard->prepareStatement($query, [])->execute();
    }


    /**
     * Release an Advisory Lock
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     * @param string           $key
     *
     * @return bool
     */
    protected function releaseAdvisoryLock($object, $key) {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $query = 'SELECT pg_advisory_unlock(' . crc32($key) . ');';

        return $shard->prepareStatement($query, [])->execute();
    }
}

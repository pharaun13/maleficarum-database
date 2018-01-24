<?php
declare(strict_types=1);

namespace Maleficarum\Database\Data\Transaction\Pgsql;

use Maleficarum\Database\Exception\InvalidArgumentException;

/**
 * This trait defines a set of helper methods for all objects that require transactional database access.
 *
 * @link https://www.postgresql.org/docs/9.5/static/explicit-locking.html#ADVISORY-LOCKS
 * @link https://vladmihalcea.com/2017/04/12/how-do-postgresql-advisory-locks-work/
 */
trait TransactionAware {
    /**
     * Helper method to establish if a shard is in a transaction
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     *
     * @return bool
     */
    protected function isInTransaction($object): bool {
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
     * Create a blocking exclusive advisory lock
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     * @param string                                                                                              $key
     * @param string                                                                                              $lockLevel 'transaction' OR 'session'
     *
     * @return bool TRUE if lock has been created
     *
     * @throws InvalidArgumentException if unsupported lock level given
     *
     * @see https://www.postgresql.org/docs/9.6/static/functions-admin.html#FUNCTIONS-ADVISORY-LOCKS
     */
    protected function createAdvisoryLock($object, $key, $lockLevel = 'transaction'): bool {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        switch ($lockLevel) {
            case 'transaction':
                $query = 'SELECT pg_advisory_xact_lock(' . crc32($key) . ');';
                break;
            case 'session':
                $query = 'SELECT pg_advisory_lock(' . crc32($key) . ');';
                break;
            default:
                throw new InvalidArgumentException("Unsupported advisory lock level: '{$lockLevel}'. Supported levels: 'transaction', 'session'.");
        }

        return $shard->prepareStatement($query, [])->execute();
    }


    /**
     * Release an Advisory Lock
     *
     * @param \Maleficarum\Database\Data\Collection\Pgsql\Collection|\Maleficarum\Database\Data\Model\Pgsql\Model $object
     * @param string                                                                                              $key
     *
     * @return bool TRUE if lock has been released
     */
    protected function releaseAdvisoryLock($object, $key): bool {
        $shard = $object->getDb()->fetchShard($object->getShardRoute());
        $query = 'SELECT pg_advisory_unlock(' . crc32($key) . ');';

        return $shard->prepareStatement($query, [])->execute();
    }
}

<?php
/**
 * This class provides CRUD implementation specific to postgresql database.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Model\Pgsql;

abstract class Model extends \Maleficarum\Database\Data\Model\AbstractModel {
    /* ------------------------------------ Database\AbstractModel START ------------------------------- */

    /**
     * @see \Maleficarum\Data\Model\AbstractPersistableModel::create()
     */
    public function create(): \Maleficarum\Data\Model\AbstractPersistableModel {
        // connect to shard if necessary
        $shard = $this->getDb()->fetchShard($this->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // fetch DB DTO object
        $data = $this->getDbDTO();

        // build the query
        $query = 'INSERT INTO "' . $this->getTable() . '" (';

        // attach column names
        $temp = [];
        foreach ($data as $el) {
            $temp[] = $el['column'];
        }
        count($temp) and $query .= '"' . implode('", "', $temp) . '"';

        // attach query transitional segment
        $query .= ') VALUES (';

        // attach parameter names
        $temp = [];
        foreach ($data as $el) {
            $temp[] = $el['param'];
        }
        count($temp) and $query .= implode(', ', $temp);

        // conclude query building
        $query .= ')';

        // attach returning
        $query .= ' RETURNING *;';

        $queryParams = [];
        foreach ($data as $el) {
            $queryParams[$el['param']] = $el['value'];
        }
        $statement = $shard->prepareStatement($query, $queryParams, true);

        $statement->execute();

        // set new model ID if possible
        $this->merge($statement->fetch());

        return $this;
    }

    /**
     * @see \Maleficarum\Data\Model\AbstractPersistableModel::read()
     */
    public function read(): \Maleficarum\Data\Model\AbstractPersistableModel {
        // connect to shard if necessary
        $shard = $this->getDb()->fetchShard($this->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // build the query
        $query = 'SELECT * FROM "' . $this->getTable() . '" WHERE "' . $this->getIdColumn() . '" = :id';
        $queryParams = [':id' => $this->getId()];
        $statement = $shard->prepareStatement($query, $queryParams, true);

        if (!$statement->execute() || $statement->rowCount() !== 1) {
            throw new \Maleficarum\Database\Exception\EntityNotFoundException(static::class, (string)$this->getId());
        }

        // fetch results and merge them into this object
        $result = $statement->fetch();
        $this->merge($result);

        return $this;
    }

    /**
     * @see \Maleficarum\Data\Model\AbstractPersistableModel::update()
     */
    public function update(): \Maleficarum\Data\Model\AbstractPersistableModel {
        // connect to shard if necessary
        $shard = $this->getDb()->fetchShard($this->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // fetch DB DTO object
        $data = $this->getDbDTO();

        // build the query
        $query = 'UPDATE "' . $this->getTable() . '" SET ';

        // attach data definition
        $temp = [];
        foreach ($data as $el) {
            $temp[] = '"' . $el['column'] . '" = ' . $el['param'];
        }
        $query .= implode(", ", $temp) . " ";

        // conclude query building
        $query .= 'WHERE "' . $this->getIdColumn() . '" = :id RETURNING *';

        $queryParams = [];
        foreach ($data as $el) {
            $queryParams[$el['param']] = $el['value'];
        }
        $queryParams[':id'] = $this->getId();

        $statement = $shard->prepareStatement($query, $queryParams, true);

        $statement->execute();

        // refresh current data with data returned from the database
        $this->merge($statement->fetch());

        return $this;
    }

    /**
     * @see \Maleficarum\Data\Model\AbstractPersistableModel::delete()
     */
    public function delete(): \Maleficarum\Data\Model\AbstractPersistableModel {
        // connect to shard if necessary
        $shard = $this->getDb()->fetchShard($this->getShardRoute());
        $shard->isConnected() or $shard->connect();

        // build the query
        $query = 'DELETE FROM "' . $this->getTable() . '" WHERE "' . $this->getIdColumn() . '" = :id';
        $queryParams = [':id' => $this->getId()];
        $statement = $shard->prepareStatement($query, $queryParams, true);

        $statement->execute();

        return $this;
    }

    /* ------------------------------------ Database\AbstractModel END --------------------------------- */
}

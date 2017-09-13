<?php
/**
 * This class provides a basis for all persistent model classes.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Model;

abstract class AbstractModel extends \Maleficarum\Data\Model\AbstractModel {
    /* ------------------------------------ Class Traits START ----------------------------------------- */

    /**
     * \Maleficarum\Database\Dependant
     */
    use \Maleficarum\Database\Dependant;

    /* ------------------------------------ Class Traits END ------------------------------------------- */

    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal cache storage for prepared PDO statements.
     *
     * @var \PDOStatement[]
     */
    protected static $st = [];

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Fetch the name of main ID column.
     *
     * @return string
     */
    public function getIdColumn(): string {
        return $this->getModelPrefix() . 'Id';
    }

    /**
     * This method returns an array of properties to be used in INSERT and UPDATE CRUD operations. The format for each entry is as follows:
     *
     * $entry['param'] = ':bindParamName';
     * $entry['value'] = 'Param value (as used during the bind process)';
     * $entry['column'] = 'Name of the storage column to bind against.';
     *
     * This is a generic persistable model implementation so it will be useful in most cases but it's not optimal in
     * terms of performance. Reimplement this method to avoid \ReflectionClass use in high-stress classes.
     *
     * @return array
     */
    protected function getDbDTO(): array {
        $result = [];

        $properties = \Maleficarum\Ioc\Container::get('ReflectionClass', [static::class])->getProperties(\ReflectionProperty::IS_PRIVATE);
        foreach ($properties as $key => $prop) {
            if ($prop->name === $this->getIdColumn()) {
                continue;
            }
            if (strpos($prop->name, $this->getModelPrefix()) !== 0) {
                continue;
            }

            $methodName = 'get' . str_replace(' ', "", ucwords($prop->name));
            if (!method_exists($this, $methodName)) {
                continue;
            }

            $result[$prop->name] = ['param' => ':' . $prop->name . '_token_' . $key, 'value' => $this->$methodName(), 'column' => $prop->name];
        }

        return $result;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */

    /* ------------------------------------ Data\AbstractModel methods START --------------------------- */

    /**
     * @see \Maleficarum\Data\Model\AbstractModel::getId()
     */
    public function getId() {
        $method = 'get' . ucfirst($this->getModelPrefix()) . 'Id';

        return $this->$method();
    }

    /**
     * @see \Maleficarum\Data\Model\AbstractModel::setId()
     *
     * @return \Maleficarum\Data\Model\AbstractModel|$this
     */
    public function setId($id): \Maleficarum\Data\Model\AbstractModel {
        $method = 'set' . ucfirst($this->getModelPrefix()) . 'Id';
        $this->$method($id);

        return $this;
    }

    /* ------------------------------------ Data\AbstractModel methods END ----------------------------- */

    /* ------------------------------------ Abstract methods START ------------------------------------- */

    /**
     * Persist data stored in this model as a new storage entry.
     *
     * @return \Maleficarum\Database\Data\Model\AbstractModel|$this
     */
    abstract public function create(): \Maleficarum\Database\Data\Model\AbstractModel;

    /**
     * Refresh this model with current data from the storage
     *
     * @return \Maleficarum\Database\Data\Model\AbstractModel|$this
     */
    abstract public function read(): \Maleficarum\Database\Data\Model\AbstractModel;

    /**
     * Update storage entry with data currently stored in this model.
     *
     * @return \Maleficarum\Database\Data\Model\AbstractModel|$this
     */
    abstract public function update(): \Maleficarum\Database\Data\Model\AbstractModel;

    /**
     * Delete an entry from the storage based on ID data stored in this model
     *
     * @return \Maleficarum\Database\Data\Model\AbstractModel|$this
     */
    abstract public function delete(): \Maleficarum\Database\Data\Model\AbstractModel;

    /**
     * Validate data stored in this model to check if it can be persisted in storage.
     *
     * @param bool $clear
     *
     * @return bool
     */
    abstract public function validate(bool $clear = true): bool;

    /**
     * Fetch the name of current shard.
     *
     * @return string
     */
    abstract public function getShardRoute(): string;

    /**
     * Fetch the name of db table used as data source for this model.
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * Fetch the prefix used as a prefix for database column property names.
     *
     * @return string
     */
    abstract protected function getModelPrefix(): string;

    /* ------------------------------------ Abstract methods END --------------------------------------- */
}

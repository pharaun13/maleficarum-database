<?php
/**
 * This class provides a basis for all persistent model classes.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Model;

abstract class AbstractModel extends \Maleficarum\Data\Model\AbstractPersistableModel {
    /* ------------------------------------ Class Traits START ----------------------------------------- */

    /**
     * \Maleficarum\Database\Dependant
     */
    use \Maleficarum\Database\Dependant;

    /* ------------------------------------ Class Traits END ------------------------------------------- */

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
     * This method returns an array of properties to be used in INSERT and UPDATE CRUD operations. The format for each
     * entry is as follows:
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
     * @inheritdoc
     */
    public function getId() {
        $method = 'get' . ucfirst($this->getModelPrefix()) . 'Id';

        return $this->$method();
    }

    /**
     * @see \Maleficarum\Data\Model\AbstractModel::setId()
     *
     * @return \Maleficarum\Data\Model\AbstractModel|$this enables method chaining
     */
    public function setId($id): \Maleficarum\Data\Model\AbstractModel {
        $method = 'set' . ucfirst($this->getModelPrefix()) . 'Id';
        $this->$method($id);

        return $this;
    }

    /* ------------------------------------ Data\AbstractModel methods END ----------------------------- */

    /* ------------------------------------ Abstract methods START ------------------------------------- */

    /**
     * Fetch the name of db table used as data source for this model.
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /* ------------------------------------ Abstract methods END --------------------------------------- */
}

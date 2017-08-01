<?php
/**
 * This trait allows for database connection usage inside classes that use it.
 */
declare (strict_types=1);

namespace Maleficarum\Database;

trait Dependant {

    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Internal storage for the database connection manager object.
     *
     * @var \Maleficarum\Database\Shard\Manager
     */
    protected $db = null;

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Get the currently assigned database connection manager object.
     *
     * @return \Maleficarum\Database\Shard\Manager|null
     */
    public function getDb(): ?\Maleficarum\Database\Shard\Manager {
        return $this->db;
    }

    /**
     * Inject a new database connection manager.
     *
     * @param \Maleficarum\Database\Shard\Manager $db
     *
     * @return \Maleficarum\Database\Dependant
     */
    public function setDb(\Maleficarum\Database\Shard\Manager $db) {
        $this->db = $db;

        return $this;
    }

    /**
     * Unassign the current database connection manager object.
     *
     * @return \Maleficarum\Database\Dependant
     */
    public function detachDb() {
        $this->db = null;

        return $this;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

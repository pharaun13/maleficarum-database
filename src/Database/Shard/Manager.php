<?php
/**
 * This is a class used to manage shard database connections.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Shard;

class Manager {
    /* ------------------------------------ Class Property START --------------------------------------- */

    /**
     * Name of the default shard route
     *
     * @var String
     */
    const DEFAULT_ROUTE = '__DEFAULT__';

    /**
     * Internal storage for route to shard mapping.
     *
     * @var array
     */
    protected $routes = [];

    /* ------------------------------------ Class Property END ----------------------------------------- */

    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * Attach a shard to the specified route.
     *
     * @param \Maleficarum\Database\Shard\Connection\AbstractConnection $shard
     * @param string                                                    $route
     *
     * @return \Maleficarum\Database\Shard\Manager
     * @throws \InvalidArgumentException
     */
    public function attachShard(\Maleficarum\Database\Shard\Connection\AbstractConnection $shard, string $route): \Maleficarum\Database\Shard\Manager {
        if (!is_string($route) || !mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::attachShard()', static::class));
        }

        $this->routes[$route] = $shard;

        return $this;
    }

    /**
     * Detach a shard from the specified route.
     *
     * @param string $route
     *
     * @return \Maleficarum\Database\Shard\Manager
     * @throws \InvalidArgumentException
     */
    public function detachShard(string $route): \Maleficarum\Database\Shard\Manager {
        if (!is_string($route) || !mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::detachShard()', static::class));
        }

        if (array_key_exists($route, $this->routes)) {
            unset($this->routes[$route]);
        }

        return $this;
    }

    /**
     * Fetch a shard for the specified route. If such route is not defined a default shard will be fetched.
     *
     * @param string $route
     *
     * @throws \InvalidArgumentException
     * @return \Maleficarum\Database\Shard\Connection\AbstractConnection
     */
    public function fetchShard(string $route): \Maleficarum\Database\Shard\Connection\AbstractConnection {
        if (!is_string($route) || !mb_strlen($route)) {
            throw new \InvalidArgumentException(sprintf('Incorrect route provided - non empty string expected. %s::fetchShard()', static::class));
        }

        if (array_key_exists($route, $this->routes)) {
            return $this->routes[$route];
        }
        if (array_key_exists(self::DEFAULT_ROUTE, $this->routes)) {
            return $this->routes[self::DEFAULT_ROUTE];
        }

        throw new \InvalidArgumentException(sprintf('Impossible to fetch the specified route. %s::fetchShard()', static::class));
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}

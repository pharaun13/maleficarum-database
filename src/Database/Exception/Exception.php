<?php
declare(strict_types=1);

namespace Maleficarum\Database\Exception;
use Maleficarum\Database\Shard\Connection\AbstractConnection;

/**
 * Generic Database exception
 */
class Exception extends \PDOException
{
    /**
     * @param \PDOException      $pex
     * @param AbstractConnection $connection
     *
     * @return Exception
     */
    public static function fromPDOException(\PDOException $pex, AbstractConnection $connection): Exception
    {
        $message = $pex->getMessage();
        if (stripos('could not find driver', $pex->getMessage()) !== false) {
            $message = "Could not find PDO database driver: '{$connection->getDriverName()}'. Is proper PHP extension installed?";
        }

        return new self(
            $message,
            (int) $pex->getCode(),
            $pex
        );
    }
}
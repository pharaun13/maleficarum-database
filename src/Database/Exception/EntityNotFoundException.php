<?php
declare(strict_types=1);

namespace Maleficarum\Database\Exception;

use Throwable;

/**
 * Thrown whenever model could not it's data from the database because it's corresponding entity does not exist.
 */
final class EntityNotFoundException extends \RuntimeException implements DatabaseExceptionInterface {
    /**
     * @param string         $modelClassName eg. 'Model\Store\Product', you can pass value of `static::class`
     * @param string         $entityId       eg. '1247'
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $modelClassName, string $entityId, int $code = 0, Throwable $previous = null) {
        $message = "No entity found - ID: {$entityId} . Model class: {$modelClassName}";
        parent::__construct($message, $code, $previous);
    }
}
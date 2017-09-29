<?php
declare(strict_types=1);

namespace Maleficarum\Database\Exception;

use Throwable;

/**
 * Thrown whenever model could not it's data from the database because it's corresponding entity does not exist.
 */
final class EntityNotFoundException extends \RuntimeException implements DatabaseExceptionInterface {
    /**
     * @var string
     */
    private $modelClassName;

    /**
     * @var string
     */
    private $entityId;

    /**
     * @param string         $modelClassName eg. 'Model\Store\Product', you can pass value of `static::class`
     * @param string         $entityId       eg. '1247'
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $modelClassName, string $entityId, int $code = 0, Throwable $previous = null) {
        $this->modelClassName = $modelClassName;
        $this->entityId = $entityId;

        $message = "No entity found - ID: {$entityId} . Model class: {$modelClassName}";
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string name of the model class that entity has not been found for; eg. 'Model\Store\Product'
     */
    public function getModelClassName(): string {
        return $this->modelClassName;
    }

    /**
     * @return string Id of the model/entity that has not been found in the database; eg. '1247'
     */
    public function getEntityId(): string {
        return $this->entityId;
    }
}
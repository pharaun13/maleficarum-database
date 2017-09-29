<?php
declare(strict_types=1);

namespace Maleficarum\Database\Exception;

/**
 * Thrown whenever plain \InvalidArgumentException would be thrown - just with marker interface so we know it's an error on the database library level
 */
final class LogicException extends \LogicException implements DatabaseExceptionInterface {

}
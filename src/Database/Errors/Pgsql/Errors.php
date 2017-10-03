<?php
declare (strict_types=1);

namespace Maleficarum\Database\Errors\Pgsql;

/**
 * Interface holding human readable constants for PostgreSQL Error Codes
 *
 * @see https://www.postgresql.org/docs/8.2/static/errcodes-appendix.html
 */
final class Errors {
    const UNDEFINED_TABLE = '42P01';
    const DUPLICATE_TABLE = '42P07';
    const UNIQUE_VIOLATION = '23505';
}

<?php
declare(strict_types=1);

namespace Maleficarum\Database\Tests\Unit\Collection;

use Maleficarum\Database\Data\Collection\Tools;
use PHPUnit\Framework\TestCase;

class ToolsTest extends TestCase
{
    public function testWithoutQueryParams()
    {
        $inputQuery = 'SELECT * FROM "miinto_products" WHERE "product_id" IN (:product_id_0, :product_id_1, :product_id_2, :product_id_9, :product_id_10, :product_id_11, :product_id_12, :product_id_15, :product_id_18, :product_id_19) AND 1=1 ';
        $removedParams = [
           ':product_id_0',
           ':product_id_1',
           ':product_id_12',
           ':product_id_18',
           ':product_id_19'
        ];
        $expectedQuery = 'SELECT * FROM "miinto_products" WHERE "product_id" IN ( :product_id_2, :product_id_9, :product_id_10, :product_id_11, :product_id_15) AND 1=1 ';

        $actualQuery = Tools::withoutQueryParams($inputQuery, $removedParams);

        self::assertEquals($expectedQuery, $actualQuery, 'Proper params should be removed from query');
    }
}

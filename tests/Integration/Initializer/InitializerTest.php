<?php
declare(strict_types=1);

namespace Maleficarum\Database\Tests\Integration\Initializer;

use Maleficarum\Config\Ini\Config;
use Maleficarum\Database\Initializer\Initializer;
use Maleficarum\Ioc\Container;
use PHPUnit\Framework\TestCase;

/**
 * Make sure we can initialize Database in Maleficarum based project
 */
class InitializerTest extends TestCase
{
    public function testInitialize()
    {
        $config = new Config(__DIR__ . '/../config.ini.dist');
        Container::registerDependency('Maleficarum\Config', $config);

        Initializer::initialize();
        $database = Container::getDependency('Maleficarum\Database');

        self::assertInstanceOf(
            'Maleficarum\Database\Shard\Manager',
            $database,
            'Maleficarum\Database dependency should be available via the Container'
        );
    }
}

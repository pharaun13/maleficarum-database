<?php
/**
 * This will help PhpStorm to autocomplete dependencies taken from \Maleficarum\Ioc\Container
 *
 * @see https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 */
namespace PHPSTORM_META {
    $STATIC_METHOD_TYPES = [
        \Maleficarum\Ioc\Container::get('') => [
            "" == "@",
        ],
        \Maleficarum\Ioc\Container::getDependency('') => [
            "" == "@",
        ]
    ];
}
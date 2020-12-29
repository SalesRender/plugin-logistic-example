<?php
/**
 * Created for plugin-logistic-example
 * Date: 29.12.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Logistic\Components;


class ValueObjectFactory
{

    private function __construct()
    {}

    public static function buildOrNull(string $class, $value)
    {
        if (is_null($value)) {
            return null;
        }

        return new $class($value);
    }

}
<?php

namespace Malico\PhpSculptor;

use PhpParser\NodeVisitorAbstract;

class ModifierFactory
{
    public static function make(string $visitorType, array $params = []): NodeVisitorAbstract
    {
        $className = sprintf('Malico\PhpSculptor\Modifiers\%sModifier', $visitorType);

        if (! class_exists($className)) {
            throw new \InvalidArgumentException('Modifier class does not exist: '.$className);
        }

        return new $className(...$params);
    }

    public static function factory(string $visitorClass, ?callable $parameterMapper = null): callable
    {
        return function (array $modification) use ($visitorClass, $parameterMapper) {
            if ($parameterMapper === null) {
                return self::make($visitorClass, [$modification]);
            }

            $params = $parameterMapper($modification);

            return self::make($visitorClass, $params);
        };
    }

    public static function simple(string $visitorType, array $paramKeys): callable
    {
        return function (array $modification) use ($visitorType, $paramKeys) {
            $params = array_map(fn ($key) => $modification[$key] ?? null, $paramKeys);

            return self::make($visitorType, $params);
        };
    }
}

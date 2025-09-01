<?php

namespace Malico\PhpSculptor;

use PhpParser\NodeVisitorAbstract;

class VisitorFactory
{
    /**
     * Create a visitor instance with one line of code
     *
     * Example: VisitorFactory::make('ChangeProperty', ['name', 'newValue'])
     */
    public static function make(string $visitorType, array $params = []): NodeVisitorAbstract
    {
        $className = sprintf('Malico\PhpSculptor\Visitors\%sVisitor', $visitorType);

        if (! class_exists($className)) {
            throw new \InvalidArgumentException('Visitor class does not exist: '.$className);
        }

        return new $className(...$params);
    }

    /**
     * Register a custom visitor with Sculptor
     *
     * Example:
     * $sculptor->addVisitor('my_custom_operation',
     *     fn($data) => VisitorFactory::make('MyCustom', [$data['param1'], $data['param2']])
     * );
     */
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

    /**
     * Quick method to create a visitor factory for simple cases
     */
    public static function simple(string $visitorType, array $paramKeys): callable
    {
        return function (array $modification) use ($visitorType, $paramKeys) {
            $params = array_map(fn ($key) => $modification[$key] ?? null, $paramKeys);

            return self::make($visitorType, $params);
        };
    }
}

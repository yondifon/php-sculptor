<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ExtendArrayPropertyModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName,
        private readonly array $additions
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Property)) {
            return null;
        }

        $targetProperty = $this->findTargetProperty($node);

        if (! $targetProperty instanceof \PhpParser\Node\PropertyItem) {
            return null;
        }

        $this->extendPropertyArray($targetProperty);

        return $node;
    }

    private function findTargetProperty(Node\Stmt\Property $property): ?Node\PropertyItem
    {
        foreach ($property->props as $prop) {
            if ($prop->name->toString() === $this->propertyName) {
                return $prop;
            }
        }

        return null;
    }

    private function extendPropertyArray(Node\PropertyItem $property): void
    {
        if (! $property->default instanceof Node\Expr\Array_) {
            $property->default = new Node\Expr\Array_;
        }

        $existingValues = [];

        foreach ($property->default->items as $item) {
            if ($item && $item->value instanceof Node\Scalar\String_) {
                $existingValues[] = $item->value->value;
            }
        }

        foreach ($this->additions as $key => $value) {
            if (is_int($key)) {
                $this->addNumericArrayItem($property, $value, $existingValues);

                continue;
            }

            $this->addAssociativeArrayItem($property, $key, $value);
        }
    }

    private function parseValue(mixed $value): Node\Expr
    {
        return match (true) {
            is_string($value) => new Node\Scalar\String_($value),
            is_int($value) => new Node\Scalar\Int_($value),
            is_float($value) => new Node\Scalar\Float_($value),
            is_bool($value) => new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false')),
            is_null($value) => new Node\Expr\ConstFetch(new Node\Name('null')),
            is_array($value) => new Node\Expr\Array_(
                array_map(fn ($v, $k) => new Node\Expr\ArrayItem(
                    $this->parseValue($v),
                    is_string($k) ? new Node\Scalar\String_($k) : null
                ), $value, array_keys($value))
            ),
            default => new Node\Expr\ConstFetch(new Node\Name('null')),
        };
    }

    private function addNumericArrayItem(Node\PropertyItem $property, string $value, array $existingValues): void
    {
        if (in_array($value, $existingValues)) {
            return;
        }

        $property->default->items[] = new Node\Expr\ArrayItem(
            new Node\Scalar\String_($value)
        );
    }

    private function addAssociativeArrayItem(Node\PropertyItem $property, string $key, mixed $value): void
    {
        $existingItem = $this->findExistingAssociativeItem($property, $key);

        if ($existingItem instanceof \PhpParser\Node\Expr\ArrayItem) {
            $existingItem->value = $this->parseValue($value);

            return;
        }

        $property->default->items[] = new Node\Expr\ArrayItem(
            $this->parseValue($value),
            new Node\Scalar\String_($key)
        );
    }

    private function findExistingAssociativeItem(Node\PropertyItem $property, string $key): ?Node\Expr\ArrayItem
    {
        foreach ($property->default->items as $item) {
            if (! $item) {
                continue;
            }

            if (! ($item->key instanceof Node\Scalar\String_)) {
                continue;
            }

            if ($item->key->value === $key) {
                return $item;
            }
        }

        return null;
    }
}

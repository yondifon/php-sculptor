<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ExtendArrayPropertyVisitor extends NodeVisitorAbstract
{
    private string $propertyName;
    private array $additions;

    public function __construct(string $propertyName, array $additions)
    {
        $this->propertyName = $propertyName;
        $this->additions = $additions;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Property) {
            // Check if this is the property we want to modify
            foreach ($node->props as $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    // Found the property, extend its array value
                    $this->extendPropertyArray($prop);
                    return $node;
                }
            }
        }

        return null;
    }

    private function extendPropertyArray(Node\PropertyItem $property): void
    {
        if (!$property->default instanceof Node\Expr\Array_) {
            // Property is not an array, convert it to one or skip
            $property->default = new Node\Expr\Array_();
        }

        $existingValues = [];
        
        // Collect existing values to avoid duplicates
        foreach ($property->default->items as $item) {
            if ($item && $item->value instanceof Node\Scalar\String_) {
                $existingValues[] = $item->value->value;
            }
        }

        // Add new items that don't already exist
        foreach ($this->additions as $key => $value) {
            if (is_int($key)) {
                // Numeric key, treat as array value
                if (!in_array($value, $existingValues)) {
                    $property->default->items[] = new Node\Expr\ArrayItem(
                        new Node\Scalar\String_($value)
                    );
                }
            } else {
                // Associative array
                $found = false;
                foreach ($property->default->items as $item) {
                    if ($item && 
                        $item->key instanceof Node\Scalar\String_ && 
                        $item->key->value === $key) {
                        // Update existing key
                        $item->value = $this->parseValue($value);
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    // Add new key-value pair
                    $property->default->items[] = new Node\Expr\ArrayItem(
                        $this->parseValue($value),
                        new Node\Scalar\String_($key)
                    );
                }
            }
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
                array_map(fn($v, $k) => new Node\Expr\ArrayItem(
                    $this->parseValue($v),
                    is_string($k) ? new Node\Scalar\String_($k) : null
                ), $value, array_keys($value))
            ),
            default => new Node\Expr\ConstFetch(new Node\Name('null')),
        };
    }
}
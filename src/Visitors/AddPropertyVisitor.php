<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddPropertyVisitor extends NodeVisitorAbstract
{
    private string $propertyName;
    private mixed $default;
    private string $visibility;
    private ?string $type;

    public function __construct(string $propertyName, mixed $default = null, string $visibility = 'protected', ?string $type = null)
    {
        $this->propertyName = $propertyName;
        $this->default = $default;
        $this->visibility = $visibility;
        $this->type = $type;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            // Check if property already exists
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Property) {
                    foreach ($stmt->props as $prop) {
                        if ($prop->name->toString() === $this->propertyName) {
                            // Property already exists, skip
                            return $node;
                        }
                    }
                }
            }

            // Create the new property
            $property = $this->createProperty();
            
            // Find the best position to insert the property
            $insertIndex = $this->findPropertyInsertPosition($node->stmts);
            
            // Insert the property
            array_splice($node->stmts, $insertIndex, 0, [$property]);
            
            return $node;
        }

        return null;
    }

    private function createProperty(): Node\Stmt\Property
    {
        // Convert visibility string to flag
        $flags = match ($this->visibility) {
            'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
            'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
            'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
            default => Node\Stmt\Class_::MODIFIER_PROTECTED,
        };

        // Create property item
        $propertyItem = new Node\PropertyItem(
            $this->propertyName,
            $this->default !== null ? $this->parseValue($this->default) : null
        );

        // Create the property statement
        $property = new Node\Stmt\Property(
            $flags,
            [$propertyItem]
        );

        // Add type if specified
        if ($this->type) {
            $property->type = new Node\Identifier($this->type);
        }

        return $property;
    }

    private function findPropertyInsertPosition(array $stmts): int
    {
        $lastTraitIndex = -1;
        $lastPropertyIndex = -1;
        $firstMethodIndex = -1;

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                $lastTraitIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Property) {
                $lastPropertyIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\ClassMethod && $firstMethodIndex === -1) {
                $firstMethodIndex = $index;
            }
        }

        // Insert after the last existing property
        if ($lastPropertyIndex !== -1) {
            return $lastPropertyIndex + 1;
        }

        // Insert after traits
        if ($lastTraitIndex !== -1) {
            return $lastTraitIndex + 1;
        }

        // Insert before the first method
        if ($firstMethodIndex !== -1) {
            return $firstMethodIndex;
        }

        // Insert at the beginning
        return 0;
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
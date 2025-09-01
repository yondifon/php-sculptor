<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddPropertyVisitor extends NodeVisitorAbstract
{
    private const NO_DEFAULT = '___NO_DEFAULT___';

    public function __construct(
        private readonly string $propertyName,
        private readonly mixed $default = self::NO_DEFAULT,
        private readonly string $visibility = 'protected',
        private readonly ?string $type = null
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        if ($this->propertyAlreadyExists($node)) {
            return $node;
        }

        $this->addPropertyToClass($node);

        return $node;
    }

    private function propertyAlreadyExists(Node\Stmt\Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addPropertyToClass(Node\Stmt\Class_ $class): void
    {
        $property = $this->createProperty();
        $insertIndex = $this->findPropertyInsertPosition($class->stmts);
        array_splice($class->stmts, $insertIndex, 0, [$property]);
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

        // Create property item - only add default if one was explicitly provided
        $default = $this->createDefaultValue();

        $propertyItem = new Node\PropertyItem(
            $this->propertyName,
            $default
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
        $positions = $this->analyzeStatementPositions($stmts);

        if ($positions['lastProperty'] !== -1) {
            return $positions['lastProperty'] + 1;
        }

        if ($positions['lastTrait'] !== -1) {
            return $positions['lastTrait'] + 1;
        }

        if ($positions['firstMethod'] !== -1) {
            return $positions['firstMethod'];
        }

        return 0;
    }

    private function analyzeStatementPositions(array $stmts): array
    {
        $positions = [
            'lastTrait' => -1,
            'lastProperty' => -1,
            'firstMethod' => -1,
        ];

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                $positions['lastTrait'] = $index;

                continue;
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $positions['lastProperty'] = $index;

                continue;
            }

            if ($stmt instanceof Node\Stmt\ClassMethod && $positions['firstMethod'] === -1) {
                $positions['firstMethod'] = $index;
            }
        }

        return $positions;
    }

    private function createDefaultValue(): ?Node\Expr
    {
        if ($this->default === self::NO_DEFAULT) {
            return null;
        }

        return $this->parseValue($this->default);
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
}

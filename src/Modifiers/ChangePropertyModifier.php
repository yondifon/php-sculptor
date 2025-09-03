<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName,
        private readonly mixed $newDefault = null,
        private readonly ?string $newVisibility = null,
        private readonly ?string $newType = null
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $this->updateClassProperties($node);

        return $node;
    }

    private function updateClassProperties(Node\Stmt\Class_ $class): void
    {
        foreach ($class->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            $targetProperty = $this->findTargetProperty($stmt);

            if (! $targetProperty instanceof \PhpParser\Node\PropertyItem) {
                continue;
            }

            $this->updateProperty($stmt, $targetProperty);

            return;
        }
    }

    private function findTargetProperty(Node\Stmt\Property $propertyStatement): ?Node\PropertyItem
    {
        foreach ($propertyStatement->props as $prop) {
            if ($prop->name->toString() === $this->propertyName) {
                return $prop;
            }
        }

        return null;
    }

    private function updateProperty(Node\Stmt\Property $propertyStatement, Node\PropertyItem $property): void
    {
        if ($this->newDefault !== null) {
            $property->default = $this->parseValue($this->newDefault);
        }

        if ($this->newVisibility !== null) {
            $propertyStatement->flags = $this->getVisibilityFlag();
        }

        if ($this->newType !== null) {
            $propertyStatement->type = new Node\Identifier($this->newType);
        }
    }

    private function getVisibilityFlag(): int
    {
        return match ($this->newVisibility) {
            'private' => \PhpParser\Modifiers::PRIVATE,
            'protected' => \PhpParser\Modifiers::PROTECTED,
            'public' => \PhpParser\Modifiers::PUBLIC,
            default => \PhpParser\Modifiers::PROTECTED,
        };
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
                array_map(fn ($v) => new Node\Expr\ArrayItem($this->parseValue($v)), $value)
            ),
            default => new Node\Expr\ConstFetch(new Node\Name('null')),
        };
    }
}

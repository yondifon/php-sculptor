<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyDefaultModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName,
        private readonly mixed $newDefault
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    $prop->default = $this->parseValue($this->newDefault);

                    return $node;
                }
            }
        }

        return $node;
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

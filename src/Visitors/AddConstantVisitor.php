<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddConstantVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $constantName,
        private readonly mixed $value,
        private readonly string $visibility = 'public'
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        // Check if constant already exists
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $const) {
                    if ($const->name->toString() === $this->constantName) {
                        // Constant already exists, skip
                        return $node;
                    }
                }
            }
        }

        // Create the new constant
        $flags = match ($this->visibility) {
            'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
            'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
            'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
            default => Node\Stmt\Class_::MODIFIER_PUBLIC,
        };

        $constant = new Node\Stmt\ClassConst([
            new Node\Const_($this->constantName, $this->parseValue($this->value)),
        ], $flags);

        // Add to the beginning of class statements (constants typically go first)
        array_unshift($node->stmts, $constant);

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

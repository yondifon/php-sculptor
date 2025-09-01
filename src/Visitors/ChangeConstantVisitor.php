<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeConstantVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $constantName,
        private readonly mixed $newValue,
        private readonly ?string $newVisibility = null
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $const) {
                    if ($const->name->toString() === $this->constantName) {
                        // Update value
                        $const->value = $this->parseValue($this->newValue);

                        // Update visibility if provided
                        if ($this->newVisibility !== null) {
                            $stmt->flags = match ($this->newVisibility) {
                                'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
                                'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
                                'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                default => $stmt->flags,
                            };
                        }

                        return $node;
                    }
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

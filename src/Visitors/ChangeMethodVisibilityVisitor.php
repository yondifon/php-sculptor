<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeMethodVisibilityVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $methodName,
        private readonly string $newVisibility
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                $stmt->flags = match ($this->newVisibility) {
                    'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
                    'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
                    'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                    default => $stmt->flags,
                };

                return $node;
            }
        }

        return $node;
    }
}

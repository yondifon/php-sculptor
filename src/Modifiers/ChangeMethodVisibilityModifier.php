<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeMethodVisibilityModifier extends NodeVisitorAbstract
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
                    'private' => \PhpParser\Modifiers::PRIVATE,
                    'protected' => \PhpParser\Modifiers::PROTECTED,
                    'public' => \PhpParser\Modifiers::PUBLIC,
                    default => $stmt->flags,
                };

                return $node;
            }
        }

        return $node;
    }
}

<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveMethodModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $methodName
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $methodIndex = $this->findMethodIndex($node);

        if ($methodIndex === -1) {
            return $node;
        }

        $this->removeMethodFromClass($node, $methodIndex);

        return $node;
    }

    private function findMethodIndex(Node\Stmt\Class_ $class): int
    {
        foreach ($class->stmts as $key => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                return $key;
            }
        }

        return -1;
    }

    private function removeMethodFromClass(Node\Stmt\Class_ $class, int $methodIndex): void
    {
        unset($class->stmts[$methodIndex]);
        $class->stmts = array_values($class->stmts);
    }
}

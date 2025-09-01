<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyTypeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName,
        private readonly string $newType
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $targetPropertyStatement = $this->findTargetPropertyStatement($node);

        if (! $targetPropertyStatement instanceof \PhpParser\Node\Stmt\Property) {
            return $node;
        }

        $targetPropertyStatement->type = new Node\Identifier($this->newType);

        return $node;
    }

    private function findTargetPropertyStatement(Node\Stmt\Class_ $class): ?Node\Stmt\Property
    {
        foreach ($class->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    return $stmt;
                }
            }
        }

        return null;
    }
}

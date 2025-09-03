<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemovePropertyModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $key => $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $propKey => $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    if (count($stmt->props) === 1) {
                        unset($node->stmts[$key]);
                        $node->stmts = array_values($node->stmts);

                        return $node;
                    }

                    unset($stmt->props[$propKey]);
                    $stmt->props = array_values($stmt->props);

                    return $node;
                }
            }
        }

        return $node;
    }
}

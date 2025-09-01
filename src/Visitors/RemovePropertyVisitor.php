<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemovePropertyVisitor extends NodeVisitorAbstract
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

            // Check each property in this property statement
            foreach ($stmt->props as $propKey => $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    // If there's only one property in this statement, remove the entire statement
                    if (count($stmt->props) === 1) {
                        unset($node->stmts[$key]);
                        // Reindex array to prevent gaps
                        $node->stmts = array_values($node->stmts);
                    } else {
                        // Remove just this property from the statement
                        unset($stmt->props[$propKey]);
                        $stmt->props = array_values($stmt->props);
                    }

                    return $node;
                }
            }
        }

        return $node;
    }
}

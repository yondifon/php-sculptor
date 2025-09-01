<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveConstantVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $constantName
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $constKey => $const) {
                    if ($const->name->toString() === $this->constantName) {
                        // If there's only one constant in this statement, remove the entire statement
                        if (count($stmt->consts) === 1) {
                            unset($node->stmts[$key]);
                            // Reindex array to prevent gaps
                            $node->stmts = array_values($node->stmts);
                        } else {
                            // Remove just this constant from the statement
                            unset($stmt->consts[$constKey]);
                            $stmt->consts = array_values($stmt->consts);
                        }

                        return $node;
                    }
                }
            }
        }

        return $node;
    }
}

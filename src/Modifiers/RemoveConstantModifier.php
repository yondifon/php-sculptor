<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveConstantModifier extends NodeVisitorAbstract
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
                        if (count($stmt->consts) === 1) {
                            unset($node->stmts[$key]);
                            $node->stmts = array_values($node->stmts);

                            return $node;
                        }

                        unset($stmt->consts[$constKey]);
                        $stmt->consts = array_values($stmt->consts);

                        return $node;
                    }
                }
            }
        }

        return $node;
    }
}

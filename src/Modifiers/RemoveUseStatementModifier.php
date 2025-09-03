<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

class RemoveUseStatementModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $className
    ) {}

    public function leaveNode(Node $node): int|Node|null
    {
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $key => $use) {
                if ($use->name->toString() === $this->className) {
                    if (count($node->uses) === 1) {
                        return NodeVisitor::REMOVE_NODE;
                    }

                    unset($node->uses[$key]);
                    $node->uses = array_values($node->uses);

                    return $node;
                }
            }
        }

        return $node;
    }
}

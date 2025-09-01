<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeClassNameVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $newName
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Class_) {
            $node->name = new Node\Identifier($this->newName);

            return $node;
        }

        return null;
    }
}

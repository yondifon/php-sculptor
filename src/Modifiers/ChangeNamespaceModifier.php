<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeNamespaceModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $newNamespace
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $node->name = new Node\Name($this->newNamespace);

            return $node;
        }

        return null;
    }
}

<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddNamespaceModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $namespace
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        return null;
    }

    public function beforeTraverse(array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                return null;
            }
        }

        $namespaceNode = new Node\Stmt\Namespace_(new Node\Name($this->namespace));

        $insertIndex = 0;
        foreach ($nodes as $index => $node) {
            if ($node instanceof Node\Stmt\InlineHTML) {
                $insertIndex = $index + 1;

                continue;
            }

            break;
        }

        array_splice($nodes, $insertIndex, 0, [$namespaceNode]);

        return $nodes;
    }
}

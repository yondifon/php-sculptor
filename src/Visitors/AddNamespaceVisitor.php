<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddNamespaceVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $namespace
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        // This visitor works at the file level, not class level
        return null;
    }

    public function beforeTraverse(array $nodes): ?array
    {
        // Check if namespace already exists
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                // Namespace already exists
                return null;
            }
        }

        // Add namespace at the beginning after opening PHP tag
        $namespaceNode = new Node\Stmt\Namespace_(new Node\Name($this->namespace));

        // Find where to insert the namespace (after opening tag, before any other statements)
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

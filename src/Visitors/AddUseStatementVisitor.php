<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddUseStatementVisitor extends NodeVisitorAbstract
{
    private string $className;
    private ?string $alias;

    public function __construct(string $className, ?string $alias = null)
    {
        $this->className = $className;
        $this->alias = $alias;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            // Check if use statement already exists
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Use_) {
                    foreach ($stmt->uses as $use) {
                        $useName = $use->name->toString();
                        $useAlias = $use->alias ? $use->alias->toString() : null;
                        
                        if ($useName === $this->className && $useAlias === $this->alias) {
                            // Use statement already exists
                            return $node;
                        }
                    }
                }
            }

            // Find the position to insert the new use statement
            $insertIndex = $this->findUseInsertPosition($node->stmts);
            
            // Create the new use statement
            $useStatement = new Node\Stmt\Use_([
                new Node\Stmt\UseUse(
                    new Node\Name($this->className),
                    $this->alias ? new Node\Identifier($this->alias) : null
                )
            ]);
            
            // Insert the use statement
            array_splice($node->stmts, $insertIndex, 0, [$useStatement]);
            
            return $node;
        }

        return null;
    }

    private function findUseInsertPosition(array $stmts): int
    {
        $lastUseIndex = -1;
        $firstClassIndex = -1;

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                $lastUseIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Class_ && $firstClassIndex === -1) {
                $firstClassIndex = $index;
            }
        }

        // Insert after the last existing use statement
        if ($lastUseIndex !== -1) {
            return $lastUseIndex + 1;
        }

        // Insert before the first class
        if ($firstClassIndex !== -1) {
            return $firstClassIndex;
        }

        // Insert at the beginning
        return 0;
    }
}
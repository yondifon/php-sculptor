<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddTraitVisitor extends NodeVisitorAbstract
{
    private string $traitName;

    public function __construct(string $traitName)
    {
        $this->traitName = $traitName;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            // Check if trait already exists
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        if ($trait->toString() === $this->traitName) {
                            // Trait already exists, skip
                            return $node;
                        }
                    }
                }
            }

            // Add new trait use statement
            $traitUse = new Node\Stmt\TraitUse([
                new Node\Name($this->traitName)
            ]);

            // Find the best position to insert the trait
            $insertIndex = $this->findTraitInsertPosition($node->stmts);
            
            // Insert the trait use statement
            array_splice($node->stmts, $insertIndex, 0, [$traitUse]);
            
            return $node;
        }

        return null;
    }

    private function findTraitInsertPosition(array $stmts): int
    {
        $lastTraitIndex = -1;
        $firstPropertyIndex = -1;
        $firstMethodIndex = -1;

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse && $lastTraitIndex === -1) {
                $lastTraitIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Property && $firstPropertyIndex === -1) {
                $firstPropertyIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\ClassMethod && $firstMethodIndex === -1) {
                $firstMethodIndex = $index;
            }
        }

        // Insert after the last existing trait
        if ($lastTraitIndex !== -1) {
            return $lastTraitIndex + 1;
        }

        // Insert before the first property
        if ($firstPropertyIndex !== -1) {
            return $firstPropertyIndex;
        }

        // Insert before the first method
        if ($firstMethodIndex !== -1) {
            return $firstMethodIndex;
        }

        // Insert at the beginning if no traits, properties, or methods exist
        return 0;
    }
}
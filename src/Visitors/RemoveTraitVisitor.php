<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class RemoveTraitVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $traitName
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $this->removeTraitFromClass($node);

        return $node;
    }

    private function removeTraitFromClass(Node\Stmt\Class_ $class): void
    {
        foreach ($class->stmts as $statementIndex => $stmt) {
            if (! ($stmt instanceof Node\Stmt\TraitUse)) {
                continue;
            }

            $traitIndex = $this->findTraitIndex($stmt);

            if ($traitIndex === -1) {
                continue;
            }

            $this->handleTraitRemoval($class, $stmt, $statementIndex, $traitIndex);

            return;
        }
    }

    private function findTraitIndex(Node\Stmt\TraitUse $traitUse): int
    {
        foreach ($traitUse->traits as $traitKey => $trait) {
            if ($trait->toString() === $this->traitName) {
                return $traitKey;
            }
        }

        return -1;
    }

    private function handleTraitRemoval(Node\Stmt\Class_ $class, Node\Stmt\TraitUse $traitUse, int $statementIndex, int $traitIndex): void
    {
        if (count($traitUse->traits) === 1) {
            $this->removeEntireTraitStatement($class, $statementIndex);

            return;
        }

        $this->removeTraitFromStatement($traitUse, $traitIndex);
    }

    private function removeEntireTraitStatement(Node\Stmt\Class_ $class, int $statementIndex): void
    {
        unset($class->stmts[$statementIndex]);
        $class->stmts = array_values($class->stmts);
    }

    private function removeTraitFromStatement(Node\Stmt\TraitUse $traitUse, int $traitIndex): void
    {
        unset($traitUse->traits[$traitIndex]);
        $traitUse->traits = array_values($traitUse->traits);
    }
}

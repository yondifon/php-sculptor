<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddTraitModifier extends NodeVisitorAbstract
{
    public function __construct(private readonly string $traitName) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        if ($this->traitAlreadyExists($node)) {
            return $node;
        }

        $this->addTraitToClass($node);

        return $node;
    }

    private function traitAlreadyExists(Node\Stmt\Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\TraitUse)) {
                continue;
            }

            foreach ($stmt->traits as $trait) {
                if ($trait->toString() === $this->traitName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addTraitToClass(Node\Stmt\Class_ $class): void
    {
        $traitUse = new Node\Stmt\TraitUse([
            new Node\Name($this->traitName),
        ]);

        $insertIndex = $this->findTraitInsertPosition($class->stmts);
        array_splice($class->stmts, $insertIndex, 0, [$traitUse]);
    }

    private function findTraitInsertPosition(array $stmts): int
    {
        $positions = $this->analyzeStatementPositions($stmts);

        if ($positions['lastTrait'] !== -1) {
            return $positions['lastTrait'] + 1;
        }

        if ($positions['firstProperty'] !== -1) {
            return $positions['firstProperty'];
        }

        if ($positions['firstMethod'] !== -1) {
            return $positions['firstMethod'];
        }

        return 0;
    }

    private function analyzeStatementPositions(array $stmts): array
    {
        $positions = [
            'lastTrait' => -1,
            'firstProperty' => -1,
            'firstMethod' => -1,
        ];

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                $positions['lastTrait'] = $index;

                continue;
            }

            if ($stmt instanceof Node\Stmt\Property && $positions['firstProperty'] === -1) {
                $positions['firstProperty'] = $index;

                continue;
            }

            if ($stmt instanceof Node\Stmt\ClassMethod && $positions['firstMethod'] === -1) {
                $positions['firstMethod'] = $index;
            }
        }

        return $positions;
    }
}

<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AddUseStatementModifier extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $className,
        private readonly ?string $alias = null
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Namespace_)) {
            return null;
        }

        if ($this->useStatementAlreadyExists($node)) {
            return $node;
        }

        $this->addUseStatementToNamespace($node);

        return $node;
    }

    private function useStatementAlreadyExists(Node\Stmt\Namespace_ $namespace): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Use_)) {
                continue;
            }

            foreach ($stmt->uses as $use) {
                $useName = $use->name->toString();
                $useAlias = $use->alias?->toString();

                if ($useName === $this->className && $useAlias === $this->alias) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addUseStatementToNamespace(Node\Stmt\Namespace_ $namespace): void
    {
        $insertIndex = $this->findUseInsertPosition($namespace->stmts);
        $useStatement = $this->createUseStatement();
        array_splice($namespace->stmts, $insertIndex, 0, [$useStatement]);
    }

    private function createUseStatement(): Node\Stmt\Use_
    {
        return new Node\Stmt\Use_([
            new Node\Stmt\UseUse(
                new Node\Name($this->className),
                $this->alias ? new Node\Identifier($this->alias) : null
            ),
        ]);
    }

    private function findUseInsertPosition(array $stmts): int
    {
        $positions = $this->analyzeStatementPositions($stmts);

        if ($positions['lastUse'] !== -1) {
            return $positions['lastUse'] + 1;
        }

        if ($positions['firstClass'] !== -1) {
            return $positions['firstClass'];
        }

        return 0;
    }

    private function analyzeStatementPositions(array $stmts): array
    {
        $positions = [
            'lastUse' => -1,
            'firstClass' => -1,
        ];

        foreach ($stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                $positions['lastUse'] = $index;

                continue;
            }

            if ($stmt instanceof Node\Stmt\Class_ && $positions['firstClass'] === -1) {
                $positions['firstClass'] = $index;
            }
        }

        return $positions;
    }
}

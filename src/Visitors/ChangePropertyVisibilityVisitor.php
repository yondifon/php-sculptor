<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangePropertyVisibilityVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $propertyName,
        private readonly string $newVisibility
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if (! ($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                if ($prop->name->toString() === $this->propertyName) {
                    $stmt->flags = match ($this->newVisibility) {
                        'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
                        'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
                        'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                        default => $stmt->flags,
                    };

                    return $node;
                }
            }
        }

        return $node;
    }
}

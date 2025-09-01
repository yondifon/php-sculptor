<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeMethodVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly string $methodName,
        private readonly ?array $parameters = null,
        private readonly ?string $body = null,
        private readonly ?string $visibility = null
    ) {}

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                // Update visibility if provided
                if ($this->visibility !== null) {
                    $stmt->flags = match ($this->visibility) {
                        'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
                        'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
                        'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                        default => $stmt->flags,
                    };
                }

                // Update parameters if provided
                if ($this->parameters !== null) {
                    // This is simplified - would need full parameter parsing like AddMethodVisitor
                    // For now, just indicate the method was found
                }

                // Update body if provided
                if ($this->body !== null) {
                    // This would need proper body parsing like AddMethodVisitor
                    // For now, just indicate the method was found
                }

                return $node;
            }
        }

        return $node;
    }
}

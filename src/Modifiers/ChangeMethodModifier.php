<?php

namespace Malico\PhpSculptor\Modifiers;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ChangeMethodModifier extends NodeVisitorAbstract
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
            if (! ($stmt instanceof Node\Stmt\ClassMethod)) {
                continue;
            }
            if ($stmt->name->toString() !== $this->methodName) {
                continue;
            }
            if ($this->visibility !== null) {
                $stmt->flags = match ($this->visibility) {
                    'private' => \PhpParser\Modifiers::PRIVATE,
                    'protected' => \PhpParser\Modifiers::PROTECTED,
                    'public' => \PhpParser\Modifiers::PUBLIC,
                    default => $stmt->flags,
                };
            }

            if ($this->parameters !== null) {
                $stmt->params = $this->buildParameterNodes();
            }

            if ($this->body !== null) {
                $stmt->stmts = $this->buildBodyStatements();
            }

            return $node;
        }

        return $node;
    }

    private function buildParameterNodes(): array
    {
        $params = [];

        foreach ($this->parameters as $param) {
            if (is_string($param)) {
                $params[] = new Node\Param(new Node\Expr\Variable($param));

                continue;
            }

            if (is_array($param)) {
                $paramNode = new Node\Param(
                    new Node\Expr\Variable($param['name']),
                    $param['default'] ?? null,
                    $param['type'] ? new Node\Identifier($param['type']) : null
                );
                $params[] = $paramNode;
            }
        }

        return $params;
    }

    private function buildBodyStatements(): array
    {
        try {
            $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion();
            $statements = $parser->parse("<?php {$this->body}");

            return $statements ?? [];
        } catch (\Exception) {
            return [new Node\Stmt\Expression(new Node\Scalar\String_($this->body))];
        }
    }
}

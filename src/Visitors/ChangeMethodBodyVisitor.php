<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class ChangeMethodBodyVisitor extends NodeVisitorAbstract
{
    private readonly Parser $parser;

    public function __construct(
        private readonly string $methodName,
        private readonly string $newBody
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $targetMethod = $this->findTargetMethod($node);

        if (! $targetMethod instanceof \PhpParser\Node\Stmt\ClassMethod) {
            return $node;
        }

        $this->updateMethodBody($targetMethod);

        return $node;
    }

    private function findTargetMethod(Node\Stmt\Class_ $class): ?Node\Stmt\ClassMethod
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                return $stmt;
            }
        }

        return null;
    }

    private function updateMethodBody(Node\Stmt\ClassMethod $method): void
    {
        $method->stmts = $this->parseMethodBody();
    }

    private function parseMethodBody(): array
    {
        if ($this->newBody === '') {
            return [];
        }

        try {
            $code = '<?php '.$this->newBody;
            $parsed = $this->parser->parse($code);

            return $parsed ?: [];
        } catch (\Throwable) {
            return $this->createParsingFailedStatement();
        }
    }

    private function createParsingFailedStatement(): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('Exception'),
                        [new Node\Arg(new Node\Scalar\String_('Method body parsing failed'))]
                    )
                )
            ),
        ];
    }
}

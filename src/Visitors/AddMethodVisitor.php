<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class AddMethodVisitor extends NodeVisitorAbstract
{
    private readonly Parser $parser;

    public function __construct(
        private readonly string $methodName,
        private readonly array $parameters = [],
        private readonly string $body = '',
        private readonly string $visibility = 'public',
        private readonly bool $override = false
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function leaveNode(Node $node): ?Node
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return null;
        }

        $existingMethodIndex = $this->findExistingMethodIndex($node);

        if ($existingMethodIndex !== -1) {
            return $this->handleExistingMethod($node, $existingMethodIndex);
        }

        return $this->addNewMethod($node);
    }

    private function findExistingMethodIndex(Node\Stmt\Class_ $class): int
    {
        foreach ($class->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                return $index;
            }
        }

        return -1;
    }

    private function handleExistingMethod(Node\Stmt\Class_ $class, int $index): Node\Stmt\Class_
    {
        if (! $this->override) {
            return $class;
        }

        $class->stmts[$index] = $this->createMethod();

        return $class;
    }

    private function addNewMethod(Node\Stmt\Class_ $class): Node\Stmt\Class_
    {
        $method = $this->createMethod();
        $class->stmts[] = $method;

        return $class;
    }

    private function createMethod(): Node\Stmt\ClassMethod
    {
        // Convert visibility string to flag
        $flags = match ($this->visibility) {
            'private' => Node\Stmt\Class_::MODIFIER_PRIVATE,
            'protected' => Node\Stmt\Class_::MODIFIER_PROTECTED,
            'public' => Node\Stmt\Class_::MODIFIER_PUBLIC,
            default => Node\Stmt\Class_::MODIFIER_PUBLIC,
        };

        // Build parameters
        $params = [];
        foreach ($this->parameters as $param) {
            $params[] = $this->createParameter($param);
        }

        // Parse method body
        $stmts = $this->parseMethodBody();

        return new Node\Stmt\ClassMethod(
            $this->methodName,
            [
                'flags' => $flags,
                'params' => $params,
                'stmts' => $stmts,
            ]
        );
    }

    private function parseValue(mixed $value): Node\Expr
    {
        return match (true) {
            is_string($value) => new Node\Scalar\String_($value),
            is_int($value) => new Node\Scalar\Int_($value),
            is_float($value) => new Node\Scalar\Float_($value),
            is_bool($value) => new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false')),
            is_null($value) => new Node\Expr\ConstFetch(new Node\Name('null')),
            is_array($value) => new Node\Expr\Array_(
                array_map(fn ($v) => new Node\Expr\ArrayItem($this->parseValue($v)), $value)
            ),
            default => new Node\Expr\ConstFetch(new Node\Name('null')),
        };
    }

    private function createParameter(mixed $param): Node\Param
    {
        if (is_string($param)) {
            return new Node\Param(new Node\Expr\Variable($param));
        }

        if (! is_array($param)) {
            return new Node\Param(new Node\Expr\Variable('param'));
        }

        $paramNode = new Node\Param(new Node\Expr\Variable($param['name']));

        if (isset($param['type'])) {
            $paramNode->type = new Node\Identifier($param['type']);
        }

        if (isset($param['default'])) {
            $paramNode->default = $this->parseValue($param['default']);
        }

        return $paramNode;
    }

    private function parseMethodBody(): array
    {
        if ($this->body === '') {
            return [];
        }

        try {
            $code = '<?php '.$this->body;
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
                        [new Node\Arg(new Node\Scalar\String_('Method not implemented: parsing failed'))]
                    )
                )
            ),
        ];
    }
}

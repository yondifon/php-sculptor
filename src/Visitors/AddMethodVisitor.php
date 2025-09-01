<?php

namespace Malico\PhpSculptor\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class AddMethodVisitor extends NodeVisitorAbstract
{
    private string $methodName;
    private array $parameters;
    private string $body;
    private string $visibility;
    private Parser $parser;

    public function __construct(string $methodName, array $parameters = [], string $body = '', string $visibility = 'public')
    {
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->body = $body;
        $this->visibility = $visibility;
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            // Check if method already exists
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $this->methodName) {
                    // Method already exists, skip
                    return $node;
                }
            }

            // Create the new method
            $method = $this->createMethod();
            
            // Add to the end of class statements
            $node->stmts[] = $method;
            
            return $node;
        }

        return null;
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
            if (is_string($param)) {
                // Simple parameter name
                $params[] = new Node\Param(new Node\Expr\Variable($param));
            } elseif (is_array($param)) {
                // Parameter with type and/or default value
                $paramNode = new Node\Param(new Node\Expr\Variable($param['name']));
                
                if (isset($param['type'])) {
                    $paramNode->type = new Node\Identifier($param['type']);
                }
                
                if (isset($param['default'])) {
                    $paramNode->default = $this->parseValue($param['default']);
                }
                
                $params[] = $paramNode;
            }
        }

        // Parse method body
        $stmts = [];
        if (!empty($this->body)) {
            try {
                // Wrap in PHP tags for parsing
                $code = "<?php {$this->body}";
                $parsed = $this->parser->parse($code);
                $stmts = $parsed ?: [];
            } catch (\Exception $e) {
                // If parsing fails, create a simple statement
                $stmts = [
                    new Node\Stmt\Expression(
                        new Node\Expr\Throw_(
                            new Node\Expr\New_(
                                new Node\Name('Exception'),
                                [new Node\Arg(new Node\Scalar\String_('Method not implemented'))]
                            )
                        )
                    )
                ];
            }
        }

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
                array_map(fn($v) => new Node\Expr\ArrayItem($this->parseValue($v)), $value)
            ),
            default => new Node\Expr\ConstFetch(new Node\Name('null')),
        };
    }
}
<?php

namespace Malico\PhpSculptor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Sculptor
{
    private Parser $parser;
    private NodeTraverser $traverser;
    private Standard $printer;
    private string $filePath;
    private ?string $originalCode = null;
    private array $ast = [];
    private array $pendingModifications = [];

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser();
        $this->printer = new Standard();
        
        $this->loadFile();
    }

    public static function make(string $filePath): self
    {
        return new self($filePath);
    }

    private function loadFile(): void
    {
        if (!file_exists($this->filePath)) {
            throw new \InvalidArgumentException("File not found: {$this->filePath}");
        }

        $this->originalCode = file_get_contents($this->filePath);
        $this->ast = $this->parser->parse($this->originalCode);
    }

    public function addTrait(string $trait): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_trait',
            'trait' => $trait,
        ];
        
        return $this;
    }

    public function addMethod(string $name, array $parameters = [], string $body = '', string $visibility = 'public'): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_method',
            'name' => $name,
            'parameters' => $parameters,
            'body' => $body,
            'visibility' => $visibility,
        ];
        
        return $this;
    }

    public function addProperty(string $name, mixed $default = null, string $visibility = 'protected', ?string $type = null): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_property',
            'name' => $name,
            'default' => $default,
            'visibility' => $visibility,
            'type' => $type,
        ];
        
        return $this;
    }

    public function implementInterface(string $interface): self
    {
        $this->pendingModifications[] = [
            'type' => 'implement_interface',
            'interface' => $interface,
        ];
        
        return $this;
    }

    public function extendClass(string $parentClass): self
    {
        $this->pendingModifications[] = [
            'type' => 'extend_class',
            'parent' => $parentClass,
        ];
        
        return $this;
    }

    public function extendArrayProperty(string $property, array $additions): self
    {
        $this->pendingModifications[] = [
            'type' => 'extend_array_property',
            'property' => $property,
            'additions' => $additions,
        ];
        
        return $this;
    }

    public function addUseStatement(string $class, ?string $alias = null): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_use_statement',
            'class' => $class,
            'alias' => $alias,
        ];
        
        return $this;
    }

    public function addToFillable(array $fields): self
    {
        return $this->extendArrayProperty('fillable', $fields);
    }

    public function addToCasts(array $casts): self
    {
        return $this->extendArrayProperty('casts', $casts);
    }

    public function addToHidden(array $fields): self
    {
        return $this->extendArrayProperty('hidden', $fields);
    }

    public function hasMethod(string $name): bool
    {
        // Implementation for querying existing methods
        // Will traverse AST to check if method exists
        return false; // Placeholder
    }

    public function hasTrait(string $trait): bool
    {
        // Implementation for querying existing traits
        return false; // Placeholder
    }

    public function save(?string $outputPath = null): self
    {
        $outputPath = $outputPath ?? $this->filePath;
        
        // Apply all pending modifications
        $this->applyModifications();
        
        // Generate modified code
        $modifiedCode = $this->printer->prettyPrintFile($this->ast);
        
        // Write to file
        file_put_contents($outputPath, $modifiedCode);
        
        return $this;
    }

    public function backup(string $backupPath): self
    {
        if ($this->originalCode) {
            file_put_contents($backupPath, $this->originalCode);
        }
        
        return $this;
    }

    public function toString(): string
    {
        // Apply modifications and return as string without saving
        $this->applyModifications();
        return $this->printer->prettyPrintFile($this->ast);
    }

    private function applyModifications(): void
    {
        foreach ($this->pendingModifications as $modification) {
            $this->applyModification($modification);
        }
        
        // Clear pending modifications after applying
        $this->pendingModifications = [];
    }

    private function applyModification(array $modification): void
    {
        $visitor = match ($modification['type']) {
            'add_trait' => new Visitors\AddTraitVisitor($modification['trait']),
            'add_method' => new Visitors\AddMethodVisitor(
                $modification['name'],
                $modification['parameters'],
                $modification['body'],
                $modification['visibility']
            ),
            'add_property' => new Visitors\AddPropertyVisitor(
                $modification['name'],
                $modification['default'],
                $modification['visibility'],
                $modification['type']
            ),
            'extend_array_property' => new Visitors\ExtendArrayPropertyVisitor(
                $modification['property'],
                $modification['additions']
            ),
            'add_use_statement' => new Visitors\AddUseStatementVisitor(
                $modification['class'],
                $modification['alias']
            ),
            default => throw new \InvalidArgumentException("Unknown modification type: {$modification['type']}")
        };

        $this->traverser->addVisitor($visitor);
        $this->ast = $this->traverser->traverse($this->ast);
        $this->traverser->removeVisitor($visitor);
    }
}
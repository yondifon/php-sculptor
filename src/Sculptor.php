<?php

namespace Malico\PhpSculptor;

use InvalidArgumentException;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class Sculptor
{
    private readonly Parser $parser;

    private readonly NodeTraverser $traverser;

    private readonly Standard $printer;

    private ?string $originalCode = null;

    private array $ast = [];

    private array $pendingModifications = [];

    public function __construct(private readonly string $filePath)
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser;
        $this->printer = new Standard;

        $this->loadFile();
    }

    public static function make(string $filePath): self
    {
        return new self($filePath);
    }

    private function loadFile(): void
    {
        if (! file_exists($this->filePath)) {
            throw new InvalidArgumentException('File not found: '.$this->filePath);
        }

        $this->originalCode = file_get_contents($this->filePath);

        if ($this->originalCode === false) {
            throw new InvalidArgumentException('Unable to read file: '.$this->filePath);
        }

        $this->ast = $this->parser->parse($this->originalCode);

        if ($this->ast === null) {
            throw new InvalidArgumentException('Unable to parse PHP file: '.$this->filePath);
        }
    }

    public function addTrait(string $trait): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_trait',
            'trait' => $trait,
        ];

        return $this;
    }

    public function addMethod(string $name, array $parameters = [], string $body = '', string $visibility = 'public', bool $override = false): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_method',
            'name' => $name,
            'parameters' => $parameters,
            'body' => $body,
            'visibility' => $visibility,
            'override' => $override,
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
            'property_type' => $type,
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

    public function changeProperty(string $name, mixed $newDefault = null, ?string $newVisibility = null, ?string $newType = null): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_property',
            'name' => $name,
            'default' => $newDefault,
            'visibility' => $newVisibility,
            'property_type' => $newType,
        ];

        return $this;
    }

    public function changePropertyType(string $name, string $newType): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_property_type',
            'name' => $name,
            'property_type' => $newType,
        ];

        return $this;
    }

    public function changePropertyDefault(string $name, mixed $newDefault): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_property_default',
            'name' => $name,
            'default' => $newDefault,
        ];

        return $this;
    }

    public function changePropertyVisibility(string $name, string $newVisibility): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_property_visibility',
            'name' => $name,
            'visibility' => $newVisibility,
        ];

        return $this;
    }

    public function removeProperty(string $name): self
    {
        $this->pendingModifications[] = [
            'type' => 'remove_property',
            'name' => $name,
        ];

        return $this;
    }

    public function changeMethod(string $name, ?array $parameters = null, ?string $body = null, ?string $visibility = null): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_method',
            'name' => $name,
            'parameters' => $parameters,
            'body' => $body,
            'visibility' => $visibility,
        ];

        return $this;
    }

    public function changeMethodBody(string $name, string $newBody): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_method_body',
            'name' => $name,
            'body' => $newBody,
        ];

        return $this;
    }

    public function changeMethodVisibility(string $name, string $newVisibility): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_method_visibility',
            'name' => $name,
            'visibility' => $newVisibility,
        ];

        return $this;
    }

    public function removeMethod(string $name): self
    {
        $this->pendingModifications[] = [
            'type' => 'remove_method',
            'name' => $name,
        ];

        return $this;
    }

    public function changeClassName(string $newName): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_class_name',
            'name' => $newName,
        ];

        return $this;
    }

    public function addConstant(string $name, mixed $value, string $visibility = 'public'): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_constant',
            'name' => $name,
            'value' => $value,
            'visibility' => $visibility,
        ];

        return $this;
    }

    public function changeConstant(string $name, mixed $newValue, ?string $newVisibility = null): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_constant',
            'name' => $name,
            'value' => $newValue,
            'visibility' => $newVisibility,
        ];

        return $this;
    }

    public function removeConstant(string $name): self
    {
        $this->pendingModifications[] = [
            'type' => 'remove_constant',
            'name' => $name,
        ];

        return $this;
    }

    public function removeTrait(string $trait): self
    {
        $this->pendingModifications[] = [
            'type' => 'remove_trait',
            'trait' => $trait,
        ];

        return $this;
    }

    public function removeUseStatement(string $class): self
    {
        $this->pendingModifications[] = [
            'type' => 'remove_use_statement',
            'class' => $class,
        ];

        return $this;
    }

    public function addNamespace(string $namespace): self
    {
        $this->pendingModifications[] = [
            'type' => 'add_namespace',
            'namespace' => $namespace,
        ];

        return $this;
    }

    public function changeNamespace(string $newNamespace): self
    {
        $this->pendingModifications[] = [
            'type' => 'change_namespace',
            'namespace' => $newNamespace,
        ];

        return $this;
    }

    public function addModifier(string $type, callable|string $modifierFactory): self
    {
        $this->pendingModifications[] = [
            'type' => $type,
            'factory' => $modifierFactory,
        ];

        return $this;
    }

    public function save(?string $outputPath = null): self
    {
        $outputPath ??= $this->filePath;

        $this->applyModifications();
        $modifiedCode = $this->printer->prettyPrintFile($this->ast);
        file_put_contents($outputPath, $modifiedCode);

        return $this;
    }

    public function backup(string $backupPath): self
    {
        if (! $this->originalCode) {
            return $this;
        }

        file_put_contents($backupPath, $this->originalCode);

        return $this;
    }

    public function toString(): string
    {
        $this->applyModifications();

        return $this->printer->prettyPrintFile($this->ast);
    }

    private function applyModifications(): void
    {
        foreach ($this->pendingModifications as $modification) {
            $this->applyModification($modification);
        }

        $this->pendingModifications = [];
    }

    private function applyModification(array $modification): void
    {
        $visitor = $this->createModifier($modification);

        $this->traverser->addVisitor($visitor);
        $this->ast = $this->traverser->traverse($this->ast);
        $this->traverser->removeVisitor($visitor);
    }

    private function createModifier(array $modification): object
    {
        if (isset($modification['factory'])) {
            return $this->createCustomModifier($modification);
        }

        return $this->createBuiltinModifier($modification);
    }

    private function createCustomModifier(array $modification): object
    {
        $factory = $modification['factory'];

        if (is_callable($factory)) {
            return $factory($modification);
        }

        return new $factory($modification);
    }

    private function createBuiltinModifier(array $modification): object
    {
        return match ($modification['type']) {
            'add_trait' => new Modifiers\AddTraitModifier($modification['trait']),
            'add_method' => new Modifiers\AddMethodModifier(
                $modification['name'],
                $modification['parameters'],
                $modification['body'],
                $modification['visibility'],
                $modification['override'] ?? false
            ),
            'add_property' => new Modifiers\AddPropertyModifier(
                $modification['name'],
                $modification['default'],
                $modification['visibility'],
                $modification['property_type']
            ),
            'extend_array_property' => new Modifiers\ExtendArrayPropertyModifier(
                $modification['property'],
                $modification['additions']
            ),
            'add_use_statement' => new Modifiers\AddUseStatementModifier(
                $modification['class'],
                $modification['alias']
            ),
            'change_property' => new Modifiers\ChangePropertyModifier(
                $modification['name'],
                $modification['default'],
                $modification['visibility'],
                $modification['property_type']
            ),
            'change_property_type' => new Modifiers\ChangePropertyTypeModifier(
                $modification['name'],
                $modification['property_type']
            ),
            'change_property_default' => new Modifiers\ChangePropertyDefaultModifier(
                $modification['name'],
                $modification['default']
            ),
            'change_property_visibility' => new Modifiers\ChangePropertyVisibilityModifier(
                $modification['name'],
                $modification['visibility']
            ),
            'remove_property' => new Modifiers\RemovePropertyModifier($modification['name']),
            'change_method' => new Modifiers\ChangeMethodModifier(
                $modification['name'],
                $modification['parameters'],
                $modification['body'],
                $modification['visibility']
            ),
            'change_method_body' => new Modifiers\ChangeMethodBodyModifier(
                $modification['name'],
                $modification['body']
            ),
            'change_method_visibility' => new Modifiers\ChangeMethodVisibilityModifier(
                $modification['name'],
                $modification['visibility']
            ),
            'remove_method' => new Modifiers\RemoveMethodModifier($modification['name']),
            'change_class_name' => new Modifiers\ChangeClassNameModifier($modification['name']),
            'add_constant' => new Modifiers\AddConstantModifier(
                $modification['name'],
                $modification['value'],
                $modification['visibility']
            ),
            'change_constant' => new Modifiers\ChangeConstantModifier(
                $modification['name'],
                $modification['value'],
                $modification['visibility']
            ),
            'remove_constant' => new Modifiers\RemoveConstantModifier($modification['name']),
            'remove_trait' => new Modifiers\RemoveTraitModifier($modification['trait']),
            'remove_use_statement' => new Modifiers\RemoveUseStatementModifier($modification['class']),
            'add_namespace' => new Modifiers\AddNamespaceModifier($modification['namespace']),
            'change_namespace' => new Modifiers\ChangeNamespaceModifier($modification['namespace']),
            'implement_interface' => throw new InvalidArgumentException('Interface implementation not yet implemented'),
            'extend_class' => throw new InvalidArgumentException('Class extension not yet implemented'),
            default => throw new InvalidArgumentException('Unknown modification type: '.$modification['type'])
        };
    }
}

# PHP Sculptor

**Programmatically modify PHP code with a fluent, intuitive API.** Built on `nikic/php-parser` for reliable AST manipulation, PHP Sculptor makes it easy to automate code modifications, refactoring, and generation tasks.

**Important:** PHP Sculptor is primarily intended for personal use and small-scale automation tasks. For comprehensive AST manipulation needs or production-scale applications, consider using the underlying [nikic/php-parser](https://github.com/nikic/PHP-Parser) package directly.

## Why PHP Sculptor?

- **Fluent API**: Chain multiple modifications in a readable, expressive way
- **Safe AST manipulation**: Built on battle-tested `nikic/php-parser` for reliable code parsing
- **Duplicate detection**: Automatically prevents duplicate traits, methods, and properties
- **Smart positioning**: Maintains proper code structure (traits → properties → methods)
- **Extensible**: Add custom modifiers for specialized use cases
- **Framework-agnostic**: Works with any PHP class structure

## Installation

```bash
composer require malico/php-sculptor
```

**Requirements:** PHP 8.0+ and `nikic/php-parser ^5.0`

## Quick Start

Transform any PHP class with a simple, chainable API:

```php
use Malico\PhpSculptor\Sculptor;

$sculptor = Sculptor::make('src/User.php')
    ->addUseStatement('App\Traits\Timestamped')
    ->addTrait('Timestamped')
    ->extendArrayProperty('config', ['cache_enabled' => true, 'timeout' => 30])
    ->addProperty('status', 'active', 'protected', 'string')
    ->addMethod('isActive', [], 'return $this->status === "active";')
    ->save();
```

## Core Features

### Class Structure Modifications

```php
// Add traits and use statements
$sculptor->addUseStatement('App\Traits\Loggable')
         ->addTrait('Loggable');

// Add properties with types and defaults
$sculptor->addProperty('status', 'pending', 'protected', 'string')
         ->addProperty('config', [], 'private', 'array');

// Add methods with parameters
$sculptor->addMethod('activate', [], '$this->status = "active";', 'public')
         ->addMethod('setConfig', [
             ['name' => 'key', 'type' => 'string'],
             ['name' => 'value', 'type' => 'mixed']
         ], '$this->config[$key] = $value;');

// Add constants
$sculptor->addConstant('DEFAULT_STATUS', 'pending', 'public');
```

### Array Property Extensions

```php
// Extend array properties safely
$sculptor->extendArrayProperty('permissions', ['read', 'write'])
         ->extendArrayProperty('config', ['debug' => true, 'cache' => false])
         ->extendArrayProperty('validationRules', ['email' => 'required']);

// Works with any array property
$sculptor->extendArrayProperty('features', ['notifications', 'logging']);
```

### Advanced Modifications

```php
// Modify existing elements
$sculptor->changeProperty('name', 'DefaultName', 'public', 'string')
         ->changeMethodBody('getName', 'return $this->name ?? "Anonymous";')
         ->changeMethodVisibility('getId', 'public');

// Remove elements
$sculptor->removeProperty('deprecatedField')
         ->removeMethod('oldMethod')
         ->removeTrait('ObsoleteTrait');

// Namespace operations
$sculptor->addNamespace('App\Services\Advanced')
         ->changeClassName('AdvancedProcessor');
```

## API Reference

### Core Modification Methods

| Method | Description | Example |
|--------|-------------|---------|
| `addTrait(string $trait)` | Add trait to class | `->addTrait('HasTeams')` |
| `addMethod(string $name, array $params, string $body, string $visibility)` | Add method with parameters | `->addMethod('getName', [], 'return $this->name;')` |
| `addProperty(string $name, mixed $default, string $visibility, ?string $type)` | Add typed property | `->addProperty('status', 'active', 'protected', 'string')` |
| `addUseStatement(string $class, ?string $alias)` | Add use statement | `->addUseStatement('App\Services\Logger', 'ServiceLogger')` |
| `addConstant(string $name, mixed $value, string $visibility)` | Add class constant | `->addConstant('VERSION', '1.0', 'public')` |

### Array Property Operations

| Method | Description | Example |
|--------|-------------|---------|
| `extendArrayProperty(string $property, array $additions)` | Safely extend array properties | `->extendArrayProperty('permissions', ['admin'])` |

### Change Operations

| Method | Description |
|--------|-------------|
| `changeProperty(string $name, mixed $default, ?string $visibility, ?string $type)` | Modify existing property |
| `changeMethod(string $name, ?array $params, ?string $body, ?string $visibility)` | Modify existing method |
| `changeClassName(string $newName)` | Change class name |
| `changeNamespace(string $newNamespace)` | Change namespace |

### Removal Operations

| Method | Description |
|--------|-------------|
| `removeProperty(string $name)` | Remove property |
| `removeMethod(string $name)` | Remove method |
| `removeTrait(string $trait)` | Remove trait |
| `removeConstant(string $name)` | Remove constant |

### File Operations

| Method | Description |
|--------|-------------|
| `save(?string $path)` | Save modifications to file (original path if null) |
| `backup(string $backupPath)` | Create backup of original file |
| `toString()` | Return modified code as string |

## Advanced Usage

### Custom Modifiers

Extend PHP Sculptor with your own modification logic:

```php
$sculptor->addModifier('custom_operation', function($modification) {
    return new MyCustomModifier($modification);
});
```

### Method Override Protection

Methods are protected from accidental duplication by default:

```php
// This will safely skip if 'getName' already exists
$sculptor->addMethod('getName', [], 'return $this->name;', 'public', false);

// This will override existing method
$sculptor->addMethod('getName', [], 'return $this->fullName;', 'public', true);
```

### Chaining Complex Modifications

```php
$result = Sculptor::make('src/Document.php')
    // Add logging functionality
    ->addUseStatement('App\Traits\Auditable')
    ->addTrait('Auditable')
    
    // Update class configuration
    ->extendArrayProperty('validationRules', ['title' => 'required', 'content' => 'string'])
    ->extendArrayProperty('config', ['auto_save' => true, 'version_control' => true])
    ->extendArrayProperty('observers', ['DocumentObserver', 'AuditObserver'])
    
    // Add business logic
    ->addMethod('publish', [], '$this->status = "published"; $this->save();', 'public')
    ->addMethod('isPublished', [], 'return $this->status === "published";', 'public')
    
    // Add utility methods
    ->addMethod('findByCategory', [
        ['name' => 'category', 'type' => 'string'],
        ['name' => 'limit', 'type' => 'int', 'default' => 10]
    ], 'return static::where("category", $category)->limit($limit)->get();', 'public static')
    
    ->save();
```

## Best Practices

1. **Use method chaining** for related modifications
2. **Group similar operations** together for readability
3. **Leverage array property extension** for configuration arrays and collections
4. **Create backups** before major modifications: `->backup('backup.php')`
5. **Test modifications** with `toString()` before saving

## Error Handling

PHP Sculptor includes built-in safety features:

- **File validation**: Ensures target files exist and are readable
- **Duplicate detection**: Prevents adding existing traits, methods, or properties
- **AST validation**: Ensures code remains syntactically valid
- **Override protection**: Methods require explicit override flag to replace existing implementations

## License

MIT License - see LICENSE file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
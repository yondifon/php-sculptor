# PHP Sculptor Development Context

## Project Overview
PHP Sculptor is a powerful package for programmatically modifying PHP code using AST manipulation with nikic/php-parser. It provides a fluent, intuitive API for common code modifications.

## Architecture

### Core Components
- **Sculptor.php**: Main class with fluent API for chaining modifications
- **Visitors/**: AST visitor classes that perform actual code modifications
  - `AddTraitVisitor`: Adds traits to classes
  - `AddMethodVisitor`: Adds methods with parameters and body
  - `AddPropertyVisitor`: Adds properties with types and defaults
  - `ExtendArrayPropertyVisitor`: Extends array properties (fillable, casts, etc.)
  - `AddUseStatementVisitor`: Adds use statements to classes

### Key Features
- Fluent API for chaining multiple modifications
- Safe AST manipulation with duplicate detection
- Laravel-specific helpers for common model modifications
- Proper code positioning (traits before properties, properties before methods)
- Type-aware property and parameter handling

## API Methods

### Core Modifications
- `addTrait(string $trait)`: Add trait to class
- `addMethod(string $name, array $params, string $body, string $visibility)`: Add method
- `addProperty(string $name, mixed $default, string $visibility, ?string $type)`: Add property
- `implementInterface(string $interface)`: Make class implement interface
- `extendClass(string $parent)`: Change class parent
- `extendArrayProperty(string $property, array $additions)`: Extend array properties
- `addUseStatement(string $class, ?string $alias)`: Add use statement

### Laravel Helpers
- `addToFillable(array $fields)`: Add fields to $fillable array
- `addToCasts(array $casts)`: Add casts to $casts array
- `addToHidden(array $fields)`: Add fields to $hidden array

### Utility Methods
- `save(?string $path)`: Save modifications to file
- `toString()`: Return modified code as string
- `backup(string $path)`: Create backup of original file
- `hasMethod(string $name)`: Check if method exists (placeholder)
- `hasTrait(string $trait)`: Check if trait exists (placeholder)

## Usage Pattern
```php
use Malico\PhpSculptor\Sculptor;

$sculptor = Sculptor::make('path/to/Class.php')
    ->addUseStatement('Some\\Trait\\HasTeams')
    ->addTrait('HasTeams')
    ->addToFillable(['team_id'])
    ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;')
    ->save();
```

## Development Notes

### Code Quality Standards
- Follow PSR-1, PSR-2, PSR-12
- Use typed properties and return types
- Avoid docblocks for fully typed methods
- Use constructor property promotion where possible
- Prefer early returns over nested conditionals

### AST Visitor Pattern
Each modification type has its own visitor class that:
1. Traverses the AST looking for target nodes (usually Class_ nodes)
2. Checks if modification already exists to avoid duplicates
3. Creates new nodes using PhpParser's node factories
4. Inserts nodes at appropriate positions in the class structure

### Test Strategy
- Unit tests for each visitor type
- Integration tests for fluent API
- Fixture-based testing with actual PHP files
- Tests for duplicate detection and proper positioning

## Future Enhancements
- Method removal and replacement
- Interface implementation
- Constructor parameter injection
- Query methods for existing code structure
- Backup and rollback capabilities
- Support for more complex method bodies with proper parsing

## File Structure
```
php-sculptor/
├── src/
│   ├── Sculptor.php
│   └── Visitors/
│       ├── AddTraitVisitor.php
│       ├── AddMethodVisitor.php
│       ├── AddPropertyVisitor.php
│       ├── ExtendArrayPropertyVisitor.php
│       └── AddUseStatementVisitor.php
├── tests/
│   └── SculptorTest.php
├── examples/
│   ├── SampleUser.php
│   └── modify_user.php
├── composer.json
├── phpunit.xml
└── README.md
```

## Dependencies
- `nikic/php-parser: ^5.0` for AST manipulation
- `phpunit/phpunit: ^12.0` for testing
- PHP 8.0+ requirement

## Known Issues
- Method body parsing is basic (wraps in try/catch)
- Query methods (hasMethod, hasTrait) are placeholders
- Need to handle edge cases in array property modification
- Test has typo: `assertStringContainsStringString` should be `assertStringContainsString`
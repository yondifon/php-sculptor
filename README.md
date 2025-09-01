# PHP Sculptor

Programmatically modify PHP code with a fluent, intuitive API. Built on top of `nikic/php-parser` for reliable AST manipulation.

## Installation

```bash
composer require malico/php-sculptor
```

## Quick Start

```php
use Malico\PhpSculptor\Sculptor;

$sculptor = new Sculptor('path/to/YourClass.php');

$sculptor
    ->addTrait('HasTeams')
    ->addMethod('getTeam', [], 'return $this->team;', 'public')
    ->addToFillable(['team_id'])
    ->save();
```

## Features

### Core Modifications
- ✅ Add/remove methods, properties, traits
- ✅ Implement interfaces and extend classes
- ✅ Modify array properties (fillable, casts, etc.)
- ✅ Add use statements and constants

### Laravel-Specific
- ✅ Modify Eloquent model properties (fillable, casts, hidden, etc.)
- ✅ Add scopes and relationships
- ✅ Constructor parameter injection

### Advanced Features
- ✅ Method wrapping and injection
- ✅ Conditional modifications
- ✅ Query existing code structure
- ✅ Backup and rollback capabilities

## API Reference

Coming soon...

## License

MIT
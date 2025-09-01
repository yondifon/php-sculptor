<?php

require_once '../vendor/autoload.php';

use Malico\PhpSculptor\Sculptor;

// Create a sculptor instance for the User model
$sculptor = Sculptor::make(__DIR__.'/SampleUser.php');

echo "🎨 PHP Sculptor Demo\n";
echo "===================\n\n";

echo "📝 Original User class:\n";
echo file_get_contents(__DIR__.'/SampleUser.php');
echo "\n".str_repeat('-', 50)."\n\n";

echo "🔧 Applying comprehensive modifications:\n";
echo "- Adding use statement for HasTeams trait\n";
echo "- Adding HasTeams trait\n";
echo "- Extending fillable array with team fields\n";
echo "- Adding team_role property\n";
echo "- Adding getCurrentTeam() method\n";
echo "- Adding switchTeam() method\n";
echo "- Changing existing property type\n";
echo "- Adding method with override protection\n\n";

// Apply multiple modifications in a fluent chain
$modifiedCode = $sculptor
    ->addUseStatement('Malico\\Teams\\HasTeams')
    ->addTrait('HasTeams')
    ->extendArrayProperty('fillable', ['team_id', 'current_team_id'])
    ->addProperty('team_role', 'member', 'protected', 'string')
    ->addProperty('status', 'active', 'public', 'string')
    ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;', 'public')
    ->addMethod('switchTeam', [
        ['name' => 'teamId', 'type' => 'int'],
    ], 'return $this->update([\'current_team_id\' => $teamId]);', 'public')
    ->changePropertyType('id', 'string')  // Change existing property
    ->changePropertyDefault('name', 'Unknown User')  // Change existing default
    ->addMethod('getName', [], 'return $this->name ?: "Anonymous";', 'public', false)  // No override
    ->addMethod('getName', [], 'return $this->name ?: "Guest User";', 'public', true)   // With override
    ->toString();

echo "✨ Modified User class:\n";
echo $modifiedCode;

echo "\n".str_repeat('=', 70)."\n";
echo "🔧 Extensible Architecture Demo:\n";
echo "Creating a custom visitor using VisitorFactory\n\n";

// Example of using the extensible visitor system
echo "📝 Using one-liner visitor creation:\n";
echo "VisitorFactory::make('ChangeProperty', ['status', 'inactive']);\n\n";

echo "📝 Using factory pattern for reusable custom operations:\n";
echo "\$sculptor->addVisitor('my_custom_task', \n";
echo "    VisitorFactory::simple('ChangePropertyDefault', ['name', 'default'])\n";
echo ");\n\n";

echo "🎉 Demo completed! The User class has been enhanced with comprehensive functionality.\n";
echo "✨ Key Features Demonstrated:\n";
echo "  • Fluent API for chaining operations\n";
echo "  • Override protection (override: false by default)\n";
echo "  • Property modification (type, default, visibility)\n";
echo "  • Extensible visitor architecture\n";
echo "  • Modern PHP 8.0+ features throughout\n";

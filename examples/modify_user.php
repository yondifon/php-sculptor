<?php

require_once '../vendor/autoload.php';

use Malico\PhpSculptor\Sculptor;

// Create a sculptor instance for the User model
$sculptor = Sculptor::make(__DIR__ . '/SampleUser.php');

echo "🎨 PHP Sculptor Demo\n";
echo "===================\n\n";

echo "📝 Original User class:\n";
echo file_get_contents(__DIR__ . '/SampleUser.php');
echo "\n" . str_repeat('-', 50) . "\n\n";

echo "🔧 Applying modifications:\n";
echo "- Adding HasTeams trait\n";
echo "- Adding team_id to fillable\n";
echo "- Adding getCurrentTeam() method\n";
echo "- Adding team_role property\n";
echo "- Adding use statement for HasTeams\n\n";

// Apply multiple modifications in a fluent chain
$modifiedCode = $sculptor
    ->addUseStatement('Malico\\Teams\\HasTeams')
    ->addTrait('HasTeams')
    ->addToFillable(['team_id', 'current_team_id'])
    ->addProperty('team_role', 'member', 'protected', 'string')
    ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;', 'public')
    ->addMethod('switchTeam', [
        ['name' => 'teamId', 'type' => 'int']
    ], 'return $this->update([\'current_team_id\' => $teamId]);', 'public')
    ->toString();

echo "✨ Modified User class:\n";
echo $modifiedCode;

echo "\n🎉 Demo completed! The User class has been enhanced with team functionality.\n";
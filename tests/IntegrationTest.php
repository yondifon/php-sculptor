<?php

namespace Malico\PhpSculptor\Tests;

class IntegrationTest extends SculptorTestBase
{

    public function test_complete_class_transformation()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            // Add dependencies
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addUseStatement('Malico\\Permissions\\HasPermissions')
            ->addUseStatement(\Carbon\Carbon::class)

            // Add traits
            ->addTrait('HasTeams')
            ->addTrait('HasPermissions')

            // Modify existing properties
            ->changePropertyType('id', 'string')
            ->changePropertyDefault('name', 'Anonymous User')
            ->changePropertyVisibility('name', 'public')

            // Add new properties
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->addProperty('permissions', ['read'], 'protected', 'array')
            ->addProperty('lastLoginAt', null, 'protected', '?Carbon\\Carbon')
            ->addProperty('isActive', true, 'public', 'bool')

            // Extend array properties
            ->extendArrayProperty('fillable', ['team_id', 'team_role', 'last_login_at', 'is_active'])

            // Add methods
            ->addMethod('getTeamRole', [], 'return $this->teamRole;', 'public')
            ->addMethod('setTeamRole', [
                ['name' => 'role', 'type' => 'string'],
            ], '$this->teamRole = $role; return $this;', 'public')
            ->addMethod('hasPermission', [
                ['name' => 'permission', 'type' => 'string'],
            ], 'return in_array($permission, $this->permissions);', 'public')
            ->addMethod('activate', [], '$this->isActive = true; return $this;', 'public')
            ->addMethod('deactivate', [], '$this->isActive = false; return $this;', 'public')

            // Override existing method
            ->addMethod('getName', [], 'return $this->name ?: "Guest User";', 'public', true)

            ->toString();

        // Verify all transformations were applied correctly
        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use Malico\\Permissions\\HasPermissions;', $result);
        $this->assertStringContainsString('use Carbon\\Carbon;', $result);

        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('use HasPermissions;', $result);

        $this->assertStringContainsString('protected string $id', $result);
        $this->assertStringContainsString('public string $name = \'Anonymous User\'', $result);

        $this->assertStringContainsString('protected string $teamRole = \'member\'', $result);
        $this->assertStringContainsString('protected array $permissions = [\'read\']', $result);
        $this->assertStringContainsString('protected ?Carbon\\Carbon $lastLoginAt = null', $result);
        $this->assertStringContainsString('public bool $isActive = true', $result);

        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('team_role', $result);
        $this->assertStringContainsString('last_login_at', $result);
        $this->assertStringContainsString('is_active', $result);

        $this->assertStringContainsString('function getTeamRole()', $result);
        $this->assertStringContainsString('function setTeamRole(string $role)', $result);
        $this->assertStringContainsString('function hasPermission(string $permission)', $result);
        $this->assertStringContainsString('function activate()', $result);
        $this->assertStringContainsString('function deactivate()', $result);

        // Verify method override worked
        $this->assertStringContainsString('return $this->name ?: "Guest User";', $result);
        $this->assertStringNotContainsString('return $this->name;', $result);
    }

    public function test_laravel_model_enhancement()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            // Add common Laravel model traits
            ->addUseStatement('Illuminate\\Database\\Eloquent\\SoftDeletes')
            ->addUseStatement('Laravel\\Scout\\Searchable')
            ->addTrait('SoftDeletes')
            ->addTrait('Searchable')

            // Add model configuration
            ->addProperty('table', 'users', 'protected', 'string')
            ->addProperty('primaryKey', 'id', 'protected', 'string')
            ->addProperty('keyType', 'string', 'protected', 'string')
            ->addProperty('incrementing', false, 'public', 'bool')

            // Extend Laravel arrays
            ->extendArrayProperty('fillable', [
                'uuid', 'first_name', 'last_name', 'email_verified_at', 'avatar',
            ])
            ->addProperty('casts', [
                'email_verified_at' => 'datetime',
                'last_login_at' => 'datetime',
            ], 'protected', 'array')
            ->addProperty('hidden', [
                'password', 'remember_token', 'api_token',
            ], 'protected', 'array')
            ->addProperty('dates', [
                'deleted_at', 'email_verified_at', 'last_login_at',
            ], 'protected', 'array')

            // Add common accessor methods
            ->addMethod(
                'getFullNameAttribute',
                [],
                'return trim($this->first_name . \' \' . $this->last_name);',
                'public'
            )
            ->addMethod(
                'getAvatarUrlAttribute',
                [],
                'return $this->avatar ? asset("storage/avatars/" . $this->avatar) : null;',
                'public'
            )

            // Add scope methods
            ->addMethod('scopeActive', [
                ['name' => 'query', 'type' => '\\Illuminate\\Database\\Eloquent\\Builder'],
            ], 'return $query->where(\'is_active\', true);', 'public')
            ->addMethod('scopeVerified', [
                ['name' => 'query', 'type' => '\\Illuminate\\Database\\Eloquent\\Builder'],
            ], 'return $query->whereNotNull(\'email_verified_at\');', 'public')

            ->toString();

        // Verify Laravel-specific enhancements
        $this->assertStringContainsString('use SoftDeletes;', $result);
        $this->assertStringContainsString('use Searchable;', $result);
        $this->assertStringContainsString('protected string $table = \'users\'', $result);
        $this->assertStringContainsString('public bool $incrementing = false', $result);
        $this->assertStringContainsString('uuid', $result);
        $this->assertStringContainsString('first_name', $result);
        $this->assertStringContainsString('email_verified_at', $result);
        $this->assertStringContainsString('getFullNameAttribute', $result);
        $this->assertStringContainsString('scopeActive', $result);
    }

    public function test_api_resource_class_creation()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            // Change class purpose
            ->changeClassName('UserResource')

            // Add API resource dependencies
            ->addUseStatement('Illuminate\\Http\\Resources\\Json\\JsonResource')
            ->addUseStatement('Illuminate\\Http\\Request')

            // Change parent class (would need implementation)
            // ->extendClass('JsonResource')

            // Add resource methods
            ->addMethod('toArray', [
                ['name' => 'request', 'type' => 'Request'],
            ], 'return [
                \'id\' => $this->id,
                \'name\' => $this->name,
                \'email\' => $this->email,
                \'created_at\' => $this->created_at,
                \'updated_at\' => $this->updated_at,
            ];', 'public')

            ->addMethod('with', [
                ['name' => 'request', 'type' => 'Request'],
            ], 'return [
                \'version\' => \'1.0\',
                \'api_url\' => url(\'/api\'),
            ];', 'public')

            ->toString();

        $this->assertStringContainsString('use Illuminate\\Http\\Resources\\Json\\JsonResource;', $result);
        $this->assertStringContainsString('function toArray(Request $request)', $result);
        $this->assertStringContainsString('function with(Request $request)', $result);
        $this->assertStringContainsString('\'id\' => $this->id', $result);
    }

    public function test_multiple_file_operations()
    {
        $outputFile1 = $this->createTempFile('model_');
        $outputFile2 = $this->createTempFile('resource_');
        $backupFile = $this->createTempFile('backup_');

        $sculptor = $this->createSculptor();

        // Create backup, then save two different versions
        $sculptor
            ->backup($backupFile)
            ->addTrait('HasTeams')
            ->addProperty('teamId', 1, 'protected', 'int')
            ->save($outputFile1);

        // Continue modifying for second file
        $sculptor
            ->addTrait('HasPermissions')
            ->addProperty('role', 'user', 'protected', 'string')
            ->save($outputFile2);

        // Verify backup is original
        $backupContent = file_get_contents($backupFile);
        $this->assertStringNotContainsString('HasTeams', $backupContent);
        $this->assertStringNotContainsString('HasPermissions', $backupContent);

        // Verify first output
        $output1Content = file_get_contents($outputFile1);
        $this->assertStringContainsString('use HasTeams;', $output1Content);
        $this->assertStringContainsString('protected int $teamId', $output1Content);
        $this->assertStringNotContainsString('HasPermissions', $output1Content);

        // Verify second output has both modifications
        $output2Content = file_get_contents($outputFile2);
        $this->assertStringContainsString('use HasTeams;', $output2Content);
        $this->assertStringContainsString('use HasPermissions;', $output2Content);
        $this->assertStringContainsString('protected int $teamId', $output2Content);
        $this->assertStringContainsString('protected string $role', $output2Content);

        // Cleanup
        $this->cleanupTempFile($outputFile1);
        $this->cleanupTempFile($outputFile2);
        $this->cleanupTempFile($backupFile);
    }

    public function test_error_recovery_and_edge_cases()
    {
        $sculptor = $this->createSculptor();

        // These operations should not break the fluent chain
        $result = $sculptor
            ->addTrait('NonExistentTrait') // Should work fine
            ->removeProperty('nonExistentProperty') // Should not error
            ->addMethod('getName', [], 'return "test";') // Should not override (default behavior)
            ->addProperty('existingProperty', 'value1', 'public', 'string')
            ->addProperty('existingProperty', 'value2', 'private', 'int') // Should not duplicate
            ->changePropertyType('nonExistentProperty', 'string') // Should not error
            ->toString();

        // Should still produce valid class
        $this->assertStringContainsString('class TestClass', $result);
        $this->assertStringContainsString('use NonExistentTrait;', $result);
        $this->assertStringContainsString('return $this->name;', $result); // Original method preserved
        $this->assertStringNotContainsString('return "test";', $result); // Override blocked
    }
}

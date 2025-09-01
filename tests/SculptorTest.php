<?php

namespace Malico\PhpSculptor\Tests;

use Malico\PhpSculptor\Sculptor;
use Malico\PhpSculptor\VisitorFactory;
use PHPUnit\Framework\TestCase;

class SculptorTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testFilePath = __DIR__.'/fixtures/TestClass.php';

        // Create test fixture
        $testClassCode = '<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestClass extends Authenticatable
{
    protected $fillable = [
        "name",
        "email",
    ];
    
    protected int $id = 1;
    protected string $name = "test";
    
    public function getName()
    {
        return $this->name;
    }
}';

        @mkdir(dirname($this->testFilePath), 0755, true);
        file_put_contents($this->testFilePath, $testClassCode);
    }

    protected function tearDown(): void
    {
        if (! file_exists($this->testFilePath)) {
            return;
        }

        unlink($this->testFilePath);
    }

    // =================================================================
    // TRAIT OPERATIONS
    // =================================================================

    public function test_can_add_trait()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addTrait('HasTeams')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
    }

    public function test_can_add_multiple_traits()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addTrait('HasPermissions')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('use HasPermissions;', $result);
    }

    public function test_cannot_add_duplicate_trait()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addTrait('HasTeams') // Should not duplicate
            ->toString();

        $this->assertEquals(1, substr_count($result, 'use HasTeams;'));
    }

    // =================================================================
    // USE STATEMENT OPERATIONS
    // =================================================================

    public function test_can_add_use_statement()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->toString();

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
    }

    public function test_can_add_use_statement_with_alias()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Very\\Long\\Namespace\\HasTeams', 'Teams')
            ->toString();

        $this->assertStringContainsString('use Very\\Long\\Namespace\\HasTeams as Teams;', $result);
    }

    public function test_cannot_add_duplicate_use_statement()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addUseStatement('Malico\\Teams\\HasTeams') // Should not duplicate
            ->toString();

        $this->assertEquals(1, substr_count($result, 'use Malico\\Teams\\HasTeams;'));
    }

    // =================================================================
    // PROPERTY ADDITION OPERATIONS
    // =================================================================

    public function test_can_add_property()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $teamRole = \'member\';', $result);
    }

    public function test_can_add_property_without_type()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addProperty('status', 'active', 'public')
            ->toString();

        $this->assertStringContainsString('public $status = \'active\';', $result);
    }

    public function test_can_add_property_without_default()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addProperty('userId', null, 'private', 'int')
            ->toString();

        $this->assertStringContainsString('private int $userId', $result);
    }

    public function test_can_add_property_with_array_default()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addProperty('permissions', ['read', 'write'], 'protected', 'array')
            ->toString();

        $this->assertStringContainsString('protected array $permissions = [\'read\', \'write\'];', $result);
    }

    // =================================================================
    // PROPERTY MODIFICATION OPERATIONS
    // =================================================================

    public function test_can_change_property_type()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->changePropertyType('id', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $id', $result);
        $this->assertStringNotContainsString('protected int $id', $result);
    }

    public function test_can_change_property_default()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->changePropertyDefault('name', 'Anonymous')
            ->toString();

        $this->assertStringContainsString('$name = \'Anonymous\'', $result);
        $this->assertStringNotContainsString('$name = "test"', $result);
    }

    public function test_can_change_property_visibility()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->changePropertyVisibility('name', 'public')
            ->toString();

        $this->assertStringContainsString('public string $name', $result);
        $this->assertStringNotContainsString('protected string $name', $result);
    }

    public function test_can_change_multiple_property_aspects()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->changeProperty('name', 'Guest User', 'public', 'string')
            ->toString();

        $this->assertStringContainsString('public string $name = \'Guest User\'', $result);
    }

    public function test_can_remove_property()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->removeProperty('name')
            ->toString();

        $this->assertStringNotContainsString('$name', $result);
    }

    // =================================================================
    // ARRAY PROPERTY OPERATIONS
    // =================================================================

    public function test_can_extend_array_property()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->extendArrayProperty('fillable', ['team_id'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
    }

    public function test_can_extend_array_property_with_multiple_values()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->extendArrayProperty('fillable', ['team_id', 'role', 'status'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('role', $result);
        $this->assertStringContainsString('status', $result);
    }

    // =================================================================
    // METHOD ADDITION OPERATIONS
    // =================================================================

    public function test_can_add_method()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('getTeam', [], 'return $this->team;')
            ->toString();

        $this->assertStringContainsString('public function getTeam()', $result);
        $this->assertStringContainsString('return $this->team;', $result);
    }

    public function test_can_add_method_with_parameters()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('setTeam', [
                ['name' => 'teamId', 'type' => 'int'],
                ['name' => 'role', 'type' => 'string', 'default' => 'member'],
            ], 'return $this->update([\'team_id\' => $teamId, \'role\' => $role]);')
            ->toString();

        $this->assertStringContainsString('public function setTeam(int $teamId, string $role = \'member\')', $result);
    }

    public function test_can_add_private_method()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('processData', [], 'return $this->data;', 'private')
            ->toString();

        $this->assertStringContainsString('private function processData()', $result);
    }

    public function test_can_add_protected_method()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('validateInput', [], 'return true;', 'protected')
            ->toString();

        $this->assertStringContainsString('protected function validateInput()', $result);
    }

    // =================================================================
    // METHOD OVERRIDE PROTECTION
    // =================================================================

    public function test_cannot_override_existing_method_by_default()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('getName', [], 'return "Override attempt";')
            ->toString();

        // Should still contain original method body
        $this->assertStringContainsString('return $this->name;', $result);
        $this->assertStringNotContainsString('return "Override attempt";', $result);
    }

    public function test_can_override_existing_method_with_explicit_flag()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('getName', [], 'return "Overridden";', 'public', true)
            ->toString();

        // Should contain new method body
        $this->assertStringContainsString('return "Overridden";', $result);
        $this->assertStringNotContainsString('return $this->name;', $result);
    }

    // =================================================================
    // EXTENSIBLE VISITOR ARCHITECTURE
    // =================================================================

    public function test_visitor_factory_can_create_visitor()
    {
        $visitor = VisitorFactory::make('ChangeProperty', ['status', 'active', 'public', 'string']);

        $this->assertInstanceOf(
            'Malico\\PhpSculptor\\Visitors\\ChangePropertyVisitor',
            $visitor
        );
    }

    public function test_visitor_factory_throws_exception_for_invalid_visitor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Visitor class does not exist');

        VisitorFactory::make('NonExistentVisitor', []);
    }

    // =================================================================
    // COMPLEX FLUENT INTERFACE OPERATIONS
    // =================================================================

    public function test_complex_fluent_interface_operations()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addTrait('HasTeams')
            ->extendArrayProperty('fillable', ['team_id'])
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;')
            ->changePropertyType('id', 'string')
            ->toString();

        // Verify all operations were applied
        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('protected string $teamRole = \'member\';', $result);
        $this->assertStringContainsString('function getCurrentTeam()', $result);
        $this->assertStringContainsString('protected string $id', $result);
    }

    // =================================================================
    // FILE OPERATIONS
    // =================================================================

    public function test_can_save_modifications()
    {
        $tempFile = sys_get_temp_dir().'/sculptor_test_'.uniqid().'.php';
        copy($this->testFilePath, $tempFile);

        $sculptor = new Sculptor($tempFile);
        $sculptor
            ->addTrait('HasTeams')
            ->save();

        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('use HasTeams;', $content);

        unlink($tempFile);
    }

    public function test_can_save_to_different_path()
    {
        $outputFile = sys_get_temp_dir().'/sculptor_output_'.uniqid().'.php';

        $sculptor = new Sculptor($this->testFilePath);
        $sculptor
            ->addTrait('HasTeams')
            ->save($outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('use HasTeams;', $content);

        unlink($outputFile);
    }

    public function test_can_create_backup()
    {
        $backupFile = sys_get_temp_dir().'/sculptor_backup_'.uniqid().'.php';

        $sculptor = new Sculptor($this->testFilePath);
        $sculptor->backup($backupFile);

        $this->assertFileExists($backupFile);
        $this->assertEquals(
            file_get_contents($this->testFilePath),
            file_get_contents($backupFile)
        );

        unlink($backupFile);
    }

    // =================================================================
    // ERROR HANDLING
    // =================================================================

    public function test_throws_exception_for_non_existent_file()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');

        new Sculptor('/path/that/does/not/exist.php');
    }

    public function test_static_make_method()
    {
        $sculptor = Sculptor::make($this->testFilePath);
        $this->assertInstanceOf(Sculptor::class, $sculptor);

        $result = $sculptor->addTrait('HasTeams')->toString();
        $this->assertStringContainsString('use HasTeams;', $result);
    }
}

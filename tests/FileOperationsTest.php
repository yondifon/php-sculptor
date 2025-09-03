<?php

namespace Malico\PhpSculptor\Tests;

use Malico\PhpSculptor\Sculptor;

class FileOperationsTest extends SculptorTestBase
{
    public function test_can_save_modifications()
    {
        $tempFile = $this->createTempFile();
        copy($this->testFilePath, $tempFile);

        $sculptor = new Sculptor($tempFile);
        $sculptor
            ->addTrait('HasTeams')
            ->save();

        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('use HasTeams;', $content);

        $this->cleanupTempFile($tempFile);
    }

    public function test_can_save_to_different_path()
    {
        $outputFile = $this->createTempFile('sculptor_output_');

        $sculptor = $this->createSculptor();
        $sculptor
            ->addTrait('HasTeams')
            ->save($outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('use HasTeams;', $content);

        $this->cleanupTempFile($outputFile);
    }

    public function test_can_save_complex_modifications()
    {
        $outputFile = $this->createTempFile('sculptor_complex_');

        $sculptor = $this->createSculptor();
        $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addTrait('HasTeams')
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->addMethod('getTeamRole', [], 'return $this->teamRole;')
            ->extendArrayProperty('fillable', ['team_id', 'team_role'])
            ->save($outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $content);
        $this->assertStringContainsString('use HasTeams;', $content);
        $this->assertStringContainsString('protected string $teamRole', $content);
        $this->assertStringContainsString('function getTeamRole()', $content);
        $this->assertStringContainsString('team_id', $content);
        $this->assertStringContainsString('team_role', $content);

        $this->cleanupTempFile($outputFile);
    }

    public function test_can_create_backup()
    {
        $backupFile = $this->createTempFile('sculptor_backup_');

        $sculptor = $this->createSculptor();
        $sculptor->backup($backupFile);

        $this->assertFileExists($backupFile);
        $this->assertEquals(
            file_get_contents($this->testFilePath),
            file_get_contents($backupFile)
        );

        $this->cleanupTempFile($backupFile);
    }

    public function test_can_create_backup_before_modifications()
    {
        $backupFile = $this->createTempFile('sculptor_backup_before_');
        $outputFile = $this->createTempFile('sculptor_output_');

        $sculptor = $this->createSculptor();
        $sculptor
            ->backup($backupFile)
            ->addTrait('HasTeams')
            ->addProperty('teamId', 1, 'protected', 'int')
            ->save($outputFile);

        $backupContent = file_get_contents($backupFile);
        $this->assertStringNotContainsString('use HasTeams;', $backupContent);
        $this->assertStringNotContainsString('$teamId', $backupContent);

        // Output should contain modifications
        $outputContent = file_get_contents($outputFile);
        $this->assertStringContainsString('use HasTeams;', $outputContent);
        $this->assertStringContainsString('protected int $teamId', $outputContent);

        $this->cleanupTempFile($backupFile);
        $this->cleanupTempFile($outputFile);
    }

    // =================================================================
    // STRING OUTPUT OPERATIONS
    // =================================================================

    public function test_can_output_as_string()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addProperty('status', 'active', 'protected', 'string')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('protected string $status = \'active\';', $result);
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('class TestClass', $result);
    }

    public function test_string_output_preserves_original_structure()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('newProp', 'value', 'public', 'string')
            ->toString();

        // Should preserve original namespace and imports
        $this->assertStringContainsString('namespace App\\Models;', $result);
        $this->assertStringContainsString('use Illuminate\\Foundation\\Auth\\User as Authenticatable;', $result);
        $this->assertStringContainsString('extends Authenticatable', $result);

        // Should include new property
        $this->assertStringContainsString('public string $newProp = \'value\';', $result);
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

    // =================================================================
    // FACTORY METHOD TESTS
    // =================================================================

    public function test_static_make_method()
    {
        $sculptor = Sculptor::make($this->testFilePath);
        $this->assertInstanceOf(Sculptor::class, $sculptor);

        $result = $sculptor->addTrait('HasTeams')->toString();
        $this->assertStringContainsString('use HasTeams;', $result);
    }

    public function test_static_make_method_with_fluent_interface()
    {
        $result = Sculptor::make($this->testFilePath)
            ->addTrait('HasTeams')
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->addMethod('getRole', [], 'return $this->teamRole;')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('protected string $teamRole', $result);
        $this->assertStringContainsString('function getRole()', $result);
    }

    // =================================================================
    // CHAINING SAVE OPERATIONS
    // =================================================================

    public function test_can_chain_save_with_other_operations()
    {
        $outputFile = $this->createTempFile();

        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->save($outputFile)
            ->addProperty('status', 'active', 'public', 'string')
            ->toString();

        // File should contain first modification
        $savedContent = file_get_contents($outputFile);
        $this->assertStringContainsString('use HasTeams;', $savedContent);

        // Result should contain both modifications
        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('public string $status', $result);

        $this->cleanupTempFile($outputFile);
    }
}

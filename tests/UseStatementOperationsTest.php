<?php

namespace Malico\PhpSculptor\Tests;

class UseStatementOperationsTest extends SculptorTestBase
{
    public function test_can_add_use_statement()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->toString();

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
    }

    public function test_can_add_use_statement_with_alias()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Very\\Long\\Namespace\\HasTeams', 'Teams')
            ->toString();

        $this->assertStringContainsString('use Very\\Long\\Namespace\\HasTeams as Teams;', $result);
    }

    public function test_cannot_add_duplicate_use_statement()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addUseStatement('Malico\\Teams\\HasTeams') // Should not duplicate
            ->toString();

        $this->assertEquals(1, substr_count($result, 'use Malico\\Teams\\HasTeams;'));
    }

    public function test_can_remove_use_statement()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addUseStatement('Malico\\Permissions\\HasPermissions')
            ->removeUseStatement('Malico\\Teams\\HasTeams')
            ->toString();

        $this->assertStringNotContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use Malico\\Permissions\\HasPermissions;', $result);
    }

    public function test_can_add_multiple_use_statements()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addUseStatement('Malico\\Permissions\\HasPermissions')
            ->addUseStatement('Carbon\\Carbon')
            ->toString();

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use Malico\\Permissions\\HasPermissions;', $result);
        $this->assertStringContainsString('use Carbon\\Carbon;', $result);
    }
}

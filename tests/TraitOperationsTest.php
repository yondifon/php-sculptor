<?php

namespace Malico\PhpSculptor\Tests;

class TraitOperationsTest extends SculptorTestBase
{
    public function test_can_add_trait()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
    }

    public function test_can_add_multiple_traits()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addTrait('HasPermissions')
            ->toString();

        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('use HasPermissions;', $result);
    }

    public function test_cannot_add_duplicate_trait()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addTrait('HasTeams') // Should not duplicate
            ->toString();

        $this->assertEquals(1, substr_count($result, 'use HasTeams;'));
    }

    public function test_can_remove_trait()
    {
        // First add a trait, then remove it
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addTrait('HasTeams')
            ->addTrait('HasPermissions')
            ->removeTrait('HasTeams')
            ->toString();

        $this->assertStringNotContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('use HasPermissions;', $result);
    }
}

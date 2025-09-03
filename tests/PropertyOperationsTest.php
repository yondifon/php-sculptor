<?php

namespace Malico\PhpSculptor\Tests;

class PropertyOperationsTest extends SculptorTestBase
{

    public function test_can_add_property()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $teamRole = \'member\';', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_add_property_without_type()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('status', 'active', 'public')
            ->toString();

        $this->assertStringContainsString('public $status = \'active\';', $result);
    }

    public function test_can_add_property_without_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('userId', null, 'private', 'int')
            ->toString();

        $this->assertStringContainsString('private int $userId', $result);
    }

    public function test_can_add_property_with_array_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('permissions', ['read', 'write'], 'protected', 'array')
            ->toString();

        $this->assertStringContainsString('protected array $permissions = [\'read\', \'write\'];', $result);
    }

    public function test_can_add_property_with_boolean_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('isActive', true, 'protected', 'bool')
            ->toString();

        $this->assertStringContainsString('protected bool $isActive = true;', $result);
    }

    public function test_can_add_property_with_null_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addProperty('deletedAt', null, 'protected', '?\\DateTime')
            ->toString();

        $this->assertStringContainsString('protected ?\\DateTime $deletedAt = null;', $result);
    }


    public function test_can_change_property_type()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changePropertyType('id', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $id', $result);
        $this->assertStringNotContainsString('protected int $id', $result);
    }

    public function test_can_change_property_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changePropertyDefault('name', 'Anonymous')
            ->toString();

        $this->assertStringContainsString('$name = \'Anonymous\'', $result);
        $this->assertStringNotContainsString('$name = "test"', $result);
    }

    public function test_can_change_property_visibility()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changePropertyVisibility('name', 'public')
            ->toString();

        $this->assertStringContainsString('public string $name', $result);
        $this->assertStringNotContainsString('protected string $name', $result);
    }

    public function test_can_change_multiple_property_aspects()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeProperty('name', 'Guest User', 'public', 'string')
            ->toString();

        $this->assertStringContainsString('public string $name = \'Guest User\'', $result);
    }

    public function test_can_remove_property()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->removeProperty('name')
            ->toString();

        $this->assertStringNotContainsString('$name', $result);
    }

    public function test_cannot_remove_nonexistent_property()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->removeProperty('nonExistentProperty')
            ->toString();

        // Should not affect the class
        $this->assertStringContainsString('class TestClass', $result);
    }


    public function test_can_extend_array_property()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->extendArrayProperty('fillable', ['team_id'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
    }

    public function test_can_extend_array_property_with_multiple_values()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->extendArrayProperty('fillable', ['team_id', 'role', 'status'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('role', $result);
        $this->assertStringContainsString('status', $result);
    }

    public function test_can_extend_multiple_array_properties()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->extendArrayProperty('fillable', ['team_id'])
            ->addProperty('hidden', ['password'], 'protected', 'array')
            ->extendArrayProperty('hidden', ['api_token'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('api_token', $result);
    }
}

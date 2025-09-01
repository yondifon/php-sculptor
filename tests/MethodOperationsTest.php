<?php

namespace Malico\PhpSculptor\Tests;

class MethodOperationsTest extends SculptorTestBase
{
    // =================================================================
    // METHOD ADDITION TESTS
    // =================================================================

    public function test_can_add_method()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getTeam', [], 'return $this->team;')
            ->toString();

        $this->assertStringContainsString('public function getTeam()', $result);
        $this->assertStringContainsString('return $this->team;', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_add_method_with_parameters()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('setTeam', [
                ['name' => 'teamId', 'type' => 'int'],
                ['name' => 'role', 'type' => 'string', 'default' => 'member'],
            ], 'return $this->update([\'team_id\' => $teamId, \'role\' => $role]);')
            ->toString();

        $this->assertStringContainsString('public function setTeam(int $teamId, string $role = \'member\')', $result);
    }

    public function test_can_add_method_with_simple_parameters()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('processData', ['data', 'options'], 'return $this->process($data, $options);')
            ->toString();

        $this->assertStringContainsString('public function processData($data, $options)', $result);
    }

    public function test_can_add_private_method()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('processData', [], 'return $this->data;', 'private')
            ->toString();

        $this->assertStringContainsString('private function processData()', $result);
    }

    public function test_can_add_protected_method()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('validateInput', [], 'return true;', 'protected')
            ->toString();

        $this->assertStringContainsString('protected function validateInput()', $result);
    }

    public function test_can_add_method_with_complex_body()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('complexMethod', ['id'], '
                if (!$id) {
                    return null;
                }
                
                $result = $this->find($id);
                return $result;
            ')
            ->toString();

        $this->assertStringContainsString('public function complexMethod($id)', $result);
        $this->assertStringContainsString('if (!$id)', $result);
        $this->assertStringContainsString('return $result;', $result);
    }

    // =================================================================
    // METHOD OVERRIDE PROTECTION TESTS
    // =================================================================

    public function test_cannot_override_existing_method_by_default()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getName', [], 'return "Override attempt";')
            ->toString();

        // Should still contain original method body
        $this->assertStringContainsString('return $this->name;', $result);
        $this->assertStringNotContainsString('return "Override attempt";', $result);
    }

    public function test_can_override_existing_method_with_explicit_flag()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getName', [], 'return "Overridden";', 'public', true)
            ->toString();

        // Should contain new method body
        $this->assertStringContainsString('return "Overridden";', $result);
        $this->assertStringNotContainsString('return $this->name;', $result);
    }

    public function test_override_protection_works_with_parameters()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getName', [['name' => 'prefix', 'type' => 'string']], 'return $prefix . $this->name;')
            ->toString();

        // Should not override - original method has no parameters
        $this->assertStringContainsString('return $this->name;', $result);
        $this->assertStringNotContainsString('$prefix', $result);
    }

    public function test_can_override_method_with_different_signature()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getName', [['name' => 'prefix', 'type' => 'string']], 'return $prefix . $this->name;', 'public', true)
            ->toString();

        // Should override with new signature
        $this->assertStringContainsString('function getName(string $prefix)', $result);
        $this->assertStringContainsString('return $prefix . $this->name;', $result);
    }

    // =================================================================
    // METHOD MODIFICATION TESTS (Future functionality)
    // =================================================================

    public function test_can_add_multiple_methods()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getTeamId', [], 'return $this->team_id;')
            ->addMethod('setTeamId', [['name' => 'teamId', 'type' => 'int']], '$this->team_id = $teamId;')
            ->addMethod('hasTeam', [], 'return !empty($this->team_id);')
            ->toString();

        $this->assertStringContainsString('function getTeamId()', $result);
        $this->assertStringContainsString('function setTeamId(int $teamId)', $result);
        $this->assertStringContainsString('function hasTeam()', $result);
    }

    public function test_methods_are_added_in_order()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('firstMethod', [], 'return "first";')
            ->addMethod('secondMethod', [], 'return "second";')
            ->toString();

        $firstPos = strpos($result, 'function firstMethod');
        $secondPos = strpos($result, 'function secondMethod');

        $this->assertGreaterThan($firstPos, $secondPos, 'Methods should be added in the order they were called');
    }

    public function test_method_with_return_type_hint()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getCount', [], 'return count($this->items);')
            ->toString();

        $this->assertStringContainsString('public function getCount()', $result);
        $this->assertStringContainsString('return count($this->items);', $result);
    }
}

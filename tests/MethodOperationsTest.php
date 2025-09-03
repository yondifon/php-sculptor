<?php

namespace Malico\PhpSculptor\Tests;

class MethodOperationsTest extends SculptorTestBase
{

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

    public function test_can_change_method_visibility()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('getName', null, null, 'private')
            ->toString();

        $this->assertStringContainsString('private function getName()', $result);
        $this->assertStringNotContainsString('public function getName()', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_change_method_body()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('getName', null, 'return "Modified: " . $this->name;')
            ->toString();

        $this->assertStringContainsString('return "Modified: " . $this->name;', $result);
        $this->assertStringNotContainsString('return $this->name;', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_change_method_parameters()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('getName', [
                ['name' => 'prefix', 'type' => 'string'],
                ['name' => 'suffix', 'type' => 'string', 'default' => ''],
            ])
            ->toString();

        $this->assertStringContainsString('function getName(string $prefix, string $suffix = \'\')', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_change_method_with_simple_parameters()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('getName', ['prefix', 'suffix'])
            ->toString();

        $this->assertStringContainsString('function getName($prefix, $suffix)', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_change_multiple_method_aspects()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod(
                'getName',
                [['name' => 'format', 'type' => 'bool', 'default' => true]],
                'return $format ? strtoupper($this->name) : $this->name;',
                'protected'
            )
            ->toString();

        $this->assertStringContainsString('protected function getName(bool $format = true)', $result);
        $this->assertStringContainsString('return $format ? strtoupper($this->name) : $this->name;', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_can_change_method_with_complex_body()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('getName', null, '
                if (empty($this->name)) {
                    return "Unknown";
                }
                
                return ucfirst($this->name);
            ')
            ->toString();

        $this->assertStringContainsString('if (empty($this->name))', $result);
        $this->assertStringContainsString('return "Unknown";', $result);
        $this->assertStringContainsString('return ucfirst($this->name);', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_change_method_preserves_other_methods()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addMethod('getId', [], 'return $this->id;') // Add a method first
            ->changeMethod('getName', null, 'return "Changed";')
            ->toString();

        // Should preserve other methods
        $this->assertStringContainsString('function getId()', $result);
        $this->assertStringContainsString('return $this->id;', $result);
        $this->assertStringContainsString('return "Changed";', $result);
        $this->assertValidPhpSyntax($result);
    }

    public function test_change_nonexistent_method_does_nothing()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->changeMethod('nonExistentMethod', null, 'return "test";')
            ->toString();

        // Should not add the method or break anything
        $this->assertStringNotContainsString('nonExistentMethod', $result);
        $this->assertStringContainsString('function getName()', $result); // Original methods preserved
        $this->assertValidPhpSyntax($result);
    }

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

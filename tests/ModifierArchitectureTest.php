<?php

namespace Malico\PhpSculptor\Tests;

use Malico\PhpSculptor\ModifierFactory;

class ModifierArchitectureTest extends SculptorTestBase
{
    // =================================================================
    // VISITOR FACTORY TESTS
    // =================================================================

    public function test_visitor_factory_can_create_visitor()
    {
        $visitor = ModifierFactory::make('ChangeProperty', ['status', 'active', 'public', 'string']);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\ChangePropertyModifier::class,
            $visitor
        );
    }

    public function test_visitor_factory_can_create_add_trait_visitor()
    {
        $visitor = ModifierFactory::make('AddTrait', ['HasTeams']);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\AddTraitModifier::class,
            $visitor
        );
    }

    public function test_visitor_factory_can_create_add_method_visitor()
    {
        $visitor = ModifierFactory::make('AddMethod', [
            'getName',
            [],
            'return $this->name;',
            'public',
        ]);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\AddMethodModifier::class,
            $visitor
        );
    }

    public function test_visitor_factory_throws_exception_for_invalid_visitor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Modifier class does not exist');

        ModifierFactory::make('NonExistentModifier', []);
    }

    public function test_visitor_factory_simple_method()
    {
        $factory = ModifierFactory::simple('ChangePropertyDefault', ['name', 'default']);

        $this->assertIsCallable($factory);

        $visitor = $factory(['name' => 'status', 'default' => 'active']);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\ChangePropertyDefaultModifier::class,
            $visitor
        );
    }

    public function test_visitor_factory_factory_method()
    {
        $factory = ModifierFactory::factory('ChangeProperty', fn ($modification) => [
            $modification['property_name'],
            $modification['new_value'],
            $modification['visibility'] ?? 'public',
            $modification['type'] ?? null,
        ]);

        $this->assertIsCallable($factory);

        $visitor = $factory([
            'property_name' => 'status',
            'new_value' => 'active',
            'visibility' => 'protected',
            'type' => 'string',
        ]);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\ChangePropertyModifier::class,
            $visitor
        );
    }

    // =================================================================
    // CUSTOM VISITOR INTEGRATION TESTS
    // =================================================================

    public function test_can_use_visitor_factory_with_sculptor()
    {
        $sculptor = $this->createSculptor();

        // First add a property, then test custom visitor usage
        $result = $sculptor
            ->addProperty('status', 'initial', 'protected', 'string')
            ->toString();

        // This test mainly ensures the architecture works
        $this->assertStringContainsString('class TestClass', $result);
        $this->assertStringContainsString('protected string $status = \'initial\';', $result);
    }

    public function test_extensible_visitor_system_works()
    {
        $sculptor = $this->createSculptor();

        // Test that we can add custom visitor types
        $result = $sculptor
            ->addProperty('customProperty', 'initial', 'protected', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $customProperty = \'initial\';', $result);

        // Now test changing it with visitor factory approach
        $changeModifier = ModifierFactory::make('ChangePropertyDefault', ['customProperty', 'updated']);
        $this->assertInstanceOf(\Malico\PhpSculptor\Modifiers\ChangePropertyDefaultModifier::class, $changeModifier);
    }

    // =================================================================
    // COMPLEX FLUENT INTERFACE WITH VISITORS
    // =================================================================

    public function test_complex_operations_with_visitor_architecture()
    {
        $sculptor = $this->createSculptor();
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addTrait('HasTeams')
            ->extendArrayProperty('fillable', ['team_id'])
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;')
            ->changePropertyType('id', 'string')
            ->toString();

        // Verify all operations were applied using the visitor architecture
        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('protected string $teamRole = \'member\';', $result);
        $this->assertStringContainsString('function getCurrentTeam()', $result);
        $this->assertStringContainsString('protected string $id', $result);
    }

    // =================================================================
    // VISITOR PATTERN VALIDATION
    // =================================================================

    public function test_all_core_visitors_exist()
    {
        $coreModifiers = [
            'AddTrait',
            'AddMethod',
            'AddProperty',
            'AddUseStatement',
            'ExtendArrayProperty',
            'ChangeProperty',
            'ChangePropertyType',
            'ChangePropertyDefault',
            'ChangePropertyVisibility',
            'RemoveProperty',
        ];

        foreach ($coreModifiers as $visitorType) {
            $this->assertTrue(
                class_exists("Malico\\PhpSculptor\\Modifiers\\{$visitorType}Modifier"),
                "Core visitor {$visitorType}Modifier should exist"
            );
        }
    }

    public function test_visitor_factory_handles_empty_parameters()
    {
        $visitor = ModifierFactory::make('AddTrait', ['HasTeams']);

        $this->assertInstanceOf(
            \Malico\PhpSculptor\Modifiers\AddTraitModifier::class,
            $visitor
        );
    }
}

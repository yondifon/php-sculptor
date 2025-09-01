<?php

namespace Malico\PhpSculptor\Tests;

use Malico\PhpSculptor\Sculptor;
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
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function test_can_add_trait()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addTrait('HasTeams')
            ->toString();

        $this->assertStringContainsStringString('use HasTeams;', $result);
    }

    public function test_can_add_to_fillable()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addToFillable(['team_id'])
            ->toString();

        $this->assertStringContainsString('team_id', $result);
    }

    public function test_can_add_method()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addMethod('getTeam', [], 'return $this->team;')
            ->toString();

        $this->assertStringContainsString('public function getTeam()', $result);
        $this->assertStringContainsString('return $this->team;', $result);
    }

    public function test_can_add_property()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addProperty('teamRole', 'member', 'protected', 'string')
            ->toString();

        $this->assertStringContainsString('protected string $teamRole = \'member\';', $result);
    }

    public function test_can_add_use_statement()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->toString();

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
    }

    public function test_fluent_interface()
    {
        $sculptor = new Sculptor($this->testFilePath);
        $result = $sculptor
            ->addUseStatement('Malico\\Teams\\HasTeams')
            ->addTrait('HasTeams')
            ->addToFillable(['team_id'])
            ->addMethod('getCurrentTeam', [], 'return $this->currentTeam;')
            ->toString();

        $this->assertStringContainsString('use Malico\\Teams\\HasTeams;', $result);
        $this->assertStringContainsString('use HasTeams;', $result);
        $this->assertStringContainsString('team_id', $result);
        $this->assertStringContainsString('function getCurrentTeam()', $result);
    }
}


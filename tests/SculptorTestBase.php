<?php

namespace Malico\PhpSculptor\Tests;

use Malico\PhpSculptor\Sculptor;
use PHPUnit\Framework\TestCase;

abstract class SculptorTestBase extends TestCase
{
    protected string $testFilePath;

    protected string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__.'/fixtures';
        $this->testFilePath = $this->fixturesDir.'/TestClass.php';

        $this->createTestFixture();
    }

    protected function tearDown(): void
    {
        if (! file_exists($this->testFilePath)) {
            return;
        }

        unlink($this->testFilePath);
    }

    protected function createTestFixture(): void
    {
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

        @mkdir($this->fixturesDir, 0755, true);
        file_put_contents($this->testFilePath, $testClassCode);
    }

    protected function createSculptor(): Sculptor
    {
        return new Sculptor($this->testFilePath);
    }

    protected function createTempFile(string $prefix = 'sculptor_test_'): string
    {
        return sys_get_temp_dir().'/'.$prefix.uniqid().'.php';
    }

    protected function cleanupTempFile(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        unlink($filePath);
    }

    protected function assertValidPhpSyntax(string $phpCode): void
    {
        $tempFile = $this->createTempFile('syntax_check_');
        file_put_contents($tempFile, $phpCode);

        $output = [];
        $returnCode = 0;
        exec('php -l '.escapeshellarg($tempFile).' 2>&1', $output, $returnCode);

        $this->cleanupTempFile($tempFile);

        $this->assertEquals(
            0,
            $returnCode,
            "Generated PHP code has syntax errors:\n".implode("\n", $output)."\n\nGenerated code:\n".$phpCode
        );
    }
}

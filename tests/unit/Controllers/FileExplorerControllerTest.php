<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\FileExplorer;
use App\Models\TemplateModel;
use App\Models\FilledFilesModel;

/**
 * @internal
 */
final class FileExplorerControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = 'App';
    private TemplateModel $templateModel;
    private FilledFilesModel $filledFilesModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateModel = new TemplateModel();
        $this->filledFilesModel = new FilledFilesModel();
    }

    public function testParseJsonFieldWithString(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('parseJsonField');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, ['{"key": "value"}']);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testParseJsonFieldWithArray(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('parseJsonField');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, [['key' => 'value']]);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testParseJsonFieldWithInvalidString(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('parseJsonField');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, ['invalid json']);
        $this->assertEquals([], $result);
    }

    public function testNormalizeFilledFile(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('normalizeFilledFile');
        $method->setAccessible(true);
        
        $file = [
            'id' => 1,
            'name' => 'Test File',
            'filledData' => '{"field1": "value1"}'
        ];
        
        $result = $method->invokeArgs($controller, [$file]);
        
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test File', $result['name']);
        $this->assertEquals(['field1' => 'value1'], $result['filledData']);
    }

    public function testExtractFieldNameWithString(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractFieldName');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, ['fieldName']);
        $this->assertEquals('fieldName', $result);
    }

    public function testExtractFieldNameWithArray(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractFieldName');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, [['name' => 'fieldName']]);
        $this->assertEquals('fieldName', $result);
    }

    public function testExtractFieldNameWithFieldArray(): void
    {
        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractFieldName');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($controller, [['field' => 'fieldName']]);
        $this->assertEquals('fieldName', $result);
    }

    public function testGetTemplatesWithFilledFiles(): void
    {
        // Insert test data
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ];
        $this->filledFilesModel->insert($filledFileData);

        $controller = new FileExplorer();
        
        // We need to use reflection to test the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getTemplatesWithFilledFiles');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Template', $result[0]['name']);
        $this->assertArrayHasKey('filledFiles', $result[0]);
        $this->assertCount(1, $result[0]['filledFiles']);
    }
}
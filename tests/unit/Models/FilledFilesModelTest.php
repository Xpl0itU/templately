<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\FilledFilesModel;
use App\Models\TemplateModel;

/**
 * @internal
 */
final class FilledFilesModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = ['App'];

    private FilledFilesModel $model;
    private TemplateModel $templateModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new FilledFilesModel();
        $this->templateModel = new TemplateModel();
    }

    public function testFilledFileCreation(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'filledData' => json_encode(['field1' => 'value1', 'field2' => 'value2']),
            'fieldTypes' => json_encode(['field1' => 'text', 'field2' => 'text']),
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $filledFile = $this->model->find($id);
        $this->assertEquals($templateId, $filledFile['templateFileId']);
        $this->assertEquals('Test Filled File', $filledFile['name']);
        $this->assertEquals(['field1' => 'value1', 'field2' => 'value2'], $filledFile['filledData']);
        $this->assertEquals(['field1' => 'text', 'field2' => 'text'], $filledFile['fieldTypes']);
    }

    public function testFilledDataParsing(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'filledData' => json_encode(['name' => 'John Doe', 'company' => 'Example Inc']),
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertIsArray($filledFile['filledData']);
        $this->assertArrayHasKey('name', $filledFile['filledData']);
        $this->assertArrayHasKey('company', $filledFile['filledData']);
        $this->assertEquals('John Doe', $filledFile['filledData']['name']);
    }

    public function testFieldTypesParsing(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'fieldTypes' => json_encode(['name' => 'text', 'avatar' => 'image']),
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertIsArray($filledFile['fieldTypes']);
        $this->assertArrayHasKey('name', $filledFile['fieldTypes']);
        $this->assertArrayHasKey('avatar', $filledFile['fieldTypes']);
        $this->assertEquals('text', $filledFile['fieldTypes']['name']);
        $this->assertEquals('image', $filledFile['fieldTypes']['avatar']);
    }

    public function testFilledDataParsingEmpty(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'filledData' => '',
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertIsArray($filledFile['filledData']);
        $this->assertEmpty($filledFile['filledData']);
    }

    public function testFieldTypesParsingEmpty(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'fieldTypes' => '',
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertIsArray($filledFile['fieldTypes']);
        $this->assertEmpty($filledFile['fieldTypes']);
    }

    public function testCountAll(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        // Create a few filled files
        $filledFileData1 = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ];
        $this->model->insert($filledFileData1);

        $filledFileData2 = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 2',
            'filledData' => json_encode(['field1' => 'value2']),
        ];
        $this->model->insert($filledFileData2);

        $count = $this->model->countAll();
        $this->assertEquals(2, $count);
    }

    public function testGetFilledFilesByTemplate(): void
    {
        $templateId = $this->templateModel->insert([
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);

        // Create multiple filled files for this template
        $this->model->insert([
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ]);

        $this->model->insert([
            'templateFileId' => $templateId,
            'name' => 'Filled File 2',
            'filledData' => json_encode(['field1' => 'value2']),
        ]);

        $filledFiles = $this->model->where('templateFileId', $templateId)->findAll();
        $this->assertCount(2, $filledFiles);
    }

    public function testFilledFileUpdate(): void
    {
        $templateId = $this->templateModel->insert([
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Original Name',
            'filledData' => json_encode(['field1' => 'value1']),
        ];

        $id = $this->model->insert($data);

        $updateData = [
            'name' => 'Updated Name',
            'filledData' => json_encode(['field1' => 'updated value']),
        ];

        $this->model->update($id, $updateData);

        $filledFile = $this->model->find($id);
        $this->assertEquals('Updated Name', $filledFile['name']);
        $this->assertEquals('updated value', $filledFile['filledData']['field1']);
    }

    public function testFilledFileSoftDelete(): void
    {
        $templateId = $this->templateModel->insert([
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Delete Test',
            'filledData' => json_encode(['field1' => 'value1']),
        ];

        $id = $this->model->insert($data);
        
        // Delete
        $this->model->delete($id);

        // Should be gone
        $filledFile = $this->model->find($id);
        $this->assertNull($filledFile);
    }

    public function testComplexFieldTypes(): void
    {
        $templateId = $this->templateModel->insert([
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);

        $data = [
            'templateFileId' => $templateId,
            'name' => 'Complex Types',
            'filledData' => json_encode([
                'text_field' => 'Simple text',
                'number_field' => 42,
                'boolean_field' => true,
            ]),
            'fieldTypes' => json_encode([
                'text_field' => 'text',
                'number_field' => 'number',
                'boolean_field' => 'checkbox',
                'image_field' => 'image',
            ]),
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertEquals('Simple text', $filledFile['filledData']['text_field']);
        $this->assertEquals(42, $filledFile['filledData']['number_field']);
        $this->assertTrue($filledFile['filledData']['boolean_field']);
        $this->assertEquals('image', $filledFile['fieldTypes']['image_field']);
    }
}
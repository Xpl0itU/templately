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
    protected $namespace = 'App';

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
            'filledData' => json_encode(['name' => 'John Doe', 'email' => 'john@example.com']),
        ];

        $id = $this->model->insert($data);
        $filledFile = $this->model->find($id);

        $this->assertIsArray($filledFile['filledData']);
        $this->assertArrayHasKey('name', $filledFile['filledData']);
        $this->assertArrayHasKey('email', $filledFile['filledData']);
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
}
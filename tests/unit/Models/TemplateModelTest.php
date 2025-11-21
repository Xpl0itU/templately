<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\TemplateModel;

/**
 * @internal
 */
final class TemplateModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = ['App'];

    private TemplateModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TemplateModel();
    }

    public function testTemplateCreation(): void
    {
        $data = [
            'name' => 'Test Template',
            'originalFileName' => 'test.docx',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);

        $template = $this->model->find($id);
        $this->assertEquals('Test Template', $template['name']);
        $this->assertEquals('test.docx', $template['originalFileName']);
        $this->assertEquals('/path/to/test.docx', $template['path']);
        $this->assertEquals(1024, $template['size']);
        $this->assertEquals(['field1', 'field2'], $template['templateFields']);
    }

    public function testTemplateFieldsParsing(): void
    {
        $data = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name', 'address', 'phone']),
        ];

        $id = $this->model->insert($data);
        $template = $this->model->find($id);

        $this->assertIsArray($template['templateFields']);
        $this->assertCount(3, $template['templateFields']);
        $this->assertContains('name', $template['templateFields']);
    }

    public function testTemplateFieldsParsingEmpty(): void
    {
        $data = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => '',
        ];

        $id = $this->model->insert($data);
        $template = $this->model->find($id);

        $this->assertIsArray($template['templateFields']);
        $this->assertEmpty($template['templateFields']);
    }

    public function testGetTemplatesWithFilledFiles(): void
    {
        // Create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->model->insert($templateData);

        // Create filled files model and add some files
        $filledFilesModel = new \App\Models\FilledFilesModel();
        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ];
        $filledFilesModel->insert($filledFileData);

        $templates = $this->model->getTemplatesWithFilledFiles();
        $this->assertIsArray($templates);
        $this->assertCount(1, $templates);
        
        $template = $templates[0];
        $this->assertArrayHasKey('filledFiles', $template);
        $this->assertCount(1, $template['filledFiles']);
    }

    public function testGetTemplateWithFilledFiles(): void
    {
        // Create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->model->insert($templateData);

        // Create filled files model and add some files
        $filledFilesModel = new \App\Models\FilledFilesModel();
        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ];
        $filledFilesModel->insert($filledFileData);

        $template = $this->model->getTemplateWithFilledFiles($templateId);
        $this->assertIsArray($template);
        $this->assertArrayHasKey('filledFiles', $template);
        $this->assertCount(1, $template['filledFiles']);
    }

    public function testCountAll(): void
    {
        // Create a few templates
        $templateData1 = [
            'name' => 'Test Template 1',
            'path' => '/path/to/test1.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $this->model->insert($templateData1);

        $templateData2 = [
            'name' => 'Test Template 2',
            'path' => '/path/to/test2.docx',
            'size' => 2048,
            'templateFields' => json_encode(['field3', 'field4']),
        ];
        $this->model->insert($templateData2);

        $count = $this->model->countAll();
        $this->assertEquals(2, $count);
    }

    public function testTemplateUpdate(): void
    {
        $data = [
            'name' => 'Original Name',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ];

        $id = $this->model->insert($data);

        $updateData = [
            'name' => 'Updated Name',
        ];

        $this->model->update($id, $updateData);

        $template = $this->model->find($id);
        $this->assertEquals('Updated Name', $template['name']);
    }

    public function testTemplateSoftDelete(): void
    {
        $data = [
            'name' => 'Delete Test',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ];

        $id = $this->model->insert($data);
        
        // Delete
        $this->model->delete($id);

        // Should be gone
        $template = $this->model->find($id);
        $this->assertNull($template);
    }

    public function testFindByName(): void
    {
        $data = [
            'name' => 'Unique Template Name',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ];

        $this->model->insert($data);

        $template = $this->model->where('name', 'Unique Template Name')->first();
        $this->assertNotNull($template);
        $this->assertEquals('Unique Template Name', $template['name']);
    }

    public function testGetRecentTemplates(): void
    {
        // Create multiple templates with slight time differences
        for ($i = 1; $i <= 5; $i++) {
            $this->model->insert([
                'name' => "Template $i",
                'path' => "/path/to/test$i.docx",
                'size' => 1024,
                'templateFields' => json_encode(['field1']),
                'created_at' => date('Y-m-d H:i:s', time() + $i), // Stagger timestamps
            ]);
        }

        $recent = $this->model->orderBy('id', 'DESC')->findAll(3);
        $this->assertCount(3, $recent);
        $this->assertEquals('Template 5', $recent[0]['name']);
    }
}
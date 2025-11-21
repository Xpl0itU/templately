<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\TemplateModel;

/**
 * @internal
 */
final class TemplateUploadWorkflowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = ['App'];

    public function testTemplateUploadWorkflow(): void
    {
        // This would test the full workflow from upload to storage
        // Since this involves file operations, we'll test the model interactions
        
        $model = new TemplateModel();
        
        $templateData = [
            'name' => 'Integration Test Template',
            'originalFileName' => 'test.docx',
            'path' => '/fake/path/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name', 'company', 'phone']),
        ];

        $id = $model->insert($templateData);
        $this->assertIsNumeric($id);

        $template = $model->find($id);
        $this->assertEquals('Integration Test Template', $template['name']);
        $this->assertEquals('test.docx', $template['originalFileName']);
        $this->assertEquals('/fake/path/test.docx', $template['path']);
        $this->assertEquals(1024, $template['size']);
        $this->assertEquals(['name', 'company', 'phone'], $template['templateFields']);
    }

    public function testTemplateDeletionWithFiles(): void
    {
        $templateModel = new TemplateModel();
        $filledFilesModel = new \App\Models\FilledFilesModel();
        
        // Create a temporary test file
        $tempPath = WRITEPATH . 'uploads/test_' . time() . '.txt';
        file_put_contents($tempPath, 'test content');
        
        // Create a template with real file path
        $templateData = [
            'name' => 'Test Template for Deletion',
            'originalFileName' => 'test.docx',
            'path' => $tempPath,
            'size' => 1024,
            'templateFields' => json_encode(['name', 'company']),
        ];
        $templateId = $templateModel->insert($templateData);

        // Create associated filled files
        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['name' => 'John Doe']),
        ];
        $filledFileId = $filledFilesModel->insert($filledFileData);

        // Verify the template and file exist
        $template = $templateModel->find($templateId);
        $this->assertNotNull($template);
        $this->assertTrue(file_exists($tempPath));

        $filledFile = $filledFilesModel->find($filledFileId);
        $this->assertNotNull($filledFile);

        // Test the deleteTemplateWithFiles method
        $result = $templateModel->deleteTemplateWithFiles($templateId);
        $this->assertTrue($result);

        // Verify the template and file are deleted
        $template = $templateModel->find($templateId);
        $this->assertNull($template);

        $filledFile = $filledFilesModel->find($filledFileId);
        $this->assertNull($filledFile);
        
        // Verify physical file is deleted
        $this->assertFalse(file_exists($tempPath));
    }
}
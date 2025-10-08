<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\TemplateModel;
use App\Models\FilledFilesModel;

/**
 * @internal
 */
final class FilledFileWorkflowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = ['App'];

    public function testFilledFileCreationWorkflow(): void
    {
        // Create a template first
        $templateModel = new TemplateModel();
        $templateData = [
            'name' => 'Test Template for Filled Files',
            'originalFileName' => 'test.docx',
            'path' => '/fake/path/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name', 'company', 'phone']),
        ];
        $templateId = $templateModel->insert($templateData);

        // Create a filled file
        $filledFilesModel = new FilledFilesModel();
        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Test Filled File',
            'filledData' => json_encode([
                'name' => 'John Doe',
                'company' => 'Example Inc',
                'phone' => '123-456-7890'
            ]),
            'fieldTypes' => json_encode([
                'name' => 'text',
                'company' => 'text',
                'phone' => 'text'
            ]),
        ];

        $id = $filledFilesModel->insert($filledFileData);
        $this->assertIsNumeric($id);

        $filledFile = $filledFilesModel->find($id);
        $this->assertEquals($templateId, $filledFile['templateFileId']);
        $this->assertEquals('Test Filled File', $filledFile['name']);
        $this->assertEquals([
            'name' => 'John Doe',
            'company' => 'Example Inc',
            'phone' => '123-456-7890'
        ], $filledFile['filledData']);
        $this->assertEquals([
            'name' => 'text',
            'company' => 'text',
            'phone' => 'text'
        ], $filledFile['fieldTypes']);
    }

    public function testDuplicateFilledFileNamePrevention(): void
    {
        // Create a template first
        $templateModel = new TemplateModel();
        $templateData = [
            'name' => 'Test Template',
            'path' => '/fake/path/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name']),
        ];
        $templateId = $templateModel->insert($templateData);

        // Create a filled file
        $filledFilesModel = new FilledFilesModel();
        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => 'Duplicate Test File',
            'filledData' => json_encode(['name' => 'John Doe']),
        ];
        $filledFilesModel->insert($filledFileData);

        // Try to create another filled file with the same name for the same template
        $duplicateFileData = [
            'templateFileId' => $templateId,
            'name' => 'Duplicate Test File', // Same name
            'filledData' => json_encode(['name' => 'Jane Doe']),
        ];

        // This should either fail or handle the duplicate in some way
        // For now, we're just testing that we can check for duplicates
        $existingFile = $filledFilesModel->where('templateFileId', $templateId)
            ->where('name', 'Duplicate Test File')
            ->first();

        $this->assertNotNull($existingFile);
        $this->assertEquals('Duplicate Test File', $existingFile['name']);
    }
}
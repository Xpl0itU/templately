<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Dashboard;
use App\Models\TemplateModel;
use App\Models\FilledFilesModel;

/**
 * @internal
 */
final class DashboardControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    private TemplateModel $templateModel;
    private FilledFilesModel $filledFilesModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateModel = new TemplateModel();
        $this->filledFilesModel = new FilledFilesModel();
    }

    public function testTemplateCounting(): void
    {
        // Insert test templates
        $templateData1 = [
            'name' => 'Test Template 1',
            'path' => '/path/to/test1.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $this->templateModel->insert($templateData1);

        $templateData2 = [
            'name' => 'Test Template 2',
            'path' => '/path/to/test2.docx',
            'size' => 2048,
            'templateFields' => json_encode(['field3', 'field4']),
        ];
        $this->templateModel->insert($templateData2);

        $count = $this->templateModel->countAll();
        $this->assertEquals(2, $count);
    }

    public function testFilledFilesCounting(): void
    {
        // First create a template
        $templateData = [
            'name' => 'Test Template',
            'path' => '/path/to/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1', 'field2']),
        ];
        $templateId = $this->templateModel->insert($templateData);

        // Insert test filled files
        $filledFileData1 = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 1',
            'filledData' => json_encode(['field1' => 'value1']),
        ];
        $this->filledFilesModel->insert($filledFileData1);

        $filledFileData2 = [
            'templateFileId' => $templateId,
            'name' => 'Filled File 2',
            'filledData' => json_encode(['field1' => 'value2']),
        ];
        $this->filledFilesModel->insert($filledFileData2);

        $count = $this->filledFilesModel->countAll();
        $this->assertEquals(2, $count);
    }
}
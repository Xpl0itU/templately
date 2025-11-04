<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Template Files Model
 *
 * Manages template file records with parsed field definitions
 *
 * @property string $table Database table name
 * @property string $primaryKey Primary key field
 * @property array $allowedFields Allowed fields for insert/update
 */
class TemplateFilesModel extends Model
{
    protected $table = 'templateFiles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'path', 'size', 'type', 'createdAt', 'templateFields'];
}

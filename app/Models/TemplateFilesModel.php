<?php

namespace App\Models;
use CodeIgniter\Model;

class TemplateFilesModel extends Model
{
    protected $table = 'templateFiles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'path', 'size', 'type', 'createdAt'];
}
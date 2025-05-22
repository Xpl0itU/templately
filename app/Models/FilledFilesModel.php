<?php

namespace App\Models;
use CodeIgniter\Model;

class FilledFilesModel extends Model
{
    protected $table = 'filledFiles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'templateFileId', 'filledData', 'createdAt'];
}
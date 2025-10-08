<?php

namespace App\Models;

use CodeIgniter\Model;

class AclPermissionModel extends Model
{
    protected $table = 'acl_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'description',
        'bit_value',
    ];
    protected $useTimestamps = false;
    
    /**
     * Get permission by name
     * 
     * @param string $name Permission name
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByName(string $name): ?array
    {
        return $this->where('name', $name)->first();
    }
    
    /**
     * Get permission by bit value
     * 
     * @param int $bitValue Bit value of the permission
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByBitValue(int $bitValue): ?array
    {
        return $this->where('bit_value', $bitValue)->first();
    }
    
    /**
     * Get all permissions
     * 
     * @return array Array of all permissions
     */
    public function getAllPermissions(): array
    {
        return $this->findAll();
    }
}
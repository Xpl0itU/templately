<?php

namespace Config;

use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function permissions(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('permissions');
        }

        return new \App\Libraries\PermissionManager();
    }

    public static function permissionManager(bool $getShared = true)
    {
        return static::permissions($getShared);
    }
}

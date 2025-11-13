<?php

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'contributor';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'manager' => [
            'title'       => 'Manager',
            'description' => 'Oversee and approve content.',
        ],
        'editor' => [
            'title'       => 'Editor',
            'description' => 'Edit all documents and templates.',
        ],
        'contributor' => [
            'title'       => 'Contributor',
            'description' => 'Create and manage own documents.',
        ],
        'viewer' => [
            'title'       => 'Viewer',
            'description' => 'Read-only access to documents.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'        => 'Can access the sites admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
    'templates.create'    => 'Can create new templates',
    'templates.edit'      => 'Can edit existing templates',
    'templates.delete'    => 'Can delete templates',
    'templates.view'      => 'Can view templates',
    'filled-files.create' => 'Can create filled files from templates',
    'filled-files.edit'   => 'Can edit filled files',
    'filled-files.delete' => 'Can delete filled files',
    'filled-files.view'   => 'Can view filled files',
    'acl.manage'          => 'Can manage advanced ACL permissions',
    'user-groups.view'           => 'Can access the user groups area',
    'user-groups.create'         => 'Can create user groups',
    'user-groups.edit'           => 'Can edit user groups',
    'user-groups.delete'         => 'Can delete user groups',
    'user-groups.view-members'   => 'Can view members inside a user group',
    'user-groups.manage-members' => 'Can add or remove members from user groups',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'users.*',
            'templates.*',
            'filled-files.*',
            'acl.manage',
            'user-groups.*',
        ],
        'manager' => [
            'admin.access',
            'templates.view',
            'templates.edit',
            'filled-files.*',
            'user-groups.view',
            'user-groups.view-members',
        ],
        'editor' => [
            'templates.*',
            'filled-files.*',
        ],
        'contributor' => [
            'templates.view',
            'templates.create',
            'filled-files.create',
            'filled-files.edit',
            'filled-files.view',
            'filled-files.delete',
        ],
        'viewer' => [
            'templates.view',
            'filled-files.view',
        ],
    ];
}

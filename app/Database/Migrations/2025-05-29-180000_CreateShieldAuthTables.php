<?php

namespace App\Database\Migrations;

if (! class_exists('CodeIgniter\\Shield\\Database\\Migrations\\CreateAuthTables')) {
	require_once ROOTPATH . 'vendor/codeigniter4/shield/src/Database/Migrations/2020-12-28-223112_create_auth_tables.php';
}

use CodeIgniter\Shield\Database\Migrations\CreateAuthTables as ShieldCreateAuthTables;
use Config\Database;

class CreateShieldAuthTables extends ShieldCreateAuthTables
{
	public function up(): void
	{
		$db         = Database::connect();
		$tableName  = $db->getPrefix() . 'users';
		$tableNames = $db->listTables();

		if (in_array($tableName, $tableNames, true)) {
			log_message('debug', 'Skipping Shield auth migration: users table already exists');

			return;
		}

		parent::up();
	}
}

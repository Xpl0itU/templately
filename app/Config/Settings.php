<?php

namespace App\Config;

use CodeIgniter\Settings\Config\Settings as BaseSettings;

if (! class_exists(Settings::class, false)) {
    class Settings extends BaseSettings
    {
        /**
         * Use the in-memory array handler by default during tests to avoid
         * touching the database when the settings table has not been created.
         */
        public $handlers = ['array'];
    }
}

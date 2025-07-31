<?php declare(strict_types=1);

/*
 * This file is part of the Endereco Shopware 6 Client.
 *
 * (c) Endereco UG (haftungsbeschränkt)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set test environment
if (!defined('TEST_ENVIRONMENT')) {
    define('TEST_ENVIRONMENT', true);
}

// Error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Memory limit for tests
ini_set('memory_limit', '-1');
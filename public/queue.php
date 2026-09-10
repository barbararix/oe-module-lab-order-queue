<?php

/**
 * Front controller for Lab Order Queue module.
 * Routes ?action= parameter to QueueController methods.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once dirname(__DIR__, 4) . '/globals.php';
require_once $GLOBALS['srcdir'] . '/forms.inc.php';

use Juggernaut\OpenEMR\Modules\LabOrderQueue\Controller\QueueController;

$controller = new QueueController();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$controller->dispatch($action);

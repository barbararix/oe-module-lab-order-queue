<?php

/**
 * Module setup / configuration page.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once dirname(__DIR__, 3) . '/globals.php';

use OpenEMR\Core\Header;

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Lab Order Queue — Configuration'); ?></title>
    <?php Header::setupHeader(); ?>
</head>
<body class="body_top">
    <div class="container mt-3">
        <h2><?php echo xlt('Lab Order Queue Module'); ?></h2>
        <p><?php echo xlt('Version'); ?>: 1.0.0</p>
        <hr />
        <p><?php echo xlt('This module enables pre-encounter lab order queuing. Providers can place lab orders from a patient chart before an encounter exists. When the patient arrives and an encounter is created, queued orders can be attached.'); ?></p>
        <div class="alert alert-info">
            <strong><?php echo xlt('Status'); ?>:</strong>
            <?php echo xlt('Module is active. Configuration options will be added in a future release.'); ?>
        </div>
    </div>
</body>
</html>

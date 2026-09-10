<?php

/**
 * Module Manager Listener for Lab Order Queue module.
 * Handles install, enable, disable, and SQL lifecycle events.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

/*
 * Do not declare a namespace.
 * If you want Laminas manager to set namespace set it in getModuleNamespace.
 */

use OpenEMR\Core\AbstractModuleActionListener;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $methodName
     * @param string $modId
     * @param string $currentActionStatus
     * @return string
     */
    public function moduleManagerAction($methodName, $modId, string $currentActionStatus = 'Success'): string
    {
        if (method_exists(self::class, $methodName)) {
            return self::$methodName($modId, $currentActionStatus);
        }

        return "Module method $methodName does not exist.";
    }

    /**
     * @return string
     */
    public static function getModuleNamespace(): string
    {
        return 'Juggernaut\\OpenEMR\\Modules\\LabOrderQueue\\';
    }

    /**
     * @return ModuleManagerListener
     */
    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function install($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function enable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function disable($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Called when module is unregistered.
     *
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function unregister($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Reset module — drops the lab_order_queue table.
     * Only runs if the module is currently disabled.
     * Follows the weno module pattern.
     *
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function reset_module($modId, $currentActionStatus): mixed
    {
        $logMessage = '';

        if (!self::getModuleState($modId)) {
            $sql = "DROP TABLE IF EXISTS `lab_order_queue`";
            $rtn = sqlQuery($sql);
            $logMessage .= "DROP TABLE `lab_order_queue`: " . (empty($rtn) ? "Success" : "Failed") . "\n";

            error_log(text($logMessage));
        } else {
            $logMessage = "Module must be disabled before reset.\n";
        }

        return text($logMessage);
    }

    /**
     * Called by module manager after install_sql runs.
     * The table.sql at module root handles initial table creation
     * via OpenEMR's conditional SQL directives (#IfNotTable, etc.).
     * This method is for any additional post-install SQL if needed.
     *
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function install_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Called when sql/ directory contains version upgrade scripts.
     *
     * @param $modId
     * @param $currentActionStatus
     * @return mixed
     */
    private function upgrade_sql($modId, $currentActionStatus): mixed
    {
        return $currentActionStatus;
    }

    /**
     * Check if module is currently disabled.
     *
     * @param $modId
     * @return bool
     */
    private static function getModuleState($modId): bool
    {
        $sql = "SELECT mod_active FROM modules WHERE mod_id = ?";
        $result = sqlQuery($sql, [$modId]);
        return !empty($result['mod_active']);
    }
}

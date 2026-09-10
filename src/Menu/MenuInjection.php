<?php

/**
 * Menu item factory for Lab Order Queue module.
 * Builds stdClass menu items matching OpenEMR's menu system structure.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace Juggernaut\OpenEMR\Modules\LabOrderQueue\Menu;

class MenuInjection
{
    private const MODULE_PUBLIC_PATH = '/interface/modules/custom_modules/oe-module-lab-order-queue/public/';

    /**
     * Build the patient chart tab menu item.
     * Injected into PatientMenuEvent::MENU_UPDATE.
     *
     * @return \stdClass
     */
    public static function buildPatientMenuItem(): \stdClass
    {
        $menuItem = new \stdClass();
        $menuItem->label = xlt('Lab Queue');
        $menuItem->url = $GLOBALS['webroot'] . self::MODULE_PUBLIC_PATH . 'queue.php?action=list';
        $menuItem->menu_id = 'mod_lab_queue';
        $menuItem->target = 'mod';
        $menuItem->requirement = 1; // requires a patient to be selected
        $menuItem->children = [];

        return $menuItem;
    }

    /**
     * Build the top-level Procedures menu child item.
     * Injected into MenuEvent::MENU_UPDATE under menu_id 'proimg'.
     *
     * @return \stdClass
     */
    public static function buildProceduresMenuItem(): \stdClass
    {
        $menuItem = new \stdClass();
        $menuItem->requirement = 0;
        $menuItem->target = 'mod';
        $menuItem->menu_id = 'mod_lab_queue_global';
        $menuItem->label = xlt('Lab Queue');
        $menuItem->url = self::MODULE_PUBLIC_PATH . 'queue.php?action=global_list';
        $menuItem->children = [];
        $menuItem->acl_req = ['patients', 'lab'];
        $menuItem->global_req = [];

        return $menuItem;
    }
}

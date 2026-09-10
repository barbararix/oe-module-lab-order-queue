<?php

/**
 * Bootstrap class for Lab Order Queue module.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis <sherwingaddis@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace Juggernaut\OpenEMR\Modules\LabOrderQueue;

use Juggernaut\OpenEMR\Modules\LabOrderQueue\Menu\MenuInjection;
use Juggernaut\OpenEMR\Modules\LabOrderQueue\Repository\LabOrderQueueRepository;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Kernel;
use OpenEMR\Events\Encounter\EncounterFormsListRenderEvent;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Menu\PatientMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class Bootstrap
{
    private const MODULE_INSTALLATION_PATH = '/interface/modules/custom_modules/';
    private const MODULE_NAME = 'oe-module-lab-order-queue';

    private EventDispatcherInterface $eventDispatcher;
    private ?Kernel $kernel;
    private Environment $twig;
    private string $moduleDirectoryName;

    public function __construct(EventDispatcherInterface $dispatcher, ?Kernel $kernel = null)
    {
        $this->eventDispatcher = $dispatcher;
        $this->kernel = $kernel;
        $this->moduleDirectoryName = basename(dirname(__FILE__, 2));
        $twigContainer = new TwigContainer($this->getTemplatePath(), $kernel);
        $this->twig = $twigContainer->getTwig();
    }

    public function subscribeToEvents(): void
    {
        // 1. Patient chart tab: "Lab Queue"
        $this->eventDispatcher->addListener(
            PatientMenuEvent::MENU_UPDATE,
            [$this, 'addPatientMenuItem']
        );

        // 2. Top-level Procedures menu: "Lab Queue"
        $this->eventDispatcher->addListener(
            MenuEvent::MENU_UPDATE,
            [$this, 'addProceduresMenuItem']
        );

        // 3. Encounter alert banner for pending queued orders
        $this->eventDispatcher->addListener(
            EncounterFormsListRenderEvent::EVENT_SECTION_RENDER_PRE,
            [$this, 'renderEncounterAlert']
        );
    }

    /**
     * Add "Lab Queue" tab to patient chart menu.
     */
    public function addPatientMenuItem(PatientMenuEvent $event): PatientMenuEvent
    {
        $menu = $event->getMenu();
        $menu[] = MenuInjection::buildPatientMenuItem();
        $event->setMenu($menu);

        return $event;
    }

    /**
     * Add "Lab Queue" under the Procedures top-level menu.
     */
    public function addProceduresMenuItem(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        foreach ($menu as $item) {
            if (($item->menu_id ?? '') === 'proimg') {
                $item->children[] = MenuInjection::buildProceduresMenuItem();
                break;
            }
        }

        $event->setMenu($menu);

        return $event;
    }

    /**
     * Render encounter alert banner if patient has pending queued orders.
     * This listener echoes HTML directly into the encounter forms list.
     */
    public function renderEncounterAlert(EncounterFormsListRenderEvent $event): void
    {
        $pid = $event->getPid();
        if (empty($pid)) {
            return;
        }

        try {
            $repository = new LabOrderQueueRepository();
            $pendingCount = $repository->countPendingForPatient((int) $pid);

            if ($pendingCount > 0) {
                echo $this->twig->render('queue/encounter_alert.html.twig', [
                    'pendingCount' => $pendingCount,
                    'pid' => $pid,
                    'modulePublicPath' => $this->getURLPath(),
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail — don't break the encounter view
            error_log('LabOrderQueue encounter alert error: ' . $e->getMessage());
        }
    }

    public function getTwig(): Environment
    {
        return $this->twig;
    }

    public function getTemplatePath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
    }

    public function getURLPath(): string
    {
        return $GLOBALS['webroot'] . self::MODULE_INSTALLATION_PATH . $this->moduleDirectoryName . '/public/';
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}

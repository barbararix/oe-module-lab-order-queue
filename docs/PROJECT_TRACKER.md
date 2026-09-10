# Lab Order Queue Module — Project Tracker

## Overview
Module: `oe-module-lab-order-queue`
Version: 1.0.0
OpenEMR Target: 7.0.3
Client: Tarevo / Affordable Custom EHR

## Purpose
Allow providers to place lab orders from a patient's chart before an encounter exists.
Orders are queued and attached to encounters when the patient arrives.

---

## Phase Completion Log

### Phase 1: Module Scaffolding
Status: COMPLETE (2026-04-15)
- [x] composer.json
- [x] info.txt
- [x] openemr.bootstrap.php
- [x] Bootstrap.php (event wiring stub — listeners in Phase 7)
- [x] ModuleManagerListener.php (install/enable/disable + install_sql)
- [x] moduleConfig.php
- [x] setup.php

### Phase 2: Database — Queue Table
Status: COMPLETE (2026-04-15)
- [x] sql/1_0_0-install.sql (lab_order_queue table)
- [x] Verified table creation on local openemr703 database

### Phase 3: Repository Layer
Status: COMPLETE (2026-04-15)
- [x] Exception/LabOrderQueueException.php
- [x] LabOrderQueueRepository.php
  - [x] createQueuedOrder() — transactional insert into procedure_order + procedure_order_code + lab_order_queue
  - [x] getQueuedOrdersForPatient() — pending orders with joins to procedure_providers, users
  - [x] getQueuedOrderById() — full detail single record
  - [x] getOrderProcedures() — procedure_order_code rows for an order
  - [x] attachToEncounter() — updates encounter_id, calls addForm(), marks attached
  - [x] cancelQueuedOrder() — marks inactive + cancelled
  - [x] countPendingForPatient() — for encounter alert banner (auto-syncs first)
  - [x] getAllPendingOrders() — global view with patient_data join
  - [x] syncPendingStatus() — reconciles queue with core-attached orders

### Phase 4: Service Layer
Status: COMPLETE (2026-04-15)
- [x] LabOrderQueueService.php
  - [x] createQueuedOrder() — validates then delegates to repository
  - [x] validateOrderData() — checks patient, provider, lab (active), procedures
  - [x] attachToEncounter() — validates encounter ownership + patient match
  - [x] cancelQueuedOrder() — validates queue ID
  - [x] getQueuedOrderById() — enriches with procedures
  - [x] getActiveLabProviders() — for form dropdown
  - [x] getOrderingProviders() — for form dropdown
  - [x] getPatientEncounters() — for attach dropdown

### Phase 5: Controller
Status: COMPLETE (2026-04-15)
- [x] QueueController.php
  - [x] dispatch() — action router with ACL gate
  - [x] showForm() — new/edit order form
  - [x] saveOrder() — POST with CSRF, builds procedures from arrays
  - [x] listQueue() — patient-specific pending orders
  - [x] globalList() — all-patient pending orders
  - [x] viewOrder() — read-only order detail
  - [x] attachOrder() — POST with CSRF
  - [x] cancelOrder() — POST with CSRF
  - [x] ACL: AclMain::aclCheckCore('patients', 'lab') on all routes
  - [x] CSRF: verifyCsrfToken on every POST, supports form + header token
- [x] public/queue.php (front controller) — loads globals + forms.inc.php

### Phase 6: Twig Templates
Status: COMPLETE (2026-04-15)
- [x] base.html.twig — setupHeader(['datetime-picker']), block structure, error display
- [x] queue/order_form.html.twig — full order form with fieldsets, procedure rows, popup integration, attach-from-detail
- [x] queue/queue_list.html.twig — patient + global view, attach dropdown per encounter, cancel, AJAX actions
- [x] queue/encounter_alert.html.twig — warning banner with pending count + "View & Attach" link
- [ ] config/settings.html.twig — deferred (setup.php serves as config for now)

### Phase 7: Event Listeners
Status: COMPLETE (2026-04-15)
- [x] PatientMenuEvent::MENU_UPDATE — "Lab Queue" tab in patient chart (requirement=1, target=mod)
- [x] MenuEvent::MENU_UPDATE — "Lab Queue" child under Procedures menu (menu_id=proimg, ACL patients/lab)
- [x] EncounterFormsListRenderEvent::EVENT_SECTION_RENDER_PRE — alert banner echoed into encounter forms list
- [x] Menu/MenuInjection.php — static factory for stdClass menu items
- [x] Bootstrap.php updated with all 3 listeners + encounter alert with try/catch safety

### Phase 8: Order Form
Status: COMPLETE (2026-04-15)
- [x] Lab provider picker (select from procedure_providers)
- [x] Procedure search (reuses find_order_popup.php via dlgopen + set_proc_type callback)
- [x] Diagnosis codes (optional — editable text input per procedure row)
- [x] Clinical history notes
- [x] Order priority (normal/high/stat)
- [x] PSC hold
- [x] Specimen fasting
- [x] public/assets/js/queue.js — LabOrderQueue object + global set_proc_type/set_proc_html callbacks

### Phase 9: Attachment Workflow
Status: COMPLETE (2026-04-15)
- [x] AJAX attach endpoint — QueueController::attachOrder() via queue.php?action=attach
- [x] procedure_order.encounter_id update — LabOrderQueueRepository::attachToEncounter()
- [x] addForm() registration — called in repository with fallback direct insert
- [x] lab_order_queue status update — sets 'attached' + attached_at timestamp
- [x] Encounter alert links to queue list for attachment; row fade-out on success

---

## Sprint Notes

### Sprint 1 — 2026-04-15
Target: Phases 1-3 (Scaffolding + Database + Repository)
Notes:
- Completed all scaffolding files (composer.json, bootstrap, ModuleManagerListener, etc.)
- Created and verified lab_order_queue table on local openemr703 database
- Built full repository with 8 methods using QueryUtils (no legacy sqlQuery/sqlStatement)
- All PHP files pass syntax check
- Added 2 bonus methods beyond plan: countPendingForPatient(), getAllPendingOrders()
- Namespace: Juggernaut\OpenEMR\Modules\LabOrderQueue


### Sprint 2 — 2026-04-15
Target: Phases 4-5 (Service + Controller)
Notes:
- Service validates lab provider active status, encounter-patient ownership
- Diagnosis NOT required at queue time (deferred to transmit)
- Controller uses match() dispatch with 7 actions
- CSRF accepts both form field and X-CSRF-TOKEN header for AJAX
- Front controller includes forms.inc.php for addForm() availability
- 3 helper queries in service: lab providers, ordering providers, patient encounters
- All 3 files pass syntax check


### Sprint 3 — 2026-04-15
Target: Phases 6 + 8 (Templates + Order Form UI)
Notes:
- 4 Twig templates created extending base.html.twig
- queue_list.html.twig handles both patient-specific and global views via globalView flag
- order_form.html.twig uses HTML <template> element for JS procedure row cloning
- encounter_alert.html.twig is a standalone partial for event listener injection
- queue.js provides LabOrderQueue object + global set_proc_type() callback for find_order_popup.php
- All text escaped with |xlt, |text, |attr per OpenEMR Twig conventions
- ES6+ syntax: const/let, arrow functions, template literals, fetch API
- All 10 PHP files pass syntax check


### Sprint 4 — 2026-04-15
Target: Phases 7 + 9 (Event Listeners + Attachment Workflow)
Notes:
- Created MenuInjection.php with static factory methods for menu items
- Bootstrap.php wired 3 Symfony event listeners
- Patient chart tab uses requirement=1 (patient must be selected)
- Procedures menu item injected under menu_id='proimg' with ACL ['patients','lab']
- Encounter alert uses try/catch to never break the encounter view
- All 24 unit tests still pass after changes
- All phases (1-9) now complete


---

## Post-Release Fixes

### Fix: table.sql + version.php (2026-04-17)
- Added `table.sql` at module root with `#IfNotTable` directive (required by module installer)
- Added `version.php` with `$v_major/$v_minor/$v_patch` (required by `getModuleVersionFromFile()`)
- Added `reset_module()` to ModuleManagerListener (drops table on uninstall, follows weno pattern)
- Without these, `sql_run` never got set to 1 and module install failed silently

### Fix: Twig truncate filter (2026-04-17)
- Replaced `|truncate(40)` with `|slice(0, 40)` — truncate not available in OpenEMR Twig

### Fix: Null encounter date deprecation (2026-04-21)
- Core `single_order_results.inc.php` line 511: `substr(null, 0, 10)` deprecated in PHP 8.1+
- Added `!empty()` guard — encounter date renders blank for queued orders with `encounter_id=0`

### Fix: Core/module attach sync (2026-04-21)
- Core `forms.php` (lines 280-298) attaches orphaned orders via JS confirm dialog
- This updated `procedure_order.encounter_id` but not `lab_order_queue.status`
- Added `syncPendingStatus()` — auto-reconciles before counting or listing pending orders
- Yellow alert banner now disappears after core attaches the order

---

## Decisions Log
- 2026-04-02: Use separate `lab_order_queue` table for queue tracking (not just encounter_id=0)
- 2026-04-02: No transmission at queue time — transmit only after encounter attachment
- 2026-04-02: Module-based approach — no modifications to core procedure_order form
- 2026-04-03: Menu injection via Symfony listeners (PatientMenuEvent + MenuEvent), no JSON edits
- 2026-04-21: syncPendingStatus() reconciles queue with core's attach pathway

## Known Risks
- Existing reports joining procedure_order on encounter_id must handle encounter_id=0 rows
- Diagnosis codes deferred to attachment/transmit time — provider must complete before sending
- find_order_popup.php reuse may need adjustments for non-encounter context
- Core forms.php attach dialog runs before module alert — both pathways now reconciled via sync

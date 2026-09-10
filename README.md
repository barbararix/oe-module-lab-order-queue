
# OpenEMR Lab Order Queue

<img width="1913" height="729" alt="Screenshot from 2026-09-10 15-18-53" src="https://github.com/user-attachments/assets/b225e50e-3b90-4bc7-b5ab-7ea423e51e1e" />

Pre-encounter lab order management for OpenEMR. Place lab orders from the patient chart before an encounter exists, then attach them when the patient arrives.

**Module Manager name:** Lab Order Queue - Pre-encounter lab order management  
**Package version:** 1.0.0 (`version.php`)  
**Install directory name:** `oe-module-lab-order-queue`  
**Namespace:** `Juggernaut\OpenEMR\Modules\LabOrderQueue`  
**Author:** Sherwin Gaddis / Juggernaut Systems Express

## Compatibility

| Target | Status |
|--------|--------|
| **OpenEMR 7.0.x** (PHP 8.1+) | Supported — Twig, QueryUtils, ACL, Symfony events |
| **OpenEMR 8.x** | Uses standard module events; validate in your environment |

- PHP **8.1+**
- No Composer runtime dependencies beyond OpenEMR core

## Features

- **Patient chart → Lab Queue** tab and **Procedures → Lab Queue** menu
- Create queued lab orders without an encounter (`procedure_order` + `lab_order_queue`)
- Lab provider, procedure search (core popup), priority, PSC hold, fasting, clinical notes
- Patient-specific and global pending queues
- Attach queued orders to an encounter (AJAX) or cancel them
- Encounter forms list banner when pending queue items exist
- Status sync with core attach pathway for orphaned orders
- Unit/integration tests under `tests/`

## Installation

1. Place under custom modules:

   ```bash
   cd /path/to/openemr/interface/modules/custom_modules
   git clone https://github.com/juggernautsei/oe-module-lab-order-queue.git
   ```

2. In OpenEMR: **Modules → Module Manager** → register, install (`table.sql`), enable.
3. ACL: routes require `patients` / `lab`.
4. Open a patient chart → **Lab Queue**, or **Procedures → Lab Queue**.

### Database (`table.sql`)

| Table | Purpose |
|-------|---------|
| `lab_order_queue` | Tracks queued orders (`pending` / `attached` / `cancelled`) linked to `procedure_order` |

Install SQL also lives in `sql/1_0_0-install.sql`. Uninstall drops the queue table via `ModuleManagerListener`.

## Security

- ACL gate: `patients` / `lab` on controller routes
- CSRF on POST (form field and `X-CSRF-TOKEN` for AJAX)
- SQL via module repository with bound parameters (`QueryUtils`)
- Twig escaping (`xlt` / `text` / `attr`)

## Directory layout

```text
oe-module-lab-order-queue/
├── composer.json
├── info.txt
├── moduleConfig.php
├── ModuleManagerListener.php
├── openemr.bootstrap.php
├── setup.php
├── table.sql
├── version.php
├── phpunit.xml
├── public/
│   ├── queue.php
│   └── assets/js/queue.js
├── sql/
├── src/
│   ├── Bootstrap.php
│   ├── Controller/
│   ├── Exception/
│   ├── Menu/
│   ├── Repository/
│   └── Service/
├── templates/
├── tests/
└── docs/
```

## License

GNU General Public License v3.0 (GPL-3.0-only). See [LICENSE](LICENSE).

## Support

Sherwin Gaddis / Juggernaut Systems Express  
https://github.com/juggernautsei/oe-module-lab-order-queue

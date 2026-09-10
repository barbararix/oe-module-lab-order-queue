/**
 * Lab Order Queue - Order Form JavaScript
 *
 * Handles:
 * - AJAX form save
 * - Procedure popup (find_order_popup.php) callback integration
 * - Add/remove procedure rows
 * - Attach from detail view
 *
 * @package   OpenEMR
 * @author    Sherwin Gaddis <sherwingaddis@gmail.com>
 * @copyright Copyright (c) 2026 Sherwin Gaddis
 */

const LabOrderQueue = {
    config: {},
    gbl_formseq: 0,

    init(options) {
        this.config = options;
        this.bindEvents();
    },

    bindEvents() {
        // Save button
        const btnSave = document.getElementById('btnSave');
        if (btnSave) {
            btnSave.addEventListener('click', () => this.saveOrder());
        }

        // Add procedure row (blank)
        const addBtn = document.getElementById('addProcBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addProcedureRow());
        }

        // Search lab compendium popup
        const searchBtn = document.getElementById('searchProcBtn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.openProcedureSearch());
        }

        // Remove procedure rows (delegated)
        const container = document.getElementById('proceduresContainer');
        if (container) {
            container.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.remove-proc');
                if (removeBtn) {
                    const row = removeBtn.closest('.proc-row');
                    if (row && confirm(xl('Remove this procedure?'))) {
                        row.remove();
                    }
                }
            });
        }

        // Attach from detail view
        const btnAttach = document.getElementById('btnAttach');
        if (btnAttach) {
            btnAttach.addEventListener('click', () => this.attachFromDetail());
        }
    },

    /**
     * Save the queued order via AJAX POST.
     */
    saveOrder() {
        const form = document.getElementById('queueOrderForm');
        if (!form) {
            return;
        }

        // Basic client-side validation
        const labId = form.querySelector('[name="lab_id"]').value;
        const providerId = form.querySelector('[name="provider_id"]').value;
        const procCodes = form.querySelectorAll('[name="proc_code[]"]');

        if (!labId) {
            alert(xl('Please select a lab provider.'));
            return;
        }
        if (!providerId) {
            alert(xl('Please select an ordering provider.'));
            return;
        }
        if (procCodes.length === 0) {
            alert(xl('Please add at least one procedure.'));
            return;
        }

        const spinner = document.getElementById('saveSpinner');
        const btnSave = document.getElementById('btnSave');
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        if (btnSave) {
            btnSave.disabled = true;
        }

        const formData = new FormData(form);
        formData.append('csrf_token_form', this.config.csrfToken);

        if (typeof top.restoreSession === 'function') {
            top.restoreSession();
        }

        fetch(this.config.modulePublicPath + 'queue.php?action=save', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.config.csrfToken,
            },
            body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                alert(data.message);
                // Redirect to queue list
                window.location.href = this.config.modulePublicPath
                    + 'queue.php?action=list&pid=' + this.config.pid;
            } else {
                alert(data.message || xl('Failed to save order.'));
            }
        })
        .catch(() => {
            alert(xl('Request failed. Please try again.'));
        })
        .finally(() => {
            if (spinner) {
                spinner.classList.add('d-none');
            }
            if (btnSave) {
                btnSave.disabled = false;
            }
        });
    },

    /**
     * Add an empty procedure row from the template.
     */
    addProcedureRow() {
        const template = document.getElementById('procRowTemplate');
        if (!template) {
            console.error('Procedure row template not found');
            return;
        }

        const clone = template.content.cloneNode(true);
        const container = document.getElementById('proceduresContainer');
        if (container) {
            container.appendChild(clone);
        }
    },

    /**
     * Open the lab compendium search popup (find_order_popup.php).
     * Reuses the same popup as the core procedure_order form.
     */
    openProcedureSearch() {
        const labId = document.getElementById('lab_id')?.value || '';
        if (!labId) {
            alert(xl('Please select a lab provider first.'));
            return;
        }

        // Add a blank row for the popup to populate
        this.addProcedureRow();
        const rows = document.querySelectorAll('#proceduresContainer .proc-row');
        this.gbl_formseq = rows.length - 1;

        const title = xl('Find Procedure Order');
        const url = this.config.webroot
            + '/interface/orders/find_order_popup.php'
            + '?labid=' + encodeURIComponent(labId)
            + '&order='
            + '&formid=0'
            + '&formseq=' + this.gbl_formseq;

        if (typeof dlgopen === 'function') {
            dlgopen(url, '_blank', 850, 500, '', title);
        } else {
            window.open(url, '_blank', 'width=850,height=500');
        }
    },

    /**
     * Attach from the detail view page.
     */
    attachFromDetail() {
        const btnAttach = document.getElementById('btnAttach');
        const encounterSelect = document.getElementById('attachEncounter');
        if (!btnAttach || !encounterSelect) {
            return;
        }

        const queueId = btnAttach.dataset.queueId;
        const pid = btnAttach.dataset.pid;
        const encounterId = encounterSelect.value;

        if (!encounterId) {
            alert(xl('Please select an encounter.'));
            return;
        }

        if (!confirm(xl('Attach this order to the selected encounter?'))) {
            return;
        }

        if (typeof top.restoreSession === 'function') {
            top.restoreSession();
        }

        fetch(this.config.modulePublicPath + 'queue.php?action=attach', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': this.config.csrfToken,
            },
            body: 'queue_id=' + encodeURIComponent(queueId)
                + '&encounter_id=' + encodeURIComponent(encounterId)
                + '&pid=' + encodeURIComponent(pid)
                + '&csrf_token_form=' + encodeURIComponent(this.config.csrfToken),
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                alert(data.message);
                window.location.href = this.config.modulePublicPath
                    + 'queue.php?action=list&pid=' + pid;
            } else {
                alert(data.message || xl('Failed to attach order.'));
            }
        })
        .catch(() => {
            alert(xl('Request failed. Please try again.'));
        });
    },
};

/**
 * Global callback for find_order_popup.php
 * The popup calls this function on the opener window when a procedure is selected.
 * Signature matches the core procedure_order form's set_proc_type().
 */
function set_proc_type(typeid, typename, diagcodes, temptype, typetitle, testid, newCnt) {
    const rows = document.querySelectorAll('#proceduresContainer .proc-row');
    const seq = LabOrderQueue.gbl_formseq;
    let targetRow = rows[seq];

    // If no row exists at the target index, add one
    if (!targetRow) {
        LabOrderQueue.addProcedureRow();
        const updatedRows = document.querySelectorAll('#proceduresContainer .proc-row');
        targetRow = updatedRows[updatedRows.length - 1];
    }

    if (targetRow) {
        const codeInput = targetRow.querySelector('.proc-code-input') || targetRow.querySelector('[name="proc_code[]"]');
        const nameInput = targetRow.querySelector('.proc-name-input') || targetRow.querySelector('[name="proc_name[]"]');
        const titleInput = targetRow.querySelector('.proc-title-input') || targetRow.querySelector('[name="proc_order_title[]"]');
        const transportInput = targetRow.querySelector('.proc-transport-input') || targetRow.querySelector('[name="proc_transport[]"]');

        if (codeInput) {
            codeInput.value = testid || '';
        }
        if (nameInput) {
            nameInput.value = typename || '';
        }
        if (titleInput) {
            titleInput.value = typetitle || 'procedure';
        }
        if (transportInput) {
            transportInput.value = temptype || '';
        }

        // If diagcodes provided from compendium config, set it
        if (diagcodes) {
            const diagInput = targetRow.querySelector('[name="proc_diagnoses[]"]');
            if (diagInput) {
                diagInput.value = diagcodes;
            }
        }
    }

    // If multiple tests selected in popup, add more rows
    if (newCnt && newCnt > 1) {
        LabOrderQueue.addProcedureRow();
        const allRows = document.querySelectorAll('#proceduresContainer .proc-row');
        LabOrderQueue.gbl_formseq = allRows.length - 1;
    }
}

/**
 * Global callback for find_order_popup.php QOE (questions).
 * We don't use QOE in the queue form, so this is a no-op stub.
 */
function set_proc_html(s, js) {
    // QOE questions are not used in queue mode.
    // They will be available when the order is opened in the encounter after attachment.
}

import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

/**
 * Forms marked with data-confirm="Message" open the shared confirmation dialog
 * before they are submitted. The server still checks permissions; this only
 * protects against accidental clicks.
 */
function setUpConfirmDialog() {
    const modalElement = document.getElementById('confirmModal');

    if (!modalElement) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const message = modalElement.querySelector('[data-confirm-message]');
    const accept = modalElement.querySelector('[data-confirm-accept]');
    let pendingForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!form.matches('form[data-confirm]') || form.dataset.confirmed === 'true') {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        pendingForm = form;
        message.textContent = form.dataset.confirm;
        accept.textContent = form.dataset.confirmButton || 'Confirm';
        modal.show();
    }, true);

    accept.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        pendingForm.dataset.confirmed = 'true';
        modal.hide();
        pendingForm.requestSubmit();
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        if (pendingForm && pendingForm.dataset.confirmed !== 'true') {
            pendingForm = null;
        }
    });
}

/**
 * Submit buttons marked data-loading show a spinner and are disabled once the
 * form is submitted, so a slow request cannot be sent twice.
 */
function setUpLoadingButtons() {
    document.addEventListener('submit', (event) => {
        const button = event.target.querySelector('button[type="submit"][data-loading]');

        if (!button || event.defaultPrevented) {
            return;
        }

        button.disabled = true;
        button.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
    });
}

/**
 * The desktop sidebar can shrink to an icon rail. The choice is kept in a
 * one-year cookie so the server renders the same state on the next page.
 */
function setUpSidebarToggle() {
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');

        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        document.cookie = `ims_sidebar=${collapsed ? 'collapsed' : 'expanded'}; path=/; max-age=31536000; SameSite=Lax`;
    });
}

/**
 * Chart marks marked data-chart-tip show their values in a floating label on
 * hover and keyboard focus. The text is plain: line breaks separate rows and
 * the first line is the heading. Every chart also has a screen-reader table.
 */
function setUpChartTooltips() {
    let tooltip = null;

    const show = (target) => {
        const [heading, ...rows] = target.dataset.chartTip.split('\n');

        tooltip ??= Object.assign(document.createElement('div'), { className: 'chart-tooltip', role: 'presentation' });
        tooltip.replaceChildren(Object.assign(document.createElement('strong'), { textContent: heading }), rows.join('\n'));
        document.body.append(tooltip);

        const box = target.getBoundingClientRect();
        const tip = tooltip.getBoundingClientRect();
        const left = Math.min(Math.max(8, box.left + box.width / 2 - tip.width / 2), window.innerWidth - tip.width - 8);
        const top = box.top - tip.height - 8 < 8 ? box.bottom + 8 : box.top - tip.height - 8;

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    const hide = () => tooltip?.remove();

    document.addEventListener('mouseover', (event) => {
        const target = event.target.closest('[data-chart-tip]');
        target ? show(target) : hide();
    });
    document.addEventListener('focusin', (event) => {
        const target = event.target.closest('[data-chart-tip]');
        target ? show(target) : hide();
    });
    document.addEventListener('focusout', hide);
    window.addEventListener('scroll', hide, { passive: true });
}

setUpConfirmDialog();
setUpLoadingButtons();
setUpSidebarToggle();
setUpChartTooltips();

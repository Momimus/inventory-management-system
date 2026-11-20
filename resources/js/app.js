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

setUpConfirmDialog();
setUpLoadingButtons();

(() => {
        const modalEl = document.getElementById('applyTemplateModal');
        const bootstrapApi = window.bootstrap ?? null;
        if (!modalEl) {
            return;
        }
        const form = document.getElementById('applyTemplateForm');
        const nameEl = document.getElementById('applyTemplateName');
        const idempotencyEl = document.getElementById('applyTemplateIdempotencyKey');
        const modal = bootstrapApi ? new bootstrapApi.Modal(modalEl) : null;
        const fallbackBackdropId = 'applyTemplateModalBackdrop';

        const fallbackOpen = () => {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
            if (!document.getElementById(fallbackBackdropId)) {
                const backdrop = document.createElement('div');
                backdrop.id = fallbackBackdropId;
                backdrop.className = 'modal-backdrop fade show';
                backdrop.addEventListener('click', fallbackClose);
                document.body.appendChild(backdrop);
            }
        };

        const fallbackClose = () => {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            const backdrop = document.getElementById(fallbackBackdropId);
            if (backdrop) {
                backdrop.remove();
            }
        };

        modalEl.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach((element) => {
            element.addEventListener('click', () => {
                if (!modal) {
                    fallbackClose();
                }
            });
        });

        document.querySelectorAll('.js-open-apply-modal').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.getAttribute('data-action') || '';
                const templateName = button.getAttribute('data-template-name') || '-';
                form.setAttribute('action', action);
                nameEl.textContent = templateName;
                if (idempotencyEl) {
                    idempotencyEl.value = `tpl-apply-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
                }
                if (modal) {
                    modal.show();
                    return;
                }
                fallbackOpen();
            });
        });
    })();

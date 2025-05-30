/* MODAL */
class Modal {
    constructor() {
        this.modalContainer = null;
        this.initContainer();
    }

    initContainer() {
        if (!this.modalContainer) {
            this.modalContainer = document.createElement('div');
            this.modalContainer.className = 'modal-container';
            document.body.appendChild(this.modalContainer);
        }
    }

    show({ icon, title, id, content, buttons, size, onShow }) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = id || 'modal-' + Date.now();

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.classList.add(size || 'modal-md');

        const headerDiv = document.createElement('div');
        headerDiv.className = 'modal-header';

        const titleEl = document.createElement('h3');
        titleEl.textContent = title;

        if (icon) {
            const iconEl = document.createElement('i');
            iconEl.className = 'fa fa-' + icon;
            titleEl.prepend(iconEl);
        }

        const closeBtn = document.createElement('button');
        closeBtn.className = 'modal-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = () => this.close(modal);

        headerDiv.appendChild(titleEl);
        headerDiv.appendChild(closeBtn);

        const bodyDiv = document.createElement('div');
        bodyDiv.className = 'modal-body';
        bodyDiv.innerHTML = content;

        const footerDiv = document.createElement('div');
        footerDiv.className = 'modal-footer';

        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.className = `btn ${btn.class || 'btn-secondary'}`;
            button.innerHTML = `<i class="fa fa-${btn.icon}"></i> ${btn.text}`;
            button.onclick = () => {
                if (btn.handler) btn.handler();
                this.close(modal);
            };
            footerDiv.appendChild(button);
        });

        modalContent.appendChild(headerDiv);
        modalContent.appendChild(bodyDiv);
        modalContent.appendChild(footerDiv);
        modal.appendChild(modalContent);

        this.modalContainer.appendChild(modal);
        setTimeout(() => {
            modal.classList.add('show');
            if (typeof onShow === 'function') {
                onShow(modal, { icon, title, id, content, buttons });
            }
        }, 10);

        return new Promise((resolve) => {
            modal.addEventListener('modal-close', () => {
                resolve(null);
            });
        });
    }

    close(modal) {
        modal.classList.remove('show');
        modal.addEventListener('transitionend', () => {
            this.modalContainer.removeChild(modal);
            modal.dispatchEvent(new Event('modal-close'));
        });
    }
}

function confirmModal(text) {
    return new Promise((resolve) => {
        modal.show({
            title: 'Confirm',
            icon: 'question-circle',
            content: "<p>" + text + "</p>",
            buttons: [
                {
                    icon: 'times',
                    text: 'Cancel',
                    class: 'btn-secondary',
                    handler: () => resolve(false)
                },
                {
                    icon: 'check',
                    text: 'OK',
                    class: 'btn-primary',
                    handler: () => resolve(true)
                }
            ]
        });
    });
}

function promptModal(text, defaultValue = '', innerText = text) {
    return new Promise((resolve) => {
        const inputId = 'prompt-input-' + Date.now();
        modal.show({
            title: text || 'Input',
            icon: 'edit',
            content: `<div class="input">
                <input type="text" id="${inputId}" value="${defaultValue}" placeholder=" " />
                <label for="${inputId}">${innerText}</label>
            </div>`,
            buttons: [
                {
                    icon: 'times',
                    text: 'Cancel',
                    class: 'btn-secondary',
                    handler: () => resolve(null)
                },
                {
                    icon: 'check',
                    text: 'OK',
                    class: 'btn-primary',
                    handler: () => {
                        const input = document.getElementById(inputId);
                        resolve(input.value);
                    }
                }
            ]
        });
    });
}

/* TOAST */
class Toast {
    constructor() {
        this.container = null;
        this.initContainer();
    }

    initContainer() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icon = document.createElement('i');
        switch (type) {
            case 'success':
                icon.className = 'fas fa-check-circle';
                break;
            case 'error':
                icon.className = 'fas fa-exclamation-circle';
                break;
            default:
                icon.className = 'fas fa-info-circle';
        }

        const messageSpan = document.createElement('span');
        messageSpan.textContent = message;

        toast.appendChild(icon);
        toast.appendChild(messageSpan);
        this.container.appendChild(toast);

        toast.addEventListener('click', () => {
            modal.show({
                title: 'Toast Details',
                icon: type === 'success' ? 'check-circle' :
                    type === 'error' ? 'exclamation-circle' : 'info-circle',
                content: `<div class="toast ${type}" style="position: static; transform: none; cursor: default;">
            <i class="${icon.className}"></i>
            <span>${message}</span>
        </div>`,
                buttons: [{
                    icon: 'check',
                    text: 'Close',
                    class: 'btn-primary'
                }]
            });
        });

        return new Promise((resolve) => {
            let timeLeft = duration;
            let timeoutId;

            const startTimer = () => {
                timeoutId = setTimeout(() => {
                    toast.classList.add('removing');
                    toast.addEventListener('animationend', () => {
                        this.container.removeChild(toast);
                        resolve();
                    });
                }, timeLeft);
            };

            toast.addEventListener('mouseenter', () => {
                clearTimeout(timeoutId);
            });

            toast.addEventListener('mouseleave', () => {
                startTimer();
            });

            startTimer();
        });
    }
}

function renderTree(container, obj, path = '') {
    for (const key in obj) {
        if (!obj.hasOwnProperty(key)) continue;
        const value = obj[key];
        const nodeId = path + key.replace(/[^a-zA-Z0-9_]/g, '_');
        if (typeof value === 'object' && value !== null) {
            const details = document.createElement('details');
            details.style.marginLeft = '20px';
            details.style.padding = '4px 0';
            const summary = document.createElement('summary');
            summary.style.cursor = 'pointer';
            summary.style.color = 'var(--text-primary)';
            summary.style.fontWeight = 'bold';
            summary.textContent = key;
            details.appendChild(summary);
            renderTree(details, value, nodeId + '_');
            container.appendChild(details);
        } else {
            const div = document.createElement('div');
            div.style.marginLeft = '20px';
            div.style.padding = '4px 0';
            div.style.color = 'var(--text-secondary)';
            const keySpan = document.createElement('span');
            keySpan.style.color = 'var(--text-primary)';
            keySpan.style.fontWeight = 'bold';
            keySpan.textContent = key + ': ';
            div.appendChild(keySpan);

            let displayValue = String(value);
            let isTruncated = false;
            if (displayValue.length > 80) {
                displayValue = displayValue.slice(0, 80) + '...';
                isTruncated = true;
            }
            const valueSpan = document.createElement('span');
            valueSpan.textContent = displayValue;
            valueSpan.style.cursor = 'pointer';
            valueSpan.title = isTruncated ? 'Click to copy full value' : 'Click to copy';
            valueSpan.onclick = function () {
                navigator.clipboard.writeText(String(value));
                toast.show('Copied to clipboard!', 'success');
            };
            div.appendChild(valueSpan);

            if (isTruncated) {
                const copyIcon = document.createElement('i');
                copyIcon.className = 'fas fa-copy';
                copyIcon.style.marginLeft = '6px';
                copyIcon.style.cursor = 'pointer';
                copyIcon.title = 'Copy full value';
                copyIcon.onclick = function (e) {
                    e.stopPropagation();
                    navigator.clipboard.writeText(String(value));
                    toast.show('Copied to clipboard!', 'success');
                };
                div.appendChild(copyIcon);
            }

            container.appendChild(div);
        }
    }
}


function lockUser() {
    document.body.style.pointerEvents = 'none';

    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'radial-gradient(circle at bottom right, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.7) 70%)';
    overlay.style.zIndex = '100';
    document.body.appendChild(overlay);
}


/* VARIABLES */
const modal = new Modal();
const toast = new Toast();
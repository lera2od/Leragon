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

    show({icon, title, id, content, buttons }) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = id || 'modal-' + Date.now();

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';

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
            button.textContent = btn.text;
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
        setTimeout(() => modal.classList.add('show'), 10);

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

const modal = new Modal();
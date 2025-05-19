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
        switch(type) {
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

        return new Promise((resolve) => {
            setTimeout(() => {
                toast.classList.add('removing');
                toast.addEventListener('animationend', () => {
                    this.container.removeChild(toast);
                    resolve();
                });
            }, duration);
        });
    }
}

const toast = new Toast();
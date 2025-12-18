/**
 * Cassiopeia - UI Animations & Interactions
 */

class CassiopeiaUI {
    constructor() {
        this.init();
    }

    init() {
        this.initLazyLoading();
        this.initSkeletonLoaders();
        this.initSmoothScroll();
        this.initCardAnimations();
        this.initMetricsUpdater();
    }

    /**
     * Lazy loading для изображений
     */
    initLazyLoading() {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    
                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Skeleton loaders для контента
     */
    initSkeletonLoaders() {
        // Автоматически скрывать скелетоны когда контент загружен
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.skeleton-card').forEach(skeleton => {
                    skeleton.style.display = 'none';
                });
            }, 1000);
        });
    }

    /**
     * Плавная прокрутка
     */
    initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Анимация появления карточек при скролле
     */
    initCardAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('fade-in');
                    }, index * 100);
                }
            });
        }, {
            threshold: 0.1
        });

        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
    }

    /**
     * Обновление метрик с анимацией
     */
    initMetricsUpdater() {
        this.animateNumbers();
    }

    /**
     * Анимация чисел (счётчик)
     */
    animateNumbers() {
        document.querySelectorAll('.metric-value').forEach(element => {
            const target = parseInt(element.dataset.value || element.textContent.replace(/[^0-9]/g, ''));
            if (isNaN(target)) return;

            const duration = 1500;
            const start = 0;
            const startTime = performance.now();

            const updateNumber = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(start + (target - start) * easeOutQuart);
                
                element.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                } else {
                    element.textContent = target.toLocaleString();
                }
            };

            requestAnimationFrame(updateNumber);
        });
    }

    /**
     * Показать loading overlay
     */
    showLoading() {
        let overlay = document.querySelector('.loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="spinner-lg"></div>';
            document.body.appendChild(overlay);
        }
        setTimeout(() => overlay.classList.add('active'), 10);
    }

    /**
     * Скрыть loading overlay
     */
    hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    /**
     * Показать toast уведомление
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        const container = document.querySelector('.toast-container') || this.createToastContainer();
        container.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    /**
     * Создать контейнер для toast
     */
    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
}

/**
 * JWST Gallery Manager
 */
class JWSTGallery {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.currentPage = 1;
        this.loading = false;
        this.filters = {
            source: 'jpg',
            instrument: '',
            program: '',
            suffix: ''
        };
    }

    async load(append = false) {
        if (this.loading) return;
        
        this.loading = true;
        if (!append) this.showSkeletons();

        try {
            const params = new URLSearchParams({
                ...this.filters,
                page: this.currentPage,
                perPage: 24
            });

            const response = await fetch(`/api/jwst/feed?${params}`);
            const data = await response.json();

            if (!append) {
                this.container.innerHTML = '';
            }

            data.items.forEach((item, index) => {
                this.addItem(item, index);
            });

        } catch (error) {
            console.error('Failed to load JWST gallery:', error);
            ui.showToast('Failed to load images', 'danger');
        } finally {
            this.loading = false;
            this.hideSkeletons();
        }
    }

    addItem(item, index) {
        const div = document.createElement('div');
        div.className = 'jwst-item stagger-item';
        div.style.animationDelay = `${index * 0.05}s`;
        div.innerHTML = `
            <img data-src="${item.url}" 
                 alt="${item.caption}" 
                 class="lazy-image"
                 loading="lazy">
            <div class="jwst-caption">
                ${item.caption}
            </div>
        `;

        div.addEventListener('click', () => this.showLightbox(item));
        this.container.appendChild(div);

        // Lazy load image
        const img = div.querySelector('img');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        observer.observe(img);
    }

    showSkeletons() {
        this.container.innerHTML = '';
        for (let i = 0; i < 12; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = 'skeleton skeleton-image';
            this.container.appendChild(skeleton);
        }
    }

    hideSkeletons() {
        this.container.querySelectorAll('.skeleton').forEach(el => el.remove());
    }

    showLightbox(item) {
        // Простой lightbox (можно улучшить)
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${item.caption}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${item.url}" class="img-fluid" alt="${item.caption}">
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    }

    setFilter(key, value) {
        this.filters[key] = value;
        this.currentPage = 1;
        this.load(false);
    }

    nextPage() {
        this.currentPage++;
        this.load(true);
    }
}

// Инициализация при загрузке страницы
let ui, jwstGallery;

document.addEventListener('DOMContentLoaded', () => {
    ui = new CassiopeiaUI();
    
    const galleryContainer = document.getElementById('jwst-gallery');
    if (galleryContainer) {
        jwstGallery = new JWSTGallery('jwst-gallery');
        jwstGallery.load();
    }
});

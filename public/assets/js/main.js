/**
 * main.js v3.0 — Progressive enhancement for TCMAS
 * NFR-16: Public verification page works with JS disabled.
 * NFR-14: Destructive actions require confirmation modals.
 * NFR-13: Client-side validation supplements server-side.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    /* ── Mobile sidebar toggle with overlay ──────────────────────────────── */
    const toggle   = document.getElementById('menu-toggle');
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
        document.body.style.overflow = '';
    }

    toggle?.addEventListener('click', () => {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay?.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(document.querySelector('.modal-backdrop.open'));
            closeSidebar();
        }
    });

    /* ── Animated stat counters ───────────────────────────────────────────── */
    const statValues = document.querySelectorAll('.stat-card__value');
    if (statValues.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.textContent.replace(/\D/g, ''), 10) || 0;
                    if (target > 0) {
                        animateCount(el, target);
                    }
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.4 });

        statValues.forEach(el => observer.observe(el));
    }

    function animateCount(el, target) {
        const duration = 700;
        const start = performance.now();
        const originalStyle = el.getAttribute('style') || '';

        requestAnimationFrame(function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            const current = Math.floor(eased * target);
            el.textContent = current.toLocaleString();
            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                el.textContent = target.toLocaleString();
                if (originalStyle) el.setAttribute('style', originalStyle);
            }
        });
    }

    /* ── Progress bars animate in ─────────────────────────────────────────── */
    const progressBars = document.querySelectorAll('.progress-bar-fill');
    if (progressBars.length && 'IntersectionObserver' in window) {
        const progObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    const target = bar.style.width;
                    bar.style.width = '0';
                    requestAnimationFrame(() => {
                        bar.style.transition = 'width 0.8s cubic-bezier(0.4,0,0.2,1)';
                        bar.style.width = target;
                    });
                    progObserver.unobserve(bar);
                }
            });
        }, { threshold: 0.3 });

        progressBars.forEach(bar => progObserver.observe(bar));
    }

    /* ── Confirm-modal wiring ─────────────────────────────────────────────── */
    document.querySelectorAll('[data-confirm-modal]').forEach(trigger => {
        trigger.addEventListener('click', e => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-confirm-modal');
            const modal   = document.querySelector(modalId);
            if (!modal) return;

            const form = modal.querySelector('form');
            if (form) {
                const src = trigger.closest('form');
                if (src) {
                    src.querySelectorAll('input[type="hidden"]').forEach(inp => {
                        let clone = form.querySelector(`[name="${inp.name}"]`);
                        if (!clone) {
                            clone = document.createElement('input');
                            clone.type  = 'hidden';
                            clone.name  = inp.name;
                            form.appendChild(clone);
                        }
                        clone.value = inp.value;
                    });
                }

                const dataId = trigger.getAttribute('data-id');
                if (dataId) {
                    let idField = form.querySelector('[name="id"]');
                    if (!idField) {
                        idField = document.createElement('input');
                        idField.type = 'hidden';
                        idField.name = 'id';
                        form.appendChild(idField);
                    }
                    idField.value = dataId;
                }
            }

            openModal(modal);
        });
    });

    // Cancel buttons inside modals
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) closeModal(modal);
        });
    });

    // Click backdrop to close
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', e => {
            if (e.target === backdrop) closeModal(backdrop);
        });
    });

    /* ── Date validation ──────────────────────────────────────────────────── */
    const startDate = document.getElementById('start_date');
    const endDate   = document.getElementById('end_date');
    if (startDate && endDate) {
        startDate.addEventListener('change', () => {
            if (endDate.value && endDate.value < startDate.value) {
                endDate.value = startDate.value;
            }
            endDate.min = startDate.value;
        });
        if (startDate.value) endDate.min = startDate.value;
    }

    /* ── Character counters ───────────────────────────────────────────────── */
    document.querySelectorAll('[data-maxlength]').forEach(input => {
        const max     = parseInt(input.getAttribute('data-maxlength'), 10);
        const counter = document.createElement('span');
        counter.className   = 'form-hint';
        input.parentNode.insertBefore(counter, input.nextSibling);

        const update = () => {
            const len = input.value.length;
            counter.textContent = `${len} / ${max} characters`;
            counter.style.color = (len > max * .9) ? '#f59e0b' : '';
        };

        input.addEventListener('input', update);
        update();
    });

    /* ── Auto-dismiss alerts after 5s ────────────────────────────────────── */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s, transform .4s';
            el.style.opacity    = '0';
            el.style.transform  = 'translateY(-6px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    /* ── Confirm inline fallback ──────────────────────────────────────────── */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* ── Active nav link highlight ────────────────────────────────────────── */
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar__nav a').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('?')[0])) {
            link.classList.add('active');
        }
    });

    /* ── Tooltip-like row highlight on tables ─────────────────────────────── */
    document.querySelectorAll('tbody tr').forEach(row => {
        row.style.cursor = 'default';
    });

});

/* ── Modal helpers ───────────────────────────────────────────────────────── */
function openModal(el) {
    if (!el) return;
    el.classList.add('open');
    el.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    const first = el.querySelector('button, input, textarea, select, a');
    if (first) first.focus();
}

function closeModal(el) {
    if (!el) return;
    el.classList.remove('open');
    el.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

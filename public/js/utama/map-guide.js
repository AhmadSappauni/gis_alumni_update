(function () {
    const GUIDE_STORAGE_KEY = 'webgis-map-guide-seen-v2';
    const GUIDE_SESSION_KEY = 'webgis-map-guide-opened-session-v2';
    const DRAG_HINT_SESSION_KEY = 'webgis-map-drag-hint-hidden-session-v1';

    function safeStorage(storage, method, key, value) {
        try {
            if (method === 'get') return storage.getItem(key);
            if (method === 'set') storage.setItem(key, value);
        } catch (_) {
            return null;
        }

        return null;
    }

    function markGuideSeen() {
        safeStorage(window.localStorage, 'set', GUIDE_STORAGE_KEY, '1');
    }

    function markGuideOpenedThisSession() {
        safeStorage(window.sessionStorage, 'set', GUIDE_SESSION_KEY, '1');
    }

    function hasSeenGuide() {
        return safeStorage(window.localStorage, 'get', GUIDE_STORAGE_KEY) === '1';
    }

    function hasOpenedThisSession() {
        return safeStorage(window.sessionStorage, 'get', GUIDE_SESSION_KEY) === '1';
    }

    function setupGuideDialog() {
        const overlay = document.getElementById('map-guide-overlay');
        const dialog = document.getElementById('map-guide-dialog');
        const openBtn = document.getElementById('open-map-guide');
        const closeBtn = document.getElementById('map-guide-close');
        const startBtn = document.getElementById('map-guide-start');
        const hideBtn = document.getElementById('map-guide-hide');
        const moreBtn = document.getElementById('map-guide-more');
        const fullGuide = document.getElementById('map-guide-full');
        const searchHint = document.getElementById('map-search-hint');

        if (!overlay || !dialog) {
            return;
        }

        let lastFocusedEl = null;
        let searchHintTimer = null;

        const setFullGuideOpen = function (open) {
            if (!moreBtn || !fullGuide) {
                return;
            }

            const isOpen = !!open;
            fullGuide.hidden = !isOpen;
            dialog.classList.toggle('is-expanded', isOpen);
            moreBtn.setAttribute('aria-expanded', isOpen.toString());
            moreBtn.textContent = isOpen ? 'Ringkas' : 'Panduan lengkap';
        };

        const showSearchHint = function () {
            if (!searchHint) {
                return;
            }

            if (searchHintTimer) {
                clearTimeout(searchHintTimer);
            }

            searchHint.hidden = false;
            requestAnimationFrame(function () {
                searchHint.classList.add('is-visible');
            });

            searchHintTimer = setTimeout(function () {
                searchHint.classList.remove('is-visible');

                searchHintTimer = setTimeout(function () {
                    searchHint.hidden = true;
                }, 240);
            }, 4200);
        };

        const closeGuide = function (options) {
            const shouldRemember = !!(options && options.remember);
            const shouldHint = !!(options && options.hint);

            overlay.hidden = true;
            document.body.classList.remove('map-guide-open');

            if (shouldRemember) {
                markGuideSeen();
            }

            if (lastFocusedEl && typeof lastFocusedEl.focus === 'function') {
                lastFocusedEl.focus({ preventScroll: true });
            }

            if (shouldHint) {
                showSearchHint();
            }
        };

        const openGuide = function (options) {
            const layerMenu = document.getElementById('layer-control-menu');
            if (layerMenu) {
                layerMenu.classList.add('hidden');
            }

            lastFocusedEl = document.activeElement;
            setFullGuideOpen(false);
            overlay.hidden = false;
            document.body.classList.add('map-guide-open');

            if (options && options.auto) {
                markGuideOpenedThisSession();
            }

            window.setTimeout(function () {
                (startBtn || closeBtn || dialog).focus?.({ preventScroll: true });
            }, 30);
        };

        window.openMapGuide = openGuide;

        openBtn?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openGuide({ auto: false });
        });

        closeBtn?.addEventListener('click', function () {
            closeGuide({ remember: false });
        });

        startBtn?.addEventListener('click', function () {
            closeGuide({ remember: true, hint: true });
        });

        hideBtn?.addEventListener('click', function () {
            closeGuide({ remember: true });
        });

        moreBtn?.addEventListener('click', function () {
            const isOpen = moreBtn.getAttribute('aria-expanded') === 'true';
            setFullGuideOpen(!isOpen);
        });

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeGuide({ remember: false });
            }
        });

        document.addEventListener('keydown', function (event) {
            if (overlay.hidden) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeGuide({ remember: false });
            }
        });

        if (!hasSeenGuide() && !hasOpenedThisSession()) {
            window.setTimeout(function () {
                openGuide({ auto: true });
            }, 700);
        }
    }

    function setupMapDragHint() {
        const hint = document.getElementById('map-drag-hint');

        if (!hint || safeStorage(window.sessionStorage, 'get', DRAG_HINT_SESSION_KEY) === '1') {
            return;
        }

        let showTimer = null;
        let hideTimer = null;

        const showHint = function () {
            hint.hidden = false;
            requestAnimationFrame(function () {
                hint.classList.add('is-visible');
            });
        };

        const hideHint = function () {
            safeStorage(window.sessionStorage, 'set', DRAG_HINT_SESSION_KEY, '1');

            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }

            if (hideTimer) {
                clearTimeout(hideTimer);
            }

            hint.classList.remove('is-visible');
            hideTimer = setTimeout(function () {
                hint.hidden = true;
            }, 240);

            if (window.map && typeof window.map.off === 'function') {
                window.map.off('dragstart movestart zoomstart', hideHint);
            }
        };

        if (window.map && typeof window.map.on === 'function') {
            window.map.on('dragstart movestart zoomstart', hideHint);
        }

        showTimer = window.setTimeout(showHint, 900);
    }

    function setupMapTooltips() {
        let tooltip = document.querySelector('.map-ui-tooltip');

        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'map-ui-tooltip';
            tooltip.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltip);
        }

        let activeTarget = null;

        function normalizeTooltipElement(el) {
            if (!el) {
                return;
            }

            const nativeTitle = el.getAttribute('title');
            if (nativeTitle) {
                if (!el.getAttribute('data-map-tooltip')) {
                    el.setAttribute('data-map-tooltip', nativeTitle);
                }

                if (!el.getAttribute('aria-label')) {
                    el.setAttribute('aria-label', nativeTitle);
                }

                el.removeAttribute('title');
            }

            if (el.dataset.mapTooltipReady === '1') {
                return;
            }

            el.dataset.mapTooltipReady = '1';
        }

        function normalizeAllTooltips() {
            document.querySelectorAll('[data-map-tooltip], .leaflet-control-zoom-in, .leaflet-control-zoom-out')
                .forEach(function (el) {
                    if (el.matches('.leaflet-control-zoom-in')) {
                        el.setAttribute('data-map-tooltip', 'Perbesar peta');
                        el.setAttribute('aria-label', 'Perbesar peta');
                    }

                    if (el.matches('.leaflet-control-zoom-out')) {
                        el.setAttribute('data-map-tooltip', 'Perkecil peta');
                        el.setAttribute('aria-label', 'Perkecil peta');
                    }

                    normalizeTooltipElement(el);
                });
        }

        function hideTooltip() {
            activeTarget = null;
            tooltip.classList.remove('is-visible');
        }

        function showTooltip(target) {
            normalizeTooltipElement(target);

            const text = (target.getAttribute('data-map-tooltip') || '').trim();
            if (!text) {
                hideTooltip();
                return;
            }

            activeTarget = target;
            tooltip.textContent = text;
            positionTooltip(target);
            tooltip.classList.add('is-visible');
        }

        function positionTooltip(target) {
            if (!target || !tooltip) {
                return;
            }

            const rect = target.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const showBelow = rect.top < tooltipRect.height + 18;
            const top = showBelow
                ? Math.min(window.innerHeight - tooltipRect.height - 10, rect.bottom + 10)
                : Math.max(10, rect.top - tooltipRect.height - 10);
            const left = Math.min(
                window.innerWidth - tooltipRect.width / 2 - 8,
                Math.max(tooltipRect.width / 2 + 8, rect.left + rect.width / 2)
            );

            tooltip.classList.toggle('is-below', showBelow);
            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';
        }

        document.addEventListener('mouseover', function (event) {
            const target = event.target?.closest?.('[data-map-tooltip]');
            if (!target || target === activeTarget) {
                return;
            }

            showTooltip(target);
        });

        document.addEventListener('focusin', function (event) {
            const target = event.target?.closest?.('[data-map-tooltip]');
            if (target) {
                showTooltip(target);
            }
        });

        document.addEventListener('mouseout', function (event) {
            if (!activeTarget) {
                return;
            }

            if (activeTarget.contains(event.relatedTarget)) {
                return;
            }

            hideTooltip();
        });

        document.addEventListener('focusout', function (event) {
            if (event.target === activeTarget || activeTarget?.contains?.(event.target)) {
                hideTooltip();
            }
        });

        window.addEventListener('scroll', hideTooltip, true);
        window.addEventListener('resize', function () {
            if (activeTarget) {
                positionTooltip(activeTarget);
            }
        });

        normalizeAllTooltips();
        window.setTimeout(normalizeAllTooltips, 250);
        window.setTimeout(normalizeAllTooltips, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupGuideDialog();
        setupMapDragHint();
        setupMapTooltips();
    });
})();

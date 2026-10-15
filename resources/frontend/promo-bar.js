/*
 * Promo Bar. No dependencies and no build step.
 *
 * The bars arrive in the page's HTML, the same for every visitor so pages
 * can be statically cached. This settles what depends on the visitor:
 * bars they've dismissed or that aren't for them, countdowns, copying
 * codes, rotating, their basket's free delivery progress, and sending
 * events to the site's analytics.
 *
 * The part between the bootstrap markers is also printed inline straight
 * after each set of bars, so they're right before the first paint.
 */
(function () {
    'use strict';

    /* bootstrap:start */
    var promoBarDismissedKey = 'promo-bar:dismissed';

    function promoBarDismissed() {
        try {
            var stored = JSON.parse(window.localStorage.getItem(promoBarDismissedKey) || '{}');
            return stored && typeof stored === 'object' ? stored : {};
        } catch (e) {
            return {};
        }
    }

    // The signed-in cookie: absent for guests, "1" or "1:group,group" when signed in.
    function promoBarAudience(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[^\w-]/g, '') + '=([^;]*)'));
        if (!match) return null;
        var value = decodeURIComponent(match[1]);
        if (value.charAt(0) !== '1') return null;
        return { groups: value.indexOf(':') > -1 ? value.slice(value.indexOf(':') + 1).split(',') : [] };
    }

    function promoBarEligible(bar, now, dismissed, audience) {
        var ends = Date.parse(bar.getAttribute('data-ends') || '');
        if (ends && ends <= now) return false;
        var until = dismissed[bar.getAttribute('data-promo-bar-key')];
        if (until !== undefined && (until === 0 || until > now)) return false;
        var need = bar.getAttribute('data-audience');
        if (need === 'guests' && audience) return false;
        if (need === 'signed_in') {
            if (!audience) return false;
            var groups = (bar.getAttribute('data-groups') || '').split(',').filter(Boolean);
            if (groups.length && !groups.some(function (group) { return audience.groups.indexOf(group) > -1; })) return false;
        }
        return true;
    }

    // Show the bars this visitor should see, as the group's mode says.
    function promoBarEvaluate(group) {
        var now = Date.now();
        var dismissed = promoBarDismissed();
        var audience = promoBarAudience(group.getAttribute('data-audience-cookie') || 'promo_bar_audience');
        var mode = group.getAttribute('data-mode');
        var bars = [].slice.call(group.querySelectorAll('[data-promo-bar]'));
        var eligible = bars.filter(function (bar) { return promoBarEligible(bar, now, dismissed, audience); });
        if (mode === 'first') eligible = eligible.slice(0, 1);
        var active = group.querySelector('[data-promo-bar]:not(.is-inactive):not([hidden])');
        if (mode === 'rotate' && eligible.indexOf(active) < 0) active = eligible[0];

        bars.forEach(function (bar) {
            var shown = eligible.indexOf(bar) > -1;
            bar.hidden = !shown;
            if (mode === 'rotate') {
                var inactive = bar !== active;
                bar.classList.toggle('is-inactive', inactive);
                bar.toggleAttribute('inert', inactive);
                if (inactive) bar.setAttribute('aria-hidden', 'true'); else bar.removeAttribute('aria-hidden');
                bar.classList.toggle('has-controls', eligible.length > 1);
            }
            [].slice.call(bar.querySelectorAll('[data-promo-bar-js]')).forEach(function (element) {
                var kind = element.getAttribute('data-promo-bar-js');
                if (kind === 'controls') element.hidden = eligible.length < 2;
                else if (kind === 'copy') element.hidden = !((navigator.clipboard && window.isSecureContext) || document.queryCommandSupported && document.queryCommandSupported('copy'));
                else element.hidden = false;
            });
            [].slice.call(bar.querySelectorAll('[data-promo-bar-fallback]')).forEach(function (element) {
                element.classList.add('promo-bar__visually-hidden');
            });
        });

        group.hidden = eligible.length === 0;
        group.setAttribute('data-promo-bar-ready', '');
        return eligible;
    }
    /* bootstrap:end */

    var config = readConfig();
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var groups = [];

    function readConfig() {
        var element = document.getElementById('promo-bar-config');
        var defaults = { endpoint: '/!/promo-bar', labels: { units: { d: 'd', h: 'h', m: 'm', s: 's' } }, analytics: { providers: [], track: [], events: {} } };
        if (!element) return defaults;
        try {
            return Object.assign(defaults, JSON.parse(element.textContent));
        } catch (e) {
            return defaults;
        }
    }

    function all(root, selector) {
        return Array.prototype.slice.call(root.querySelectorAll(selector));
    }

    function emit(name, bar, extra) {
        var detail = Object.assign(describe(bar), extra || {});
        document.dispatchEvent(new CustomEvent('promo-bar:' + name, { detail: detail }));
        track(name, detail);
    }

    function describe(bar) {
        var cta = bar.querySelector('[data-promo-bar-cta]');
        return {
            id: bar.getAttribute('data-promo-bar-id'),
            name: bar.getAttribute('data-promo-bar-name'),
            url: cta ? cta.href : null,
            site: config.site || null,
            page: window.location.pathname,
            element: bar,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */

    function track(action, detail) {
        var analytics = config.analytics || {};
        if ((analytics.track || []).indexOf(action) < 0) return;

        var name = (analytics.events || {})[action] || 'promo_bar_' + action;
        var props = { id: detail.id, name: detail.name, action: action, url: detail.url, site: detail.site, page: detail.page };

        if (analytics.debug && window.console) console.info('[Promo Bar]', name, props);

        (analytics.providers || []).forEach(function (provider) {
            try {
                send(provider, name, props, analytics);
            } catch (e) {
                if (analytics.debug && window.console) console.warn('[Promo Bar] ' + provider + ':', e);
            }
        });
    }

    function send(provider, name, props, analytics) {
        var prefixed = { promo_bar_id: props.id, promo_bar_name: props.name, promo_bar_action: props.action, promo_bar_url: props.url };

        switch (provider) {
            case 'gtm':
                (window.dataLayer = window.dataLayer || []).push(Object.assign({ event: name }, prefixed));
                break;
            case 'ga4':
                if (typeof window.gtag === 'function') window.gtag('event', name, prefixed);
                break;
            case 'plausible':
                if (typeof window.plausible === 'function') window.plausible(name, { props: { bar: props.name, action: props.action, url: props.url || '' } });
                break;
            case 'fathom':
                if (window.fathom && typeof window.fathom.trackEvent === 'function') window.fathom.trackEvent(name + ': ' + props.name);
                break;
            case 'umami':
                if (window.umami && typeof window.umami.track === 'function') window.umami.track(name, { bar: props.name, action: props.action, url: props.url });
                break;
            case 'matomo':
                (window._paq = window._paq || []).push(['trackEvent', 'Promo Bar', name, props.name]);
                break;
            case 'custom':
                custom(name, props, analytics.custom || {});
                break;
        }
    }

    function custom(name, props, settings) {
        var renamed = {};
        Object.keys(props).forEach(function (key) {
            renamed[(settings.properties || {})[key] || key] = props[key];
        });

        if (settings.function) {
            var parts = settings.function.split('.');
            var owner = window;
            for (var i = 0; i < parts.length - 1; i++) {
                owner = owner ? owner[parts[i]] : undefined;
            }
            var fn = owner ? owner[parts[parts.length - 1]] : undefined;

            if (typeof fn === 'function') {
                if (settings.arguments === 'values') fn.call(owner, name, props.name, props.url);
                else if (settings.arguments === 'array') fn.call(owner, ['trackEvent', 'Promo Bar', name, props.name]);
                else fn.call(owner, name, renamed);
            }
        }

        if (settings.endpoint && navigator.sendBeacon) {
            navigator.sendBeacon(settings.endpoint, new Blob([JSON.stringify(Object.assign({ event: name }, renamed))], { type: 'application/json' }));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Views, dismissing, copying
    |--------------------------------------------------------------------------
    */

    function viewed(bar) {
        if (bar.hidden || bar.classList.contains('is-inactive')) return;
        var key = 'promo-bar:viewed';
        var seen = {};
        try { seen = JSON.parse(window.sessionStorage.getItem(key) || '{}') || {}; } catch (e) { /* private mode */ }
        var id = bar.getAttribute('data-promo-bar-key');
        if (seen[id]) return;
        seen[id] = 1;
        try { window.sessionStorage.setItem(key, JSON.stringify(seen)); } catch (e) { /* private mode */ }
        emit('view', bar);
    }

    function dismiss(group, bar) {
        var days = parseInt(bar.getAttribute('data-dismiss-days') || '', 10);
        var stored = promoBarDismissed();
        var now = Date.now();

        // Drop what's run out, so the list doesn't grow for ever.
        Object.keys(stored).forEach(function (key) {
            if (stored[key] !== 0 && stored[key] <= now) delete stored[key];
        });

        stored[bar.getAttribute('data-promo-bar-key')] = days > 0 ? now + days * 864e5 : 0;
        try { window.localStorage.setItem(promoBarDismissedKey, JSON.stringify(stored)); } catch (e) { /* private mode */ }

        emit('dismiss', bar);
        var shown = refresh(group);

        // Keep the keyboard's place: on the next bar if one took its spot.
        var next = shown[0] && shown[0].querySelector('a, button:not([hidden])');
        if (next) next.focus();
    }

    // Where the clipboard API is refused, select the code and copy that.
    function copySelection(code) {
        var range = document.createRange();
        range.selectNodeContents(code);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        var copied = false;
        try { copied = document.execCommand('copy'); } catch (e) { /* leave it selected */ }
        return copied ? Promise.resolve() : Promise.reject();
    }

    function copy(bar, button) {
        var code = bar.querySelector('[data-promo-bar-code]');
        if (!code) return;

        var text = code.textContent.trim();
        var write = navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject();

        write.catch(function () { return copySelection(code); }).then(function () {
            var status = bar.querySelector('[data-promo-bar-status]');
            button.textContent = button.getAttribute('data-copied-label');
            if (status) status.textContent = button.getAttribute('data-copied-label');
            clearTimeout(button._promoBarTimer);
            button._promoBarTimer = setTimeout(function () {
                button.textContent = button.getAttribute('data-copy-label');
                if (status) status.textContent = '';
            }, 2000);
            emit('copy', bar);
        }, function () { /* The code is left selected to copy by hand. */ });
    }

    /*
    |--------------------------------------------------------------------------
    | Countdowns
    |--------------------------------------------------------------------------
    */

    function pad(number) {
        return number < 10 ? '0' + number : String(number);
    }

    function remaining(ms) {
        var units = (config.labels || {}).units || { d: 'd', h: 'h', m: 'm', s: 's' };
        var total = Math.max(0, Math.floor(ms / 1000));
        var days = Math.floor(total / 86400);
        var hours = Math.floor((total % 86400) / 3600);
        var minutes = Math.floor((total % 3600) / 60);
        var seconds = total % 60;
        var parts = [];

        if (days) parts.push(days + units.d);
        parts.push(pad(hours) + units.h, pad(minutes) + units.m);

        // Seconds tick every second; with reduced motion, minutes are enough.
        if (!reduceMotion) parts.push(pad(seconds) + units.s);

        return parts.join(' ');
    }

    function tick() {
        var now = Date.now();

        groups.forEach(function (group) {
            var ended = false;

            all(group, '[data-promo-bar-countdown]').forEach(function (countdown) {
                var ends = Date.parse(countdown.getAttribute('data-ends'));
                var output = countdown.querySelector('[data-promo-bar-remaining]');
                if (output) output.textContent = remaining(ends - now);
            });

            all(group, '[data-promo-bar][data-ends]').forEach(function (bar) {
                if (!bar.hidden && Date.parse(bar.getAttribute('data-ends')) <= now) ended = true;
            });

            if (ended) refresh(group);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Rotating
    |--------------------------------------------------------------------------
    */

    function rotation(group) {
        var state = { paused: reduceMotion, hovered: false, timer: null };
        var interval = Math.max(2, parseInt(group.getAttribute('data-interval') || '6', 10)) * 1000;

        function shown() {
            return all(group, '[data-promo-bar]').filter(function (bar) { return !bar.hidden; });
        }

        function go(step) {
            var bars = shown();
            if (bars.length < 2) return;
            var current = bars.findIndex(function (bar) { return !bar.classList.contains('is-inactive'); });
            var next = bars[(current + step + bars.length) % bars.length];
            var hadFocus = group.contains(document.activeElement) && document.activeElement.closest('[data-promo-bar-previous], [data-promo-bar-next], [data-promo-bar-pause]');

            bars.forEach(function (bar) {
                var inactive = bar !== next;
                bar.classList.toggle('is-inactive', inactive);
                bar.toggleAttribute('inert', inactive);
                if (inactive) bar.setAttribute('aria-hidden', 'true'); else bar.removeAttribute('aria-hidden');
            });

            // Move focus to the same control on the bar now showing.
            if (hadFocus) {
                var same = next.querySelector('[' + ['data-promo-bar-previous', 'data-promo-bar-next', 'data-promo-bar-pause'].filter(function (attribute) { return hadFocus.hasAttribute(attribute); })[0] + ']');
                if (same) same.focus();
            }

            viewed(next);
        }

        function schedule() {
            clearInterval(state.timer);
            if (!state.paused && !state.hovered) state.timer = setInterval(function () { go(1); }, interval);
        }

        function showPaused() {
            all(group, '[data-promo-bar-pause]').forEach(function (button) {
                button.setAttribute('aria-label', button.getAttribute(state.paused ? 'data-play-label' : 'data-pause-label'));
                var pause = button.querySelector('[data-promo-bar-icon="pause"]');
                var play = button.querySelector('[data-promo-bar-icon="play"]');
                if (pause) pause.toggleAttribute('hidden', state.paused);
                if (play) play.toggleAttribute('hidden', !state.paused);
            });
        }

        group.addEventListener('click', function (event) {
            if (event.target.closest('[data-promo-bar-previous]')) { go(-1); schedule(); }
            if (event.target.closest('[data-promo-bar-next]')) { go(1); schedule(); }
            if (event.target.closest('[data-promo-bar-pause]')) { state.paused = !state.paused; showPaused(); schedule(); }
        });

        // Hold still while someone's reading or using it.
        group.addEventListener('mouseenter', function () { state.hovered = true; schedule(); });
        group.addEventListener('mouseleave', function () { state.hovered = false; schedule(); });
        group.addEventListener('focusin', function () { state.hovered = true; schedule(); });
        group.addEventListener('focusout', function (event) { if (!group.contains(event.relatedTarget)) { state.hovered = false; schedule(); } });

        showPaused();
        schedule();
    }

    /*
    |--------------------------------------------------------------------------
    | Free delivery (Cargo)
    |--------------------------------------------------------------------------
    */

    function loadFreeDelivery() {
        var elements = [];
        groups.forEach(function (group) {
            elements = elements.concat(all(group, '[data-promo-bar-free-delivery][data-fetch]'));
        });

        var ids = elements.map(function (element) { return element.closest('[data-promo-bar]').getAttribute('data-promo-bar-id'); });
        if (!ids.length || !window.fetch) return Promise.resolve();

        return fetch(config.endpoint + '/state?bars=' + encodeURIComponent(ids.join(',')), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        })
            .then(function (response) { return response.ok ? response.json() : { bars: {} }; })
            .then(function (data) {
                elements.forEach(function (element) {
                    var bar = element.closest('[data-promo-bar]');
                    var state = (data.bars || {})[bar.getAttribute('data-promo-bar-id')];
                    var message = bar.querySelector('[data-promo-bar-message]');

                    if (!state) {
                        element.hidden = true;
                        if (message) message.hidden = false;
                        return;
                    }

                    var text = state.unlocked
                        ? element.getAttribute('data-unlocked-text')
                        : element.getAttribute('data-progress-text').split(':remaining').join(state.remaining_formatted);

                    element.querySelector('[data-promo-bar-progress-text]').textContent = text;
                    var meter = element.querySelector('progress');
                    if (meter) meter.value = state.percent;
                    element.hidden = false;
                    if (message) message.hidden = true;
                });
            })
            .catch(function () { /* Leave the message showing. */ });
    }

    /*
    |--------------------------------------------------------------------------
    | Setting up
    |--------------------------------------------------------------------------
    */

    function refresh(group) {
        var shown = promoBarEvaluate(group);
        shown.forEach(viewed);
        return shown;
    }

    function setUp(group) {
        if (group._promoBar) return;
        group._promoBar = true;
        groups.push(group);

        // Plausible's own click tagging covers clicks before this runs; from
        // here this script sends them, so they aren't counted twice.
        all(group, '[class*="plausible-event-"]').forEach(function (element) {
            element.className = element.className.split(/\s+/).filter(function (name) { return name.indexOf('plausible-event-') !== 0; }).join(' ');
        });

        group.addEventListener('click', function (event) {
            var bar = event.target.closest('[data-promo-bar]');
            if (!bar) return;

            if (event.target.closest('[data-promo-bar-dismiss]')) dismiss(group, bar);
            else if (event.target.closest('[data-promo-bar-copy]')) copy(bar, event.target.closest('[data-promo-bar-copy]'));
            else if (event.target.closest('[data-promo-bar-cta]')) emit('click', bar);
        });

        // Middle and ⌘-clicks open the link too.
        group.addEventListener('auxclick', function (event) {
            var bar = event.target.closest('[data-promo-bar]');
            if (bar && event.button === 1 && event.target.closest('[data-promo-bar-cta]')) emit('click', bar);
        });

        if (group.getAttribute('data-mode') === 'rotate') rotation(group);

        refresh(group);
    }

    function start() {
        all(document, '[data-promo-bar-group]').forEach(setUp);
        if (!groups.length) return;

        tick();
        if (document.querySelector('[data-promo-bar-countdown], [data-promo-bar][data-ends]')) {
            setInterval(tick, reduceMotion ? 30000 : 1000);
        }

        loadFreeDelivery();
    }

    window.PromoBar = {
        /** Settle every bar again, and fetch free delivery progress (after the basket changes). */
        refresh: function () {
            groups.forEach(refresh);
            return loadFreeDelivery();
        },
        /** Show every dismissed bar again. */
        reset: function () {
            try { window.localStorage.removeItem(promoBarDismissedKey); } catch (e) { /* private mode */ }
            groups.forEach(refresh);
        },
        /** For bars added to the page later. */
        start: start,
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();

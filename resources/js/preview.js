/*
 * The Control Panel preview's pure parts: a bar's markup from the form's
 * values, its custom properties, and colour contrast. Kept apart from the
 * component so they can be tested with node --test.
 */

export const SIZES = { small: '0.8125rem', normal: '0.9375rem', large: '1.0625rem' };
export const PADDING = { compact: '0.375rem', normal: '0.625rem', roomy: '1rem' };
export const COLOURS = ['background', 'background_2', 'text', 'accent', 'accent_text'];

export function escape(text) {
    return String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

export function validColour(colour) {
    return typeof colour === 'string' && /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(colour.trim()) ? colour.trim().toLowerCase() : null;
}

/**
 * Bard's value (ProseMirror JSON) as HTML: text with bold, italic and links.
 */
export function bardToHtml(value) {
    if (typeof value === 'string') {
        try {
            value = JSON.parse(value);
        } catch (e) {
            return escape(value);
        }
    }

    const nodes = Array.isArray(value) ? value : value?.content ?? [];

    const render = (node) => {
        if (!node) return '';
        if (node.type === 'text') {
            let html = escape(node.text);
            for (const mark of node.marks ?? []) {
                if (mark.type === 'bold') html = `<strong>${html}</strong>`;
                if (mark.type === 'italic') html = `<em>${html}</em>`;
                if (mark.type === 'link') html = `<a href="#">${html}</a>`;
            }
            return html;
        }
        if (node.type === 'hardBreak') return '<br>';
        const inner = (node.content ?? []).map(render).join('');
        return node.type === 'paragraph' ? inner + ' ' : inner;
    };

    return nodes.map(render).join('').trim();
}

/**
 * A preset's values, checked, over its defaults.
 */
export function clean(values, defaults = {}) {
    const clean = {};
    values = values && typeof values === 'object' ? values : {};

    for (const key of COLOURS) {
        const colour = validColour(values[key]) ?? validColour(defaults[key]);
        if (colour) clean[key] = colour;
    }

    clean.button_style = ['filled', 'outline', 'link'].includes(values.button_style) ? values.button_style : defaults.button_style ?? 'filled';
    clean.size = values.size in SIZES ? values.size : defaults.size ?? 'normal';
    clean.padding = values.padding in PADDING ? values.padding : defaults.padding ?? 'normal';
    clean.align = ['start', 'center'].includes(values.align) ? values.align : defaults.align ?? 'center';
    const radius = Number.parseInt(values.radius, 10);
    clean.radius = Number.isFinite(radius) ? Math.max(0, Math.min(999, radius)) : Number(defaults.radius ?? 0);

    return clean;
}

export function properties(values) {
    const map = { background: '--promo-bar-background', background_2: '--promo-bar-background-2', text: '--promo-bar-text', accent: '--promo-bar-accent', accent_text: '--promo-bar-accent-text' };
    const css = [];

    for (const [key, property] of Object.entries(map)) {
        if (validColour(values[key])) css.push(`${property}:${values[key]}`);
    }
    if (values.size) css.push(`--promo-bar-font-size:${SIZES[values.size]}`);
    if (values.padding) css.push(`--promo-bar-padding:${PADDING[values.padding]}`);
    if (values.align) {
        css.push(`--promo-bar-align:${values.align === 'start' ? 'flex-start' : 'center'}`);
        css.push(`--promo-bar-text-align:${values.align === 'start' ? 'start' : 'center'}`);
    }
    if (values.radius !== undefined) css.push(`--promo-bar-radius:${Number(values.radius)}px`);

    return css.join(';');
}

export function luminance(colour) {
    let hex = (validColour(colour) ?? '#000000').slice(1);
    if (hex.length === 3) hex = hex.split('').map((c) => c + c).join('');
    const [r, g, b] = [0, 2, 4].map((i) => {
        const c = parseInt(hex.slice(i, i + 2), 16) / 255;
        return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a, b) {
    const la = luminance(a);
    const lb = luminance(b);
    return Math.round(((Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05)) * 100) / 100;
}

/**
 * Pairs of colours below WCAG AA (4.5:1) for a preset's values.
 *
 * @returns {Array<{ pair: string, ratio: number }>}
 */
export function contrastWarnings(values, style) {
    const warnings = [];
    const check = (pair, a, b) => {
        if (!a || !b) return;
        const ratio = contrast(a, b);
        if (ratio < 4.5) warnings.push({ pair, ratio });
    };

    check('text', values.text, values.background);
    if (style === 'gradient') check('text_end', values.text, values.background_2);

    if (values.button_style === 'filled') check('button', values.accent_text, values.accent);
    if (values.button_style === 'outline') check('button', values.accent, values.background);

    return warnings;
}

/**
 * A sample bar's markup, as the site draws it.
 */
/**
 * Time left until an end date, as the site shows it (without seconds).
 */
export function remaining(ends, units = {}, now = Date.now(), short = false) {
    const end = typeof ends === 'string' || typeof ends === 'number' ? Date.parse(ends) : NaN;
    if (Number.isNaN(end)) return null;
    const total = Math.max(0, Math.floor((end - now) / 60000));
    const days = Math.floor(total / 1440);
    const pad = (n) => String(n).padStart(2, '0');
    const parts = [];
    if (short) {
        return days
            ? `${days}${units.d ?? 'd'} ${pad(Math.floor((total % 1440) / 60))}${units.h ?? 'h'}`
            : `${pad(Math.floor((total % 1440) / 60))}${units.h ?? 'h'} ${pad(total % 60)}${units.m ?? 'm'}`;
    }
    if (days) parts.push(days + (units.d ?? 'd'));
    parts.push(pad(Math.floor((total % 1440) / 60)) + (units.h ?? 'h'), pad(total % 60) + (units.m ?? 'm'));
    return parts.join(' ');
}

export function barHtml({ style, values, message, cta, code, countdown, ends, progress, dismissible, words, phone = false }) {
    const themed = style !== 'none';
    const classes = ['promo-bar__bar', `promo-bar--${style}`];
    if (themed) classes.push('promo-bar--themed', `promo-bar--button-${values.button_style}`);
    if (dismissible) classes.push('promo-bar--dismissible');

    let inner = `<div class="promo-bar__message">${message || '&nbsp;'}</div>`;
    if (countdown) inner += `<p class="promo-bar__countdown">${escape(countdown)} <span data-promo-bar-remaining>${escape(remaining(ends, words.units, Date.now(), phone) ?? `2${words.units?.d ?? 'd'} 04${words.units?.h ?? 'h'} 12${words.units?.m ?? 'm'}`)}</span></p>`;
    if (progress) inner += `<p class="promo-bar__progress"><span>${escape(progress)}</span><progress class="promo-bar__meter" max="100" value="60"></progress></p>`;
    if (code) inner += `<p class="promo-bar__code"><span>${escape(words.code)}</span> <code>${escape(code)}</code> <button type="button" class="promo-bar__copy">${escape(words.copy)}</button></p>`;
    if (cta) inner += `<a class="promo-bar__cta" href="#">${escape(cta)}</a>`;

    const dismiss = dismissible
        ? `<button type="button" class="promo-bar__dismiss" aria-label="${escape(words.dismiss)}"><svg aria-hidden="true" viewBox="0 0 16 16"><path d="M3.7 2.3 8 6.6l4.3-4.3 1.4 1.4L9.4 8l4.3 4.3-1.4 1.4L8 9.4l-4.3 4.3-1.4-1.4L6.6 8 2.3 3.7z"/></svg></button>`
        : '';

    return `<div class="${classes.join(' ')}" style="${escape(themed ? properties(values) : '')}"><div class="promo-bar__inner">${inner}</div>${dismiss}</div>`;
}

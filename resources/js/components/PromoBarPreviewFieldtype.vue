<!--
    A live preview: of the bar being edited (mode "bar"), or of every preset
    on the settings page (mode "settings"). Drawn in a shadow root with the
    site's own stylesheet, so it looks as it will on the site.
-->
<script setup>
import { Fieldtype } from '@statamic/cms';
import { Description, injectPublishContext } from '@statamic/cms/ui';
import { computed, onMounted, ref, watch } from 'vue';
import { barHtml, bardToHtml, clean, contrastWarnings, escape, validColour, COLOURS } from '../preview.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const { values } = injectPublishContext();
const host = ref(null);
let root = null;

const meta = computed(() => props.meta ?? {});
const words = computed(() => meta.value.words ?? {});

const pairs = {
    text: __('Text on the background'),
    text_end: __('Text on the second background'),
    button: __('The button'),
};

function presetValues(handle) {
    const saved = values.value?.[`style_${handle}`];
    return saved ? clean(saved, meta.value.defaults?.[handle] ?? {}) : meta.value.presets?.[handle] ?? {};
}

const samples = computed(() => {
    const v = values.value ?? {};

    if (meta.value.mode === 'settings') {
        return Object.keys(meta.value.defaults ?? {}).map((handle) => {
            const preset = presetValues(handle);
            const label = meta.value.labels?.[handle] ?? handle;
            return {
                key: handle,
                label,
                warnings: contrastWarnings(preset, handle),
                html: barHtml({
                    style: handle,
                    values: preset,
                    message: `<strong>${escape(label)}</strong> ${escape(__('Free delivery on everything this weekend.'))}`,
                    cta: __('Shop now'),
                    dismissible: true,
                    words: words.value,
                }),
            };
        });
    }

    let style = v.style || meta.value.default_style || 'solid';
    if (!(style in (meta.value.labels ?? {}))) style = 'solid';

    let preset = style === 'none' ? {} : { ...(meta.value.presets?.[style] ?? {}) };

    if (v.custom_colours && style !== 'none') {
        for (const key of COLOURS) {
            const colour = validColour(v[`colour_${key}`]);
            if (colour) preset[key] = colour;
        }
    }

    const code = v.code_source === 'text' ? v.code : v.code_source === 'cargo' ? 'CODE' : null;
    const progress = v.free_delivery ? String(v.free_delivery_progress || words.value.progress || '').split(':remaining').join('£12.00') : null;

    return [{
        key: 'bar',
        label: null,
        warnings: style === 'none' ? [] : contrastWarnings(preset, style),
        html: barHtml({
            style,
            values: preset,
            message: bardToHtml(v.message) || escape(__('Your message')),
            cta: v.cta_label,
            code,
            countdown: v.countdown ? v.countdown_label || words.value.countdown : null,
            progress,
            dismissible: v.dismissible !== false,
            words: words.value,
        }),
    }];
});

function draw() {
    if (!host.value) return;
    root ??= host.value.attachShadow({ mode: 'open' });

    const items = samples.value
        .map((sample) => `${sample.label ? `<p class="label">${escape(sample.label)}</p>` : ''}<div class="promo-bar">${sample.html}</div>`)
        .join('');

    // The page's own font, as on the site the bar inherits it.
    root.innerHTML = `<style>${meta.value.css ?? ''}
        :host { display: block; font-family: system-ui, sans-serif; }
        .label { margin: 1rem 0 0.375rem; font-size: 0.8125rem; font-weight: 600; color: #4b5563; }
        .label:first-child { margin-top: 0; }
        .promo-bar__bar { border-radius: 0.375rem; }
        a, button { pointer-events: none; }
    </style>${items}`;
}

onMounted(draw);
watch(samples, draw, { deep: true });
</script>

<template>
    <div>
        <div ref="host" />
        <div v-for="sample in samples.filter((s) => s.warnings.length)" :key="sample.key" class="mt-2">
            <Description class="text-amber-700! dark:text-amber-400!">
                <span v-if="sample.label">{{ sample.label }}: </span>
                <span v-for="(warning, index) in sample.warnings" :key="warning.pair">
                    {{ index ? '; ' : '' }}{{ __(':pair is hard to read (contrast :ratio:1, aim for 4.5:1)', { pair: pairs[warning.pair], ratio: warning.ratio }) }}
                </span>
            </Description>
        </div>
    </div>
</template>

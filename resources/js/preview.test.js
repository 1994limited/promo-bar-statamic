import { test } from 'node:test';
import assert from 'node:assert/strict';
import { barHtml, bardToHtml, clean, contrast, contrastWarnings, escape, properties } from './preview.js';

test('escapes HTML', () => {
    assert.equal(escape('<b>"Hi" & \'bye\'</b>'), '&lt;b&gt;&quot;Hi&quot; &amp; &#39;bye&#39;&lt;/b&gt;');
});

test('renders Bard text with marks, escaped', () => {
    const value = [{ type: 'text', text: 'Free ' }, { type: 'text', text: 'delivery', marks: [{ type: 'bold' }] }, { type: 'text', text: ' <script>' }];
    assert.equal(bardToHtml(value), 'Free <strong>delivery</strong> &lt;script&gt;');
});

test('renders Bard paragraphs and breaks', () => {
    const value = { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'One' }, { type: 'hardBreak' }, { type: 'text', text: 'Two' }] }] };
    assert.equal(bardToHtml(value), 'One<br>Two');
});

test('cleans values, keeping only valid colours', () => {
    const values = clean({ background: 'red;}body{', text: '#FFF', radius: '2000', size: 'huge' }, { background: '#111111', size: 'normal', radius: 4 });
    assert.equal(values.background, '#111111');
    assert.equal(values.text, '#fff');
    assert.equal(values.radius, 999);
    assert.equal(values.size, 'normal');
});

test('writes custom properties', () => {
    const css = properties(clean({ background: '#000000', text: '#ffffff', align: 'start', radius: 0, size: 'small', padding: 'roomy' }));
    assert.match(css, /--promo-bar-background:#000000/);
    assert.match(css, /--promo-bar-align:flex-start/);
    assert.match(css, /--promo-bar-radius:0px/);
    assert.match(css, /--promo-bar-font-size:0\.8125rem/);
});

test('works out contrast as WCAG does', () => {
    assert.equal(contrast('#000000', '#ffffff'), 21);
    assert.equal(contrast('#777777', '#ffffff'), 4.48);
});

test('warns about low contrast', () => {
    const warnings = contrastWarnings({ background: '#ffff00', text: '#ffffff', accent: '#1d4ed8', accent_text: '#ffffff', button_style: 'filled' }, 'solid');
    assert.deepEqual(warnings.map((w) => w.pair), ['text']);
});

test('draws a sample bar', () => {
    const html = barHtml({ style: 'solid', values: clean({}, { background: '#1d4ed8', text: '#ffffff', button_style: 'filled' }), message: 'Hi', cta: 'Shop <now>', code: 'SAVE10', dismissible: true, words: { code: 'Use code', copy: 'Copy', dismiss: 'Dismiss' } });
    assert.match(html, /promo-bar--themed/);
    assert.match(html, /Shop &lt;now&gt;/);
    assert.match(html, /<code>SAVE10<\/code>/);
    assert.match(html, /promo-bar__dismiss/);
});

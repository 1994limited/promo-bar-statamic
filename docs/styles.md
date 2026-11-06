# Styles

Every bar has a style: one of six presets, or **None**, for sites that style bars themselves.

| Preset | Look |
| --- | --- |
| Solid | A strong background, light text, a filled button. |
| Subtle | A pale tint, dark text, the button as a link. |
| Dark | Near-black, light text, a bright button. |
| Outline | Your page's background with a coloured line beneath, and an outlined button. |
| Gradient | A two-colour gradient, for sales and launches. |
| Minimal | Small, quiet text for low-key notices. |

## Editing the presets

![The Styles settings: every preset previewed](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/cp-styles.png)

**Tools → Addons → Promo Bar → Settings → Styles** shows every preset as it will look, and changes as you edit. For each preset:

- **Colours**: background (two for Gradient), text, button, and button text.
- **Button**: filled, outline, or a plain link.
- **Button corners**, in pixels (999 for a pill).
- **Text size**: small, normal or large.
- **Height**: compact, normal or roomy.
- **Align**: left or centred.

When text or a button is hard to read against its background (below the 4.5:1 contrast WCAG asks for), the preview says so. It doesn't stop you saving.

Which preset bars use unless they choose one is under **General → Default style**, along with **Stick to the top on scroll**.

## A bar's own colours

![A bar's Style tab: its preset, and Own colours](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/cp-bar-style.png)

A bar can keep its preset but change its colours: turn on **Own colours** on the bar's **Style** tab. The bar's preview shows the result and any contrast warning.

## None: your own CSS

With **None**, bars get only the rules they need to work (hiding, rotating in place) and no look of their own. Style them from your own stylesheet with these classes:

| Class | |
| --- | --- |
| `.promo-bar` | The wrapper around all the bars (a `<section>`), plus `.promo-bar--first`, `.promo-bar--stack` or `.promo-bar--rotate`. |
| `.promo-bar__bar` | One bar, plus `.promo-bar--{preset}` and `.promo-bar--dismissible`. Themed bars also have `.promo-bar--themed` and `.promo-bar--button-{filled|outline|link}`. |
| `.promo-bar__inner` | The bar's content. |
| `.promo-bar__message` | The message. |
| `.promo-bar__countdown` | The countdown, with `[data-promo-bar-remaining]` inside. |
| `.promo-bar__code` | The code (`<code>`) and its `.promo-bar__copy` button. |
| `.promo-bar__progress` | Free delivery progress, with a `.promo-bar__meter` `<progress>`. |
| `.promo-bar__cta` | The button. |
| `.promo-bar__dismiss` | The close button. |
| `.promo-bar__controls` | Rotating's previous, pause and next buttons (`.promo-bar__control`). |

## Phones

The phone rules are in their own media query, for the phone width in the settings. They add these classes to style against:

| Class | |
| --- | --- |
| `.promo-bar__message--wide`, `.promo-bar__message--phone` | When a bar has a phone message: the message for bigger screens and for phones. |
| `.promo-bar--phone-hidden` | On a bar not shown on phones. |
| `.promo-bar--phone-no-countdown`, `-no-code`, `-no-button` | On a bar leaving those out on phones. |
| `.promo-bar--phone-first` | On the wrapper of stacked bars showing only the first on phones. |

## Changing a preset from your CSS

The presets are written as CSS custom properties, so your stylesheet can override any of them, for every bar or one preset:

```css
.promo-bar--solid {
    --promo-bar-background: #0f5c63;
    --promo-bar-radius: 999px;
}

.promo-bar__bar {
    font-family: var(--font-display);
}
```

| Property | |
| --- | --- |
| `--promo-bar-background` | Background (the gradient's start). |
| `--promo-bar-background-2` | The gradient's end. |
| `--promo-bar-text` | Text. |
| `--promo-bar-accent` | The button (and Outline's line). |
| `--promo-bar-accent-text` | The button's text. |
| `--promo-bar-font-size` | Text size. |
| `--promo-bar-padding` | Space above and below. |
| `--promo-bar-align`, `--promo-bar-text-align` | Alignment. |
| `--promo-bar-radius` | Button corners. |

Bars inherit your page's font.

The presets' values are set with `:where()`, which has no specificity, so a rule like the one above wins wherever the stylesheets load. The bars' own layout rules are ordinary classes, so they aren't upset by your site's styles for plain `button` or `a` elements.

# Accessibility

Promo Bar's markup and script are built to WCAG 2.2 AA. The presets' colours pass, and the settings warn when edited colours don't (see [Styles](styles.md)).

## What's there

- The bars are a `<section>` landmark named "Announcements" (change it with the settings' wording or `label=""`), so screen reader users can find or skip them.
- The bar is in the page's HTML from the start and the space it takes doesn't change as the script runs, so nothing jumps (no layout shift).
- Buttons are real `<button>`s with names: "Dismiss: Autumn sale", "Copy", "Previous", "Pause", "Next". A link opening a new tab says so.
- Closing a bar moves keyboard focus to the bar that takes its place, if there is one.
- "Copied" is announced to screen readers.
- Countdowns: the changing numbers are hidden from screen readers (they'd be read every second); the end date is read instead. Visitors who prefer reduced motion see hours and minutes, updated every half minute, not ticking seconds.
- Rotating bars have previous, pause and next buttons, pause while the pointer or focus is on them, and never move on their own for visitors who prefer reduced motion. Bars not showing are `inert`, so they can't be tabbed to or read.

## What to check in your design

- Keep messages short: a line, or two on phones.
- Use Dismissible for anything that isn't essential, and a modest rotating interval (6 seconds or more).
- If you use **None** and your own CSS, keep a visible focus style on the buttons and enough contrast.
- If your header is `position: sticky` too, check the two don't overlap (or turn off **Stick to the top on scroll**).

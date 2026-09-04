---
name: API Bench
description: A conventional, quiet HTTP request client — the familiar tool, executed carefully.
colors:
  surface: "#ffffff"
  canvas: "#f6f7f8"
  sunken: "#fafbfc"
  border: "#e3e5e8"
  border-strong: "#cdd0d5"
  text: "#1c1e21"
  text-muted: "#5f6672"
  text-faint: "#6f7681"
  accent: "#ff6c37"
  accent-hover: "#e85a26"
  focus: "#3d7eff"
  get: "#0f7b3f"
  post: "#b8860b"
  put: "#2563c9"
  patch: "#7c3aed"
  delete: "#c62828"
  success: "#0f7b3f"
  warning: "#b8860b"
  danger: "#c62828"
typography:
  title:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.3
  body:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 500
    lineHeight: 1.4
  data:
    fontFamily: "JetBrains Mono, ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"
    fontSize: "0.8125rem"
    lineHeight: 1.6
rounded:
  sm: "4px"
  md: "6px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.surface}"
    rounded: "{rounded.sm}"
    padding: "8px 16px"
  button-primary-hover:
    backgroundColor: "{colors.accent-hover}"
    textColor: "{colors.surface}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.sm}"
    padding: "8px 16px"
  field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.sm}"
    padding: "8px 10px"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "12px"
---

# Design System: API Bench

## Overview

**Creative North Star: "The Familiar Tool"**

This is a conventional HTTP request client and it is meant to look like one. The user arrives mid-debug, already knows what a method picker, a params table and a response pane are, and wants to spend zero attention learning a metaphor. Every screen therefore uses the vocabulary of the category — Send, Params, Body, Headers, Response, Status, Time, Size — and the arrangement developers already have muscle memory for: method and URL on one line with Send at the end, request tabs beneath, response panel below with its own Body/Headers tabs.

The craft lives in restraint rather than invention: a quiet neutral surface, one accent colour, hairline borders, tight consistent spacing, and monospace reserved strictly for things that are literally code or data. Nothing is decorative. The interface should read as unremarkable and get out of the way of the JSON.

**Key Characteristics:**
- Plain product language; no invented metaphors or themed nouns
- Neutral greys with a single warm accent for the primary action
- Colour carries meaning only: HTTP methods and response status
- Monospace for URLs, JSON, headers and measurements; Inter for everything the interface says
- Flat surfaces, 1px borders, 4–6px radii, no shadows

## Colors

A neutral working surface with one accent, plus a small semantic set for methods and status.

### Primary
- **Action Orange** (#ff6c37): the Send button and the active tab underline. The only saturated colour in the chrome, so the primary action is never ambiguous.

### Secondary
- **Focus Blue** (#3d7eff): focus rings on fields and buttons only. Never decorative.

### Tertiary
HTTP method colours, used as text on the method label and picker: **GET** green (#0f7b3f), **POST** amber (#b8860b), **PUT** blue (#2563c9), **PATCH** violet (#7c3aed), **DELETE** red (#c62828). Response status reuses the same semantics: 2xx green, 3xx amber, 4xx/5xx red.

### Neutral
- **Text** (#1c1e21): all primary copy, values, and code.
- **Muted Text** (#5f6672): labels, hints, secondary metadata.
- **Faint Text** (#6f7681): placeholders and counts. Still ≥4.5:1 on white.
- **Canvas** (#f6f7f8): the page behind the panels.
- **Surface** (#ffffff): panels, fields, editors.
- **Sunken** (#fafbfc): editor gutters and the tab strip inside a bordered group.
- **Border** (#e3e5e8) / **Strong Border** (#cdd0d5): panel edges and dividers / input outlines.

### Named Rules
**The Meaningful Colour Rule.** Colour is reserved for the primary action, focus, HTTP methods and status. If a colour is not saying one of those four things, it should be grey.

**The Contrast Floor Rule.** Every text token, placeholders included, clears 4.5:1 against the surface it sits on.

## Typography

**Display / Body Font:** Inter (with system stack fallback)
**Label/Mono Font:** JetBrains Mono (with ui-monospace fallback)

**Character:** Deliberately ordinary. Inter is the workhorse UI face this category runs on; JetBrains Mono is chosen for character-level legibility in payloads, where distinguishing `l`/`1`/`I` and `0`/`O` decides how long a debugging session takes.

### Hierarchy
- **Title** (600, 1.125rem): page headings — project name, endpoint name, Quick Test.
- **Body** (400, 0.875rem/1.5): descriptions, empty-state copy, button labels.
- **Label** (500, 0.75rem): field labels, column headers, tab counts, metadata.
- **Data** (mono 400, 0.8125rem/1.6): URLs, JSON, header keys and values, timings, sizes.

### Named Rules
**The Data-Is-Mono Rule.** Anything the user sends or the server returned is set in JetBrains Mono. Anything the interface says about it is set in Inter. Monospace is never used for emphasis or flavour.

## Layout

A single centred column, max 1180px, with 16px page gutters (24px from `sm`). Content is a vertical stack of bordered panels: request first, response second, with 16px between them.

Inside a panel, sections are separated by 1px borders rather than gaps: the URL row, the tab strip, the active tab's body, and (on Quick Test) the clear-actions row each sit in their own bordered band. Key/value rows use a `minmax(0,1fr) minmax(0,1.5fr) 28px` grid so keys, values and the remove button align down the column.

Responsive: below `sm` the method picker, URL field and Send button stack to full width; tab strips wrap rather than truncate; key/value grids keep both columns. The page never scrolls horizontally — long payload lines scroll inside the editor instead.

## Elevation & Depth

Flat. There are no shadows anywhere; separation comes from 1px borders and the small tonal step between canvas, surface and sunken. The only raised-looking element is the focus ring, which is a 3px translucent blue halo plus a border colour change.

### Named Rules
**The No Shadow Rule.** Depth is expressed with borders and tonal steps, never `box-shadow` — except the focus ring, which is a state, not decoration.

## Shapes

Modest radii: 6px on panels, 4px on fields, buttons and editor frames. Borders are always 1px. The active tab is marked by a 2px accent underline, never a filled pill or a background change.

## Components

### Buttons
- **Shape:** 4px radius, 8px/16px padding, 0.8125rem medium.
- **Primary:** solid Action Orange with white text; one per view (Send, Create project, Save endpoint).
- **Secondary:** white with a strong border and dark text; hover fills with canvas grey.
- **Danger:** white with a strong border and red text; hover fills red with white text. Destructive actions confirm first.
- **Link:** borderless muted text for low-weight actions (Add row, Clear URL/body/all).
- **Focus:** 2px blue outline, 1px offset.

### Method label and picker
The method reads as coloured monospace text, not a filled badge: `.method` in list rows and headings, and `.method-select` for the picker, which colours its own text and caret by the current value. Same colour vocabulary in both places so a project list scans by colour alone.

### Panels
White, 6px radius, 1px border, no shadow. Internal bands are divided by 1px borders. Panels are never nested inside panels.

### Inputs / Fields
White ground, 1px strong border, 4px radius, 0.8125rem. Mono variant (`.field-mono`) for URLs and key/value rows. Focus turns the border blue and adds a 3px translucent blue ring. Errors turn the border red and put the message directly beneath in red.

### Tabs
Muted 0.8125rem medium labels with a transparent 2px bottom border; the active one takes the accent colour and underline. Used for the nav, the request tabs and the response tabs — one pattern in three places. Response tabs carry a count in faint text where a count is meaningful.

### Key/value tables
Read-only header lists use `.kv-table`: muted 0.75rem column headings on a 1px underline, mono 0.75rem values, 1px row dividers, values wrapping on long tokens.

### Code editors (CodeMirror)
White ground, sunken gutter with a 1px right border, JetBrains Mono at 12.5–13px, line-height 1.6 set on the editor root. The request body editor is 260px tall with JSON linting and bracket matching; the response viewer is 420px, read-only, no lint. **Line wrapping is off in both** so one source line is always one numbered row.

## Do's and Don'ts

### Do:
- **Do** use the category's own words: Send, Params, Body, Headers, Response, Status, Time, Size.
- **Do** keep exactly one primary (orange) button per view.
- **Do** set anything sent or returned in mono, and anything the UI says in Inter.
- **Do** state what a control actually does — "Sent as query string parameters", not a restatement of its label.
- **Do** give every state a real treatment: empty, loading, error, and no-body responses all have copy of their own.

### Don't:
- **Don't** introduce themed vocabulary or metaphors for standard HTTP concepts.
- **Don't** add `box-shadow`, gradients, or radii above 6px.
- **Don't** spend accent orange, green, amber or red on anything that is not the primary action, a method, or a status.
- **Don't** turn on line wrapping in the editors; the gutter must stay one row per line.
- **Don't** animate state changes beyond simple colour transitions on hover and focus.

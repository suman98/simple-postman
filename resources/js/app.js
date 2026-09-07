import Alpine from 'alpinejs';
import { EditorView, keymap, lineNumbers, highlightActiveLine } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { json, jsonParseLinter } from '@codemirror/lang-json';
import { linter, lintGutter } from '@codemirror/lint';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching } from '@codemirror/language';

/**
 * Mounts a small JSON-aware CodeMirror editor into `container` and keeps
 * `onChange` in sync with its content. Used for every editable JSON field:
 * the request body, and (in "JSON view") the headers editor.
 */
function createJsonEditor(container, initialDoc, onChange) {
    const view = new EditorView({
        doc: initialDoc || '',
        extensions: [
            lineNumbers(),
            highlightActiveLine(),
            bracketMatching(),
            history(),
            keymap.of([...defaultKeymap, ...historyKeymap]),
            json(),
            syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
            linter(jsonParseLinter()),
            lintGutter(),
            EditorView.updateListener.of((update) => {
                if (update.docChanged) onChange(update.state.doc.toString());
            }),
            EditorView.theme({
                '&': { fontSize: '13px', height: '260px', backgroundColor: 'var(--color-field)', lineHeight: '1.6' },
                '.cm-content': { color: 'var(--color-ink)', caretColor: 'var(--color-ink)' },
                '.cm-scroller': { overflow: 'auto', fontFamily: 'var(--font-mono)' },
                // Mobile Chrome boosts text in the wide content block but not in
                // the narrow gutter, which walks the line numbers out of step.
                '&, .cm-content, .cm-gutters, .cm-gutterElement': {
                    '-webkit-text-size-adjust': '100%',
                    'text-size-adjust': '100%',
                },
            }),
        ],
        parent: container,
    });

    keepMeasured(view);

    return view;
}

/**
 * CodeMirror caches a measured height per line. Mounting one inside a panel
 * that is still hidden bakes in metrics from the fallback font, and a later
 * requestMeasure() won't invalidate that cache — the gutter then runs out of
 * step with wrapped content. So every editor here is mounted on first reveal,
 * and this only guards against the web font landing a moment afterwards.
 */
function keepMeasured(view) {
    if (document.fonts?.ready) {
        document.fonts.ready.then(() => view.requestMeasure());
    }
}

/**
 * Mounts an editor only once its container is really laid out AND the mono face
 * has loaded. CodeMirror writes an inline height onto every gutter element from
 * whatever it measured at construction, and a later requestMeasure() will not
 * revisit it — so measuring against a zero-width box or fallback font metrics
 * leaves the line numbers permanently out of step with the code beside them.
 */
function mountWhenReady(getEl, factory) {
    const layoutReady = (attemptsLeft = 30) => {
        const el = getEl();
        if (el && el.offsetWidth > 0) {
            factory(el);
            return;
        }
        if (attemptsLeft <= 0) return;
        requestAnimationFrame(() => layoutReady(attemptsLeft - 1));
    };

    if (document.fonts?.ready) {
        document.fonts.ready.then(() => layoutReady());
    } else {
        layoutReady();
    }
}

/**
 * True when the gutter's row height matches the content's. CodeMirror bakes an
 * inline height per gutter row at construction; if it measured mid-paint those
 * heights are wrong for good, and the line numbers slide away from their lines.
 */
function gutterMatchesContent(container) {
    const gutterRow = container?.querySelector('.cm-lineNumbers .cm-gutterElement:nth-child(2)');
    const contentRow = container?.querySelector('.cm-content .cm-line');
    if (!gutterRow || !contentRow) return true;

    const drift = Math.abs(
        gutterRow.getBoundingClientRect().height - contentRow.getBoundingClientRect().height
    );
    return drift <= 1;
}

/**
 * Builds an editor into `container` and, on the next frame, checks that its
 * gutter actually lines up. If it doesn't, it rebuilds once against the now
 * settled layout. `onView` receives whichever view is current.
 */
function mountVerified(container, factory, onView, attemptsLeft = 6) {
    onView(factory());

    if (attemptsLeft <= 0) return;

    // One frame is not always enough: the panel is still being revealed in the
    // same paint, so layout can move under the first measurement. Re-check, and
    // rebuild against the settled layout if the gutter came out wrong.
    setTimeout(() => {
        if (gutterMatchesContent(container)) return;
        onView(null, { disposing: true });
        mountVerified(container, factory, onView, attemptsLeft - 1);
    }, 80);
}

/** Replaces a CodeMirror view's content wholesale. */
function setEditorContent(view, text) {
    if (!view) return;
    view.dispatch({
        changes: { from: 0, to: view.state.doc.length, insert: text || '' },
    });
}

function formatJsonEditor(view, currentText) {
    try {
        const pretty = JSON.stringify(JSON.parse(currentText || '{}'), null, 2);
        setEditorContent(view, pretty);
        return { ok: true, value: pretty };
    } catch (e) {
        return { ok: false, error: 'Invalid JSON: ' + e.message };
    }
}

/**
 * Parses a JSON object's text into [{key,value}] rows; null if the text
 * isn't valid JSON or isn't a flat object (arrays/primitives have no sensible
 * key/value shape). Non-string values are re-stringified so a row's value is
 * always plain text. Used to carry a payload across the JSON<->form-data
 * toggle, and across the headers rows<->JSON view toggle.
 */
function jsonTextToRows(text) {
    try {
        const parsed = JSON.parse(text || '{}');
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            const rows = Object.entries(parsed).map(([key, value]) => ({
                key,
                value: typeof value === 'string' ? value : JSON.stringify(value),
            }));
            return rows.length ? rows : [{ key: '', value: '' }];
        }
    } catch (e) {
        // not valid JSON, or not a flat object - nothing sensible to carry over
    }
    return null;
}

/** Serializes [{key,value}] rows into pretty-printed JSON object text. */
function rowsToJsonText(rows) {
    const obj = {};
    for (const row of rows) {
        if (row.key) obj[row.key] = row.value;
    }
    return JSON.stringify(obj, null, 2);
}

/**
 * Percent-decoding that never throws on half-typed input (a lone "%" while
 * the user is still typing is not valid encoding) and treats "+" as a space,
 * the way a query string means it.
 */
function decodeQueryPart(text) {
    try {
        return decodeURIComponent(text.replace(/\+/g, ' '));
    } catch (e) {
        return text;
    }
}

/** Encodes a query key/value but leaves {{variable}} braces readable. */
function encodeQueryPart(text) {
    return encodeURIComponent(text ?? '')
        .replace(/%7B/g, '{')
        .replace(/%7D/g, '}');
}

/**
 * Splits a URL's query string into [{key,value}] rows. Empty pairs are
 * dropped, a key with no "=" yields an empty value, and the fragment is
 * ignored — it isn't part of the query.
 */
function urlQueryToRows(url) {
    const queryStart = (url || '').indexOf('?');
    if (queryStart === -1) return [];
    const query = url.slice(queryStart + 1).split('#')[0];
    return query
        .split('&')
        .filter((pair) => pair !== '')
        .map((pair) => {
            const eq = pair.indexOf('=');
            return eq === -1
                ? { key: decodeQueryPart(pair), value: '' }
                : { key: decodeQueryPart(pair.slice(0, eq)), value: decodeQueryPart(pair.slice(eq + 1)) };
        });
}

/** Rewrites a URL's query string from [{key,value}] rows, keeping path and fragment. */
function rowsToUrlQuery(url, rows) {
    const text = url || '';
    const hashStart = text.indexOf('#');
    const hash = hashStart === -1 ? '' : text.slice(hashStart);
    const withoutHash = hashStart === -1 ? text : text.slice(0, hashStart);
    const base = withoutHash.split('?')[0];
    const query = rows
        .filter((row) => row.key)
        .map((row) => `${encodeQueryPart(row.key)}=${encodeQueryPart(row.value)}`)
        .join('&');
    return base + (query ? `?${query}` : '') + hash;
}

/** True when two row lists carry the same keys and values in the same order. */
function sameRows(a, b) {
    if (a.length !== b.length) return false;
    return a.every((row, index) => row.key === b[index].key && row.value === b[index].value);
}

/**
 * Resolves {{variable}} placeholders against a project's or Quick Test's
 * environment, exactly like Postman: unresolved names are left as-is (a typo
 * shouldn't silently become an empty string), and a disabled or empty-key row
 * doesn't participate.
 */
function resolveVariables(text, variables) {
    if (!text) return text;
    return text.replace(/\{\{\s*([\w.-]+)\s*\}\}/g, (match, name) => {
        const row = variables.find((v) => v.key === name);
        return row ? row.value : match;
    });
}

/**
 * Read-only, syntax-highlighted viewer for the response panel. Content is
 * replaced wholesale (via setEditorContent) each time a new response comes
 * back, rather than recreated, so scroll position resets cleanly.
 */
function createJsonViewer(container, initialDoc) {
    const view = new EditorView({
        doc: initialDoc || '',
        extensions: [
            lineNumbers(),
            json(),
            syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
            EditorState.readOnly.of(true),
            EditorView.editable.of(false),
            EditorView.theme({
                // line-height lives on the root, not .cm-content: CodeMirror's
                // height measurement runs outside the content node and would
                // otherwise size the gutter off the default leading.
                '&': { fontSize: '12.5px', height: '420px', backgroundColor: 'var(--color-field)', lineHeight: '1.6' },
                '.cm-content': { color: 'var(--color-ink)', caretColor: 'transparent' },
                '.cm-scroller': { overflow: 'auto', fontFamily: 'var(--font-mono)' },
                // Mobile Chrome boosts text in the wide content block but not in
                // the narrow gutter, which walks the line numbers out of step.
                '&, .cm-content, .cm-gutters, .cm-gutterElement': {
                    '-webkit-text-size-adjust': '100%',
                    'text-size-adjust': '100%',
                },
                '.cm-activeLineGutter, .cm-activeLine': { backgroundColor: 'transparent' },
            }),
            // Deliberately unwrapped: one source line is one numbered row, so the
            // gutter can never drift from the content. Long values scroll sideways.
        ],
        parent: container,
    });

    keepMeasured(view);

    return view;
}

async function copyToClipboard(text) {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            // fall through to legacy path
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch (e) {
        ok = false;
    }
    document.body.removeChild(textarea);
    return ok;
}

/**
 * Shared behaviour for both Alpine components below: the JSON<->form-data body
 * toggle and the headers rows<->JSON view toggle. Both editors, once mounted,
 * are never destroyed — their containers stay in the DOM (toggled with
 * x-show, not x-if) and just get shown/hidden, so there's no remount dance
 * and no stale-reference risk. Mixed into each component via
 * withEditableJsonBehaviour() below, which preserves its getters as live
 * accessors so `this` still resolves to the actual component instance.
 */
const editableJsonBehaviour = {
    /** Body tab: mounts the request-body editor the first time it's visible. */
    mountJsonEditor() {
        if (this.jsonEditorView) return;
        mountWhenReady(() => this.$refs.jsonEditor, (el) => {
            if (this.jsonEditorView) return;
            this.jsonEditorView = createJsonEditor(el, this.body, (value) => {
                this.body = value;
            });
        });
    },

    formatJson() {
        const result = formatJsonEditor(this.jsonEditorView, this.body);
        if (result.ok) {
            this.body = result.value;
            this.jsonFormatError = null;
        } else {
            this.jsonFormatError = result.error;
        }
    },

    showBodyTab() {
        this.activeTab = 'body';
        if (this.bodyType === 'json' && !this.jsonEditorView) {
            this.$nextTick(() => this.mountJsonEditor());
        }
    },

    /** Carries the payload across the toggle so switching never loses data. */
    syncBodyType(bodyType) {
        if (bodyType === 'json') {
            this.body = rowsToJsonText(this.formRows);
            if (this.jsonEditorView) {
                setEditorContent(this.jsonEditorView, this.body);
            } else if (this.activeTab === 'body') {
                this.$nextTick(() => this.mountJsonEditor());
            }
            return;
        }
        const rows = jsonTextToRows(this.body);
        if (rows) this.formRows = rows;
    },

    /** Headers tab: mounts the headers-as-JSON editor the first time it's visible. */
    mountHeadersEditor() {
        if (this.headersEditorView) return;
        mountWhenReady(() => this.$refs.headersEditor, (el) => {
            if (this.headersEditorView) return;
            this.headersEditorView = createJsonEditor(el, this.headersJson, (value) => {
                this.headersJson = value;
            });
        });
    },

    formatHeadersJson() {
        const result = formatJsonEditor(this.headersEditorView, this.headersJson);
        if (result.ok) {
            this.headersJson = result.value;
            this.headersJsonError = null;
        } else {
            this.headersJsonError = result.error;
        }
    },

    showHeadersTab() {
        this.activeTab = 'headers';
        if (this.headersMode === 'json' && !this.headersEditorView) {
            this.$nextTick(() => this.mountHeadersEditor());
        }
    },

    syncHeadersMode(mode) {
        if (mode === 'json') {
            this.headersJson = rowsToJsonText(this.headerRows);
            if (this.headersEditorView) {
                setEditorContent(this.headersEditorView, this.headersJson);
            } else if (this.activeTab === 'headers') {
                this.$nextTick(() => this.mountHeadersEditor());
            }
            return;
        }
        const rows = jsonTextToRows(this.headersJson);
        if (rows) this.headerRows = rows;
        this.persistState?.();
    },

    /**
     * Forces headerRows to reflect the JSON view's latest text even if the
     * user never toggled back to Rows — used right before a native form
     * submit (see endpointForm), since the headers view mode is otherwise
     * only synced back to rows lazily, on toggle.
     */
    ensureHeaderRowsSynced() {
        if (this.headersMode !== 'json') return;
        const rows = jsonTextToRows(this.headersJson);
        if (rows) this.headerRows = rows;
    },

    /**
     * Query string <-> Params rows, kept in step both ways like Postman:
     * typing "?query=123" onto the URL fills the rows, and editing a row
     * rewrites the URL. GET only — that's the only method whose params
     * travel in the URL. `urlDriven` marks the URL as the side that just
     * changed, so a half-typed "a=1&" isn't tidied away under the cursor.
     */
    watchUrlParams() {
        this.$watch('url', () => this.syncParamsFromUrl());
        this.$watch('paramRows', () => this.syncUrlFromParams());

        // A saved GET whose URL already carries a query: the URL wins, and
        // any stored param it doesn't mention is appended to it.
        if (!this.isGet) return;
        const urlRows = urlQueryToRows(this.url);
        if (!urlRows.length) return;
        const inUrl = new Set(urlRows.map((row) => row.key));
        const extras = this.paramRows.filter((row) => row.key && !inUrl.has(row.key));
        this.paramRows = [...urlRows, ...extras];
    },

    syncParamsFromUrl() {
        if (!this.isGet) return;
        const rows = urlQueryToRows(this.url);
        const filled = this.paramRows.filter((row) => row.key || row.value);
        if (sameRows(rows, filled)) return;

        this.urlDriven = true;
        this.paramRows = rows.length ? rows : [{ key: '', value: '' }];
        this.$nextTick(() => {
            this.urlDriven = false;
        });
    },

    syncUrlFromParams() {
        if (!this.isGet || this.urlDriven) return;
        const url = rowsToUrlQuery(this.url, this.paramRows);
        if (url !== this.url) this.url = url;
    },

    addParam() {
        this.paramRows.push({ key: '', value: '' });
    },
    removeParam(index) {
        this.paramRows.splice(index, 1);
        if (this.paramRows.length === 0) this.addParam();
        this.persistState?.();
    },
    addFormRow() {
        this.formRows.push({ key: '', value: '' });
    },
    removeFormRow(index) {
        this.formRows.splice(index, 1);
        if (this.formRows.length === 0) this.addFormRow();
        this.persistState?.();
    },
    addHeader() {
        this.headerRows.push({ key: '', value: '' });
    },
    removeHeader(index) {
        this.headerRows.splice(index, 1);
        if (this.headerRows.length === 0) this.addHeader();
        this.persistState?.();
    },

    get isGet() {
        return this.method === 'GET';
    },

    get methodClass() {
        return 'method-' + this.method.toLowerCase();
    },

    get paramsLabel() {
        return this.bodyType === 'form' ? 'Form data' : 'Params';
    },
};

/**
 * Merges editableJsonBehaviour's getters (isGet, methodClass, paramsLabel)
 * onto a component as live accessors. Object.assign would instead invoke each
 * getter once immediately, against the wrong `this`, and freeze the result —
 * silently breaking every x-show that depends on them.
 */
function withEditableJsonBehaviour(component) {
    return Object.defineProperties(component, Object.getOwnPropertyDescriptors(editableJsonBehaviour));
}

/**
 * Endpoint create/edit form: params + headers row repeaters that post
 * as normal named array fields (params[i][key], headers[i][key]),
 * plus the body-type toggle that shows/hides the raw JSON editor. The
 * editor's content mirrors into a hidden `body` textarea so the form
 * still posts it as a normal field.
 */
Alpine.data('endpointForm', (config) => withEditableJsonBehaviour({
    bodyType: config.bodyType || 'json',
    body: config.body || '',
    method: config.method || 'GET',
    url: config.url || '',
    urlDriven: false,
    activeTab: (config.method || 'GET') === 'GET' ? 'params' : 'body',
    paramRows: config.params && config.params.length ? config.params : [{ key: '', value: '' }],
    formRows: config.formRows && config.formRows.length ? config.formRows : [{ key: '', value: '' }],
    headerRows: config.headers && config.headers.length ? config.headers : [{ key: '', value: '' }],

    headersMode: 'rows',
    headersJson: '',
    headersEditorView: null,
    headersJsonError: null,

    jsonEditorView: null,
    jsonFormatError: null,
    payloadCopied: false,

    init() {
        this.$watch('method', () => this.syncActiveTab());
        this.$watch('bodyType', (value) => this.syncBodyType(value));
        this.$watch('headersMode', (value) => this.syncHeadersMode(value));
        this.watchUrlParams();
        if (this.activeTab === 'body' && this.bodyType === 'json') {
            this.$nextTick(() => this.mountJsonEditor());
        }
    },

    syncActiveTab() {
        if (this.isGet && this.activeTab === 'body') this.activeTab = 'params';
        if (!this.isGet && this.activeTab === 'params') this.showBodyTab();
        // Switching back to GET picks the URL's query string back up.
        if (this.isGet) this.syncParamsFromUrl();
    },

    get payloadText() {
        if (this.bodyType === 'form') {
            return this.formRows
                .filter((row) => row.key)
                .map((row) => `${encodeURIComponent(row.key)}=${encodeURIComponent(row.value)}`)
                .join('&');
        }
        return this.body;
    },

    async copyPayload() {
        const ok = await copyToClipboard(this.payloadText || '');
        if (ok) {
            this.payloadCopied = true;
            setTimeout(() => {
                this.payloadCopied = false;
            }, 1500);
        }
    },
}));

/**
 * Drives the request builder + "Send" flow used by both the Quick
 * Test tab and the per-endpoint test page. Everything here is
 * client-side/ephemeral; saving an endpoint is a normal form post
 * handled separately (see endpointForm).
 */
Alpine.data('requestRunner', (config) => withEditableJsonBehaviour({
    method: config.method || 'GET',
    url: config.url || '',
    urlDriven: false,
    bodyType: config.bodyType || 'json',
    body: config.body || '',
    paramRows: config.params && config.params.length ? config.params : [{ key: '', value: '' }],
    formRows: config.formRows && config.formRows.length ? config.formRows : [{ key: '', value: '' }],
    headerRows: config.headers && config.headers.length ? config.headers : [{ key: '', value: '' }],
    activeTab: (config.method || 'GET') === 'GET' ? 'params' : 'body',

    headersMode: 'rows',
    headersJson: '',
    headersEditorView: null,
    headersJsonError: null,

    persist: !!config.persist,
    storageKey: config.storageKey || 'apiBench:lastRequest',

    // Environment: either this browser's Quick Test variables (localStorage,
    // scoped like the rest of Quick Test's persistence) or a project's saved
    // variables (server-side, shared with anyone who opens that project).
    envScope: config.environmentScope || 'quickTest',
    envProjectId: config.projectId || null,
    envRows: config.environmentVariables && config.environmentVariables.length
        ? config.environmentVariables
        : [{ key: '', value: '', enabled: true }],
    envOpen: false,
    envSaving: false,
    envSaved: false,
    envError: null,

    // Set only on a saved endpoint's page: lets the builder write the request
    // back to that endpoint instead of the edits being run-only.
    endpointId: config.endpointId || null,
    endpointSaving: false,
    endpointSaved: false,
    endpointSaveError: null,

    jsonEditorView: null,
    jsonFormatError: null,
    responseEditorView: null,
    responseTab: 'body',
    responseBodyView: 'raw',
    copied: false,
    payloadCopied: false,

    loading: false,
    error: null,
    response: null,

    init() {
        if (this.persist) this.restore();
        if (this.envScope === 'quickTest') this.restoreEnvironment();
        this.$watch('method', () => this.syncActiveTab());
        this.$watch('bodyType', (value) => this.syncBodyType(value));
        this.$watch('headersMode', (value) => this.syncHeadersMode(value));
        this.watchUrlParams();
        // Switching from Preview to Raw is the first time the raw-text
        // container is actually visible, so that's when it gets mounted.
        this.$watch('responseBodyView', (view) => {
            if (view === 'raw') this.$nextTick(() => this.renderResponseBody());
        });

        // Open on whichever tab actually carries something, so a filed
        // endpoint shows its payload instead of an empty params table.
        if (this.activeTab === 'body' && this.bodyType === 'json') {
            this.showBodyTab();
        }
    },

    syncActiveTab() {
        if (this.isGet && this.activeTab === 'body') this.activeTab = 'params';
        if (!this.isGet && this.activeTab === 'params') this.showBodyTab();
        // Switching back to GET picks the URL's query string back up.
        if (this.isGet) this.syncParamsFromUrl();
    },

    restoreEnvironment() {
        try {
            const raw = localStorage.getItem('apiBench:quickTestEnv');
            const saved = raw ? JSON.parse(raw) : null;
            if (saved && saved.length) this.envRows = saved;
        } catch (e) {
            // storage unavailable - keep the default empty row
        }
    },

    /** Enabled, named rows only — what actually resolves a {{name}}. */
    get activeVariables() {
        return this.envRows.filter((row) => row.key && row.enabled !== false);
    },

    get filledEnvCount() {
        return this.activeVariables.length;
    },

    addEnvRow() {
        this.envRows.push({ key: '', value: '', enabled: true });
    },

    removeEnvRow(index) {
        this.envRows.splice(index, 1);
        if (this.envRows.length === 0) this.addEnvRow();
        this.saveEnvironment();
    },

    /**
     * Quick Test's environment lives in this browser only (localStorage),
     * matching how it persists the last request. A project's environment is
     * shared, so it's saved to the server instead — called on blur/change
     * rather than per keystroke.
     */
    async saveEnvironment() {
        if (this.envScope === 'quickTest') {
            try {
                localStorage.setItem('apiBench:quickTestEnv', JSON.stringify(this.envRows));
            } catch (e) {
                // storage unavailable (private mode, quota) - ignore
            }
            return;
        }

        if (!this.envProjectId) return;
        this.envSaving = true;
        this.envError = null;
        try {
            const res = await fetch(`/projects/${this.envProjectId}/environment`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ variables: this.envRows }),
            });
            if (!res.ok) throw new Error('Save failed');
            this.envSaved = true;
            setTimeout(() => {
                this.envSaved = false;
            }, 1500);
        } catch (e) {
            this.envError = 'Could not save environment.';
        } finally {
            this.envSaving = false;
        }
    },

    /**
     * Writes the request as it currently stands back to the saved endpoint —
     * method, URL, params/form fields, headers and body. Headers edited in the
     * JSON view are folded back into rows first, since rows are what's stored.
     */
    async saveEndpoint() {
        if (!this.endpointId) return;

        this.ensureHeaderRowsSynced();
        this.endpointSaving = true;
        this.endpointSaveError = null;

        try {
            const res = await fetch(`/endpoints/${this.endpointId}/request`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    method: this.method,
                    url: this.url,
                    body_type: this.bodyType,
                    body: this.body,
                    params: this.isGet ? this.paramRows : this.formRows,
                    headers: this.headerRows,
                }),
            });
            if (!res.ok) throw new Error('Save failed');
            this.endpointSaved = true;
            setTimeout(() => {
                this.endpointSaved = false;
            }, 1500);
        } catch (e) {
            this.endpointSaveError = 'Could not save.';
        } finally {
            this.endpointSaving = false;
        }
    },

    get statusClass() {
        const status = this.response?.status ?? 0;
        if (status >= 200 && status < 300) return 'status-2xx';
        if (status >= 300 && status < 400) return 'status-3xx';
        return 'status-4xx';
    },

    get prettySize() {
        const bytes = this.response?.size_bytes ?? 0;
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    },

    /** Laravel hands back each response header value as an array. */
    get responseHeaderRows() {
        const headers = this.response?.headers ?? {};
        return Object.entries(headers).map(([key, value]) => ({
            key,
            value: Array.isArray(value) ? value.join(', ') : String(value),
        }));
    },

    /** True when the response declares itself as HTML, regardless of header casing. */
    get isHtmlResponse() {
        const headers = this.response?.headers ?? {};
        const contentTypeKey = Object.keys(headers).find((key) => key.toLowerCase() === 'content-type');
        if (!contentTypeKey) return false;
        const value = headers[contentTypeKey];
        const contentType = Array.isArray(value) ? value.join(', ') : String(value);
        return contentType.toLowerCase().includes('text/html');
    },

    showResponseBody() {
        this.responseTab = 'body';
        this.$nextTick(() => this.renderResponseBody());
    },

    /**
     * Builds the response viewer into whichever container is currently mounted.
     * The response block is re-created on every send, so the view is rebuilt
     * rather than refilled — which also guarantees CodeMirror measures its line
     * heights against the layout that is really on screen. No-ops while the
     * Raw view isn't showing (e.g. an HTML response defaults to Preview) —
     * mounting into a hidden, zero-width container would just fail silently.
     * The responseBodyView watcher below mounts it once Raw is actually shown.
     */
    renderResponseBody() {
        if (!this.response?.raw_body) return;
        if (this.responseBodyView !== 'raw') return;
        const body = this.prettyBody;

        this.responseEditorView?.destroy();
        this.responseEditorView = null;

        mountWhenReady(() => this.$refs.responseEditor, (container) => {
            mountVerified(
                container,
                () => createJsonViewer(container, body),
                (view, { disposing } = {}) => {
                    if (disposing) {
                        this.responseEditorView?.destroy();
                        this.responseEditorView = null;
                        return;
                    }
                    this.responseEditorView = view;
                }
            );
        });
    },

    get payloadText() {
        if (this.bodyType === 'form') {
            return this.formRows
                .filter((row) => row.key)
                .map((row) => `${encodeURIComponent(row.key)}=${encodeURIComponent(row.value)}`)
                .join('&');
        }
        return this.body;
    },

    async copyPayload() {
        const ok = await copyToClipboard(this.payloadText || '');
        if (ok) {
            this.payloadCopied = true;
            setTimeout(() => {
                this.payloadCopied = false;
            }, 1500);
        }
    },

    restore() {
        let saved;
        try {
            const raw = localStorage.getItem(this.storageKey);
            saved = raw ? JSON.parse(raw) : null;
        } catch (e) {
            saved = null;
        }
        if (!saved) return;

        this.method = saved.method ?? this.method;
        this.url = saved.url ?? this.url;
        this.bodyType = saved.bodyType ?? this.bodyType;
        this.body = saved.body ?? this.body;
        if (saved.paramRows?.length) this.paramRows = saved.paramRows;
        if (saved.formRows?.length) this.formRows = saved.formRows;
        if (saved.headerRows?.length) this.headerRows = saved.headerRows;
        this.activeTab = this.isGet ? 'params' : 'body';
    },

    persistState() {
        if (!this.persist) return;
        try {
            localStorage.setItem(this.storageKey, JSON.stringify({
                method: this.method,
                url: this.url,
                bodyType: this.bodyType,
                body: this.body,
                paramRows: this.paramRows,
                formRows: this.formRows,
                headerRows: this.headerRows,
            }));
        } catch (e) {
            // storage unavailable (private mode, quota) - ignore
        }
    },

    clearAll() {
        this.method = 'GET';
        this.url = '';
        this.bodyType = 'json';
        this.body = '';
        this.paramRows = [{ key: '', value: '' }];
        this.formRows = [{ key: '', value: '' }];
        this.headerRows = [{ key: '', value: '' }];
        this.headersMode = 'rows';
        this.headersJson = '';
        this.headersJsonError = null;
        this.activeTab = 'params';
        this.response = null;
        this.error = null;
        this.jsonFormatError = null;
        this.responseBodyView = 'raw';
        setEditorContent(this.jsonEditorView, '');
        setEditorContent(this.headersEditorView, '');
        setEditorContent(this.responseEditorView, '');
        if (this.persist) {
            try {
                localStorage.removeItem(this.storageKey);
            } catch (e) {
                // ignore
            }
        }
    },

    clearUrl() {
        this.url = '';
    },

    clearPayload() {
        this.body = '';
        this.paramRows = [{ key: '', value: '' }];
        this.formRows = [{ key: '', value: '' }];
        this.jsonFormatError = null;
        setEditorContent(this.jsonEditorView, '');
    },

    get filledParamCount() {
        return this.paramRows.filter((row) => row.key).length;
    },

    get filledHeaderCount() {
        return this.headerRows.filter((row) => row.key).length;
    },

    async send() {
        this.loading = true;
        this.error = null;
        this.response = null;
        this.persistState();

        // Variables resolve into the outgoing request only — the URL, body
        // and rows on screen keep showing the {{name}} template, exactly
        // like Postman leaves a saved request untouched by environment state.
        const vars = this.activeVariables;
        const resolve = (text) => resolveVariables(text, vars);

        const params = {};
        const rows = this.isGet ? this.paramRows : this.formRows;
        for (const row of rows) {
            if (row.key) params[resolve(row.key)] = resolve(row.value);
        }
        const headers = this.headerRows
            .filter((row) => row.key)
            .map((row) => ({ key: resolve(row.key), value: resolve(row.value) }));

        try {
            const res = await fetch('/api/run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    method: this.method,
                    url: resolve(this.url),
                    body_type: this.bodyType,
                    body: resolve(this.body),
                    params,
                    headers,
                }),
            });

            const data = await res.json();

            if (!res.ok && data.error) {
                this.error = data.error;
            } else {
                this.response = data;
                this.responseTab = 'body';
                // HTML defaults to its rendered preview; anything else shows raw.
                this.responseBodyView = this.isHtmlResponse ? 'preview' : 'raw';
                this.$nextTick(() => this.renderResponseBody());
            }
        } catch (e) {
            this.error = e.message || 'Request failed.';
        } finally {
            this.loading = false;
        }
    },

    async copyResponse() {
        const ok = await copyToClipboard(this.prettyBody || '');
        if (ok) {
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 1500);
        }
    },

    get prettyBody() {
        if (!this.response) return '';
        if (this.response.is_json) {
            return JSON.stringify(this.response.body, null, 2);
        }
        return this.response.raw_body;
    },
}));

window.Alpine = Alpine;
Alpine.start();

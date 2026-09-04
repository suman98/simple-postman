import Alpine from 'alpinejs';
import { EditorView, keymap, lineNumbers, highlightActiveLine } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { json, jsonParseLinter } from '@codemirror/lang-json';
import { linter, lintGutter } from '@codemirror/lint';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching } from '@codemirror/language';

/**
 * Mounts a small JSON-aware CodeMirror editor into `container` and keeps
 * `onChange` in sync with its content. Used for every raw JSON body field
 * (Quick Test, endpoint show/test, endpoint create/edit).
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
 * Endpoint create/edit form: params + headers row repeaters that post
 * as normal named array fields (params[i][key], headers[i][key]),
 * plus the body-type toggle that shows/hides the raw JSON editor. The
 * editor's content mirrors into a hidden `body` textarea so the form
 * still posts it as a normal field.
 */
Alpine.data('endpointForm', (config) => ({
    bodyType: config.bodyType || 'json',
    body: config.body || '',
    method: config.method || 'GET',
    activeTab: 'params',
    paramRows: config.params && config.params.length ? config.params : [{ key: '', value: '' }],
    headerRows: config.headers && config.headers.length ? config.headers : [{ key: '', value: '' }],

    jsonEditorView: null,
    jsonFormatError: null,

    /** Mounted the first time the Body tab is actually shown. */
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
        this.$nextTick(() => this.mountJsonEditor());
    },

    addParam() {
        this.paramRows.push({ key: '', value: '' });
    },
    removeParam(index) {
        this.paramRows.splice(index, 1);
        if (this.paramRows.length === 0) this.addParam();
    },
    addHeader() {
        this.headerRows.push({ key: '', value: '' });
    },
    removeHeader(index) {
        this.headerRows.splice(index, 1);
        if (this.headerRows.length === 0) this.addHeader();
    },

    get methodClass() {
        return 'method-' + this.method.toLowerCase();
    },

    get paramsLabel() {
        return this.bodyType === 'form' ? 'Form data' : 'Params';
    },
}));

/**
 * Drives the request builder + "Send" flow used by both the Quick
 * Test tab and the per-endpoint test page. Everything here is
 * client-side/ephemeral; saving an endpoint is a normal form post
 * handled separately (see endpointForm).
 */
Alpine.data('requestRunner', (config) => ({
    method: config.method || 'GET',
    url: config.url || '',
    bodyType: config.bodyType || 'json',
    body: config.body || '',
    paramRows: config.params && config.params.length ? config.params : [{ key: '', value: '' }],
    headerRows: config.headers && config.headers.length ? config.headers : [{ key: '', value: '' }],
    activeTab: 'params',

    persist: !!config.persist,
    storageKey: config.storageKey || 'apiBench:lastRequest',

    jsonEditorView: null,
    jsonFormatError: null,
    responseEditorView: null,
    responseTab: 'body',
    copied: false,

    init() {
        if (this.persist) this.restore();

        // Open on whichever tab actually carries something, so a filed
        // endpoint shows its payload instead of an empty params table.
        if (this.bodyType === 'json' && this.body.trim() !== '') {
            this.showBodyTab();
        }
    },

    /** Mounted the first time the Body tab is actually shown. */
    mountJsonEditor() {
        if (this.jsonEditorView) return;
        mountWhenReady(() => this.$refs.jsonEditor, (el) => {
            if (this.jsonEditorView) return;
            this.jsonEditorView = createJsonEditor(el, this.body, (value) => {
                this.body = value;
            });
        });
    },

    get methodClass() {
        return 'method-' + this.method.toLowerCase();
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

    showResponseBody() {
        this.responseTab = 'body';
        this.$nextTick(() => this.renderResponseBody());
    },

    /**
     * Builds the response viewer into whichever container is currently mounted.
     * The response block is re-created on every send, so the view is rebuilt
     * rather than refilled — which also guarantees CodeMirror measures its line
     * heights against the layout that is really on screen.
     */
    renderResponseBody() {
        if (!this.response?.raw_body) return;
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
        this.$nextTick(() => this.mountJsonEditor());
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
        if (saved.headerRows?.length) this.headerRows = saved.headerRows;
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
        this.headerRows = [{ key: '', value: '' }];
        this.response = null;
        this.error = null;
        this.jsonFormatError = null;
        setEditorContent(this.jsonEditorView, '');
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
        this.jsonFormatError = null;
        setEditorContent(this.jsonEditorView, '');
    },

    addParam() {
        this.paramRows.push({ key: '', value: '' });
    },
    removeParam(index) {
        this.paramRows.splice(index, 1);
        if (this.paramRows.length === 0) this.addParam();
    },
    addHeader() {
        this.headerRows.push({ key: '', value: '' });
    },
    removeHeader(index) {
        this.headerRows.splice(index, 1);
        if (this.headerRows.length === 0) this.addHeader();
    },

    loading: false,
    error: null,
    response: null,

    get paramsLabel() {
        return this.bodyType === 'form' ? 'Form data' : 'Params';
    },

    /** GET/DELETE send params on the query string; the rest send a body. */
    get paramsHint() {
        const asQuery = this.method === 'GET' || this.method === 'DELETE';
        if (asQuery) return 'Sent as query string parameters.';
        return this.bodyType === 'form'
            ? 'Sent as form-encoded fields in the request body.'
            : 'Sent as query string parameters. Use the Body tab for the JSON payload.';
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

        const params = {};
        for (const row of this.paramRows) {
            if (row.key) params[row.key] = row.value;
        }
        const headers = this.headerRows.filter((row) => row.key);

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
                    url: this.url,
                    body_type: this.bodyType,
                    body: this.body,
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

    get statusClass() {
        if (!this.response) return '';
        if (this.response.status >= 200 && this.response.status < 300) return 'text-green-600';
        if (this.response.status >= 400) return 'text-red-600';
        return 'text-amber-600';
    },
}));

window.Alpine = Alpine;
Alpine.start();

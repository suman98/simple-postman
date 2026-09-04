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
                '&': { fontSize: '13px', height: '260px' },
                '.cm-scroller': { overflow: 'auto', fontFamily: 'ui-monospace, monospace' },
            }),
        ],
        parent: container,
    });

    return view;
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
    return new EditorView({
        doc: initialDoc || '',
        extensions: [
            lineNumbers(),
            json(),
            syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
            EditorState.readOnly.of(true),
            EditorView.editable.of(false),
            EditorView.theme({
                '&': { fontSize: '12px', height: '380px', backgroundColor: '#ffffff' },
                '.cm-content': { color: '#0f172a', caretColor: 'transparent' },
                '.cm-scroller': { overflow: 'auto', fontFamily: 'ui-monospace, monospace' },
                '.cm-gutters': { backgroundColor: '#f8fafc', color: '#94a3b8', border: 'none' },
                '.cm-activeLineGutter, .cm-activeLine': { backgroundColor: 'transparent' },
            }),
        ],
        parent: container,
    });
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
    activeTab: 'params',
    paramRows: config.params && config.params.length ? config.params : [{ key: '', value: '' }],
    headerRows: config.headers && config.headers.length ? config.headers : [{ key: '', value: '' }],

    jsonEditorView: null,
    jsonFormatError: null,

    init() {
        this.jsonEditorView = createJsonEditor(this.$refs.jsonEditor, this.body, (value) => {
            this.body = value;
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
        this.$nextTick(() => this.jsonEditorView?.requestMeasure());
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

    get paramsLabel() {
        return this.bodyType === 'form' ? 'Form Data' : 'Query / Params';
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
    copied: false,

    init() {
        if (this.persist) this.restore();
        this.jsonEditorView = createJsonEditor(this.$refs.jsonEditor, this.body, (value) => {
            this.body = value;
        });
        this.responseEditorView = createJsonViewer(this.$refs.responseEditor, '');
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
        this.$nextTick(() => this.jsonEditorView?.requestMeasure());
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
        return this.bodyType === 'form' ? 'Form Data' : 'Query / Params';
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
                this.$nextTick(() => {
                    setEditorContent(this.responseEditorView, this.prettyBody);
                    this.responseEditorView?.requestMeasure();
                });
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

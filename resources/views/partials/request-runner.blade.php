@php
    // $runner: ['method'=>, 'url'=>, 'bodyType'=>, 'body'=>, 'params'=>[['key','value']], 'headers'=>[['key','value']], 'persist'=>bool]
@endphp
<div x-data="requestRunner({{ Illuminate\Support\Js::from($runner) }})">

    {{-- Environment --}}
    <section class="panel mb-4" aria-label="Environment">
        <button type="button" @click="envOpen = !envOpen" class="flex w-full items-center justify-between px-3 py-2.5">
            <span class="text-sm font-medium">
                Environment<span class="text-text-faint" x-show="filledEnvCount" x-text="` (${filledEnvCount})`" x-cloak></span>
            </span>
            <span class="text-xs text-text-muted" x-text="envOpen ? 'Hide' : 'Show'"></span>
        </button>

        <div x-show="envOpen" x-cloak class="border-t border-border p-3">
            <p class="mb-3 text-xs text-text-muted">
                Use <code class="font-mono">@{{name}}</code> in the URL, params, headers, or body &mdash; resolved when you send.
                <span x-show="envScope === 'quickTest'">Saved to this browser only.</span>
                <span x-show="envScope === 'project'">Saved to this project.</span>
            </p>

            <div class="mb-1.5 grid grid-cols-[20px_minmax(0,1fr)_minmax(0,1.5fr)_28px] gap-x-2">
                <span></span>
                <span class="text-xs text-text-muted">Key</span>
                <span class="text-xs text-text-muted">Value</span>
            </div>
            <template x-for="(row, index) in envRows" :key="index">
                <div class="mb-1.5 grid grid-cols-[20px_minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                    <input type="checkbox" x-model="row.enabled" @change="saveEnvironment()" class="h-4 w-4" aria-label="Enabled">
                    <input type="text" x-model="row.key" @change="saveEnvironment()" placeholder="base_url" class="field field-mono" spellcheck="false">
                    <input type="text" x-model="row.value" @change="saveEnvironment()" placeholder="https://api.example.com" class="field field-mono" spellcheck="false">
                    <button type="button" @click="removeEnvRow(index)" class="text-text-faint hover:text-danger"
                            :aria-label="`Remove ${row.key || 'empty'} variable`">&times;</button>
                </div>
            </template>

            <div class="flex items-center gap-3">
                <button type="button" @click="addEnvRow" class="btn btn-link">+ Add variable</button>
                <span class="ml-auto flex items-center gap-2 text-xs">
                    <span x-show="envSaving" class="text-text-muted">Saving…</span>
                    <span x-show="envSaved" x-cloak class="text-success">Saved</span>
                    <span x-show="envError" x-cloak class="text-danger" x-text="envError"></span>
                </span>
            </div>
        </div>
    </section>

    {{-- Request --}}
    <section class="panel" aria-label="Request">
        <div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center">
            <select x-model="method" class="method-select shrink-0" :class="methodClass" aria-label="HTTP method">
                <option>GET</option>
                <option>POST</option>
                <option>PUT</option>
                <option>PATCH</option>
                <option>DELETE</option>
            </select>

            <input type="text" inputmode="url" x-model="url" @keydown.enter="send" spellcheck="false"
                   placeholder="https://api.example.com/users"
                   class="field field-mono min-w-0 flex-1" aria-label="Request URL">

            <button @click="send" :disabled="loading || !url" class="btn btn-primary shrink-0">
                <span x-show="!loading">Send</span>
                <span x-show="loading" x-cloak>Sending…</span>
            </button>

            @if ($runner['endpointId'] ?? false)
                <button type="button" @click="saveEndpoint" :disabled="endpointSaving" class="btn btn-secondary shrink-0">
                    <span x-show="!endpointSaving && !endpointSaved">Save</span>
                    <span x-show="endpointSaving" x-cloak>Saving…</span>
                    <span x-show="endpointSaved" x-cloak class="text-success">Saved</span>
                </button>
            @endif
        </div>

        @if ($runner['endpointId'] ?? false)
            <p class="px-3 pb-2 text-xs text-danger" x-show="endpointSaveError" x-text="endpointSaveError" x-cloak></p>
        @endif

        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-t border-border px-3">
            <button type="button" x-show="isGet" @click="activeTab = 'params'" class="tab" :class="activeTab === 'params' && 'tab-active'">
                Params<span class="text-text-faint" x-show="filledParamCount" x-text="` (${filledParamCount})`" x-cloak></span>
            </button>
            <button type="button" x-show="!isGet" @click="showBodyTab()" class="tab" :class="activeTab === 'body' && 'tab-active'">Body</button>
            <button type="button" @click="showHeadersTab()" class="tab" :class="activeTab === 'headers' && 'tab-active'">
                Headers<span class="text-text-faint" x-show="filledHeaderCount" x-text="` (${filledHeaderCount})`" x-cloak></span>
            </button>
        </div>

        <div class="border-t border-border p-3">
            <div x-show="activeTab === 'params' && isGet">
                <p class="mb-3 text-xs text-text-muted">Sent as query string parameters.</p>
                <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] gap-x-2">
                    <span class="text-xs text-text-muted">Key</span>
                    <span class="text-xs text-text-muted">Value</span>
                </div>
                <template x-for="(row, index) in paramRows" :key="index">
                    <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                        <input type="text" x-model="row.key" @change="persistState()" placeholder="key" class="field field-mono" spellcheck="false">
                        <input type="text" x-model="row.value" @change="persistState()" placeholder="value" class="field field-mono" spellcheck="false">
                        <button type="button" @click="removeParam(index)" class="text-text-faint hover:text-danger"
                                :aria-label="`Remove ${row.key || 'empty'} parameter`">&times;</button>
                    </div>
                </template>
                <button type="button" @click="addParam" class="btn btn-link mt-1">+ Add row</button>
            </div>

            <div x-show="activeTab === 'body' && !isGet" x-cloak>
                <div class="mb-1.5 flex items-center justify-between">
                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] gap-x-2" x-show="bodyType === 'form'">
                        <span class="text-xs text-text-muted">Key</span>
                        <span class="text-xs text-text-muted">Value</span>
                    </div>
                    <span x-show="bodyType === 'json'" class="text-xs text-text-muted">JSON request body</span>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" @click="copyPayload" class="btn btn-secondary btn-sm">
                            <span x-show="!payloadCopied">Copy</span>
                            <span x-show="payloadCopied" x-cloak class="text-success">Copied</span>
                        </button>
                        <button type="button" @click="formatJson" x-show="bodyType === 'json'" class="btn btn-secondary btn-sm">Format</button>
                        <label class="flex items-center gap-2">
                            <span class="text-xs text-text-muted">View</span>
                            <select x-model="bodyType" class="field w-auto px-2 py-1 text-xs" aria-label="Body view">
                                <option value="json">JSON</option>
                                <option value="form">Form data</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div x-show="bodyType === 'json'">
                    <div x-ref="jsonEditor" class="overflow-hidden rounded border border-border"></div>
                    <p class="mt-2 text-xs text-danger" x-show="jsonFormatError" x-text="jsonFormatError" x-cloak></p>
                </div>

                <div x-show="bodyType === 'form'">
                    <template x-for="(row, index) in formRows" :key="index">
                        <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                            <input type="text" x-model="row.key" @change="persistState()" placeholder="key" class="field field-mono" spellcheck="false">
                            <input type="text" x-model="row.value" @change="persistState()" placeholder="value" class="field field-mono" spellcheck="false">
                            <button type="button" @click="removeFormRow(index)" class="text-text-faint hover:text-danger"
                                    :aria-label="`Remove ${row.key || 'empty'} field`">&times;</button>
                        </div>
                    </template>
                    <button type="button" @click="addFormRow" class="btn btn-link mt-1">+ Add row</button>
                </div>
                <p class="mt-2 text-xs text-text-faint">Switching JSON / Form data carries the payload across.</p>
            </div>

            <div x-show="activeTab === 'headers'" x-cloak>
                <div class="mb-1.5 flex items-center justify-between">
                    <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] gap-x-2" x-show="headersMode === 'rows'">
                        <span class="text-xs text-text-muted">Key</span>
                        <span class="text-xs text-text-muted">Value</span>
                    </div>
                    <span x-show="headersMode === 'json'" class="text-xs text-text-muted">Headers as JSON</span>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" @click="formatHeadersJson" x-show="headersMode === 'json'" class="btn btn-secondary btn-sm">Format</button>
                        <label class="flex items-center gap-2">
                            <span class="text-xs text-text-muted">View</span>
                            <select x-model="headersMode" class="field w-auto px-2 py-1 text-xs" aria-label="Headers view">
                                <option value="rows">Rows</option>
                                <option value="json">JSON</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div x-show="headersMode === 'rows'">
                    <template x-for="(row, index) in headerRows" :key="index">
                        <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                            <input type="text" x-model="row.key" @change="persistState()" placeholder="Content-Type" class="field field-mono" spellcheck="false">
                            <input type="text" x-model="row.value" @change="persistState()" placeholder="application/json" class="field field-mono" spellcheck="false">
                            <button type="button" @click="removeHeader(index)" class="text-text-faint hover:text-danger"
                                    :aria-label="`Remove ${row.key || 'empty'} header`">&times;</button>
                        </div>
                    </template>
                    <button type="button" @click="addHeader" class="btn btn-link mt-1">+ Add row</button>
                </div>

                <div x-show="headersMode === 'json'">
                    <div x-ref="headersEditor" class="overflow-hidden rounded border border-border"></div>
                    <p class="mt-2 text-xs text-danger" x-show="headersJsonError" x-text="headersJsonError" x-cloak></p>
                </div>
            </div>
        </div>

        @if ($runner['persist'] ?? false)
            <div class="flex flex-wrap items-center gap-4 border-t border-border px-3 py-2">
                <button type="button" @click="clearUrl" class="btn btn-link">Clear URL</button>
                <button type="button" @click="clearPayload" class="btn btn-link">Clear body</button>
                <button type="button" @click="clearAll" class="btn btn-link">Clear all</button>
            </div>
        @endif
    </section>

    {{-- Response --}}
    <section class="panel mt-4" aria-label="Response">
        <div class="flex flex-wrap items-center justify-between gap-3 px-3 py-2.5" :class="(response || error) && 'border-b border-border'">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm">
                <span class="font-medium">Response</span>

                <template x-if="response">
                    <span class="flex flex-wrap items-center gap-x-5 gap-y-1">
                        <span class="status-code" :class="statusClass">
                            <span x-text="response.status"></span>
                            <span class="text-text-muted" x-text="response.reason"></span>
                        </span>
                        <span class="text-xs text-text-muted">Time <span class="font-mono text-text" x-text="`${response.duration_ms} ms`"></span></span>
                        <span class="text-xs text-text-muted">Size <span class="font-mono text-text" x-text="prettySize"></span></span>
                    </span>
                </template>
            </div>

            <button type="button" @click="copyResponse" class="btn btn-secondary btn-sm" x-show="response" x-cloak>
                <span x-show="!copied">Copy</span>
                <span x-show="copied" x-cloak class="text-success">Copied</span>
            </button>
        </div>

        {{-- Empty --}}
        <p class="px-3 py-14 text-center text-sm text-text-muted" x-show="!response && !error && !loading" x-cloak>
            Send a request to see the response.
        </p>

        {{-- Loading --}}
        <p class="px-3 py-14 text-center text-sm text-text-muted" x-show="loading" x-cloak>
            Sending request…
        </p>

        {{-- Failed before a response existed --}}
        <div class="px-3 py-6" x-show="error" x-cloak role="alert">
            <p class="mb-1 text-sm font-medium text-danger">Request failed</p>
            <p class="font-mono text-xs text-text-muted" x-text="error"></p>
        </div>

        <template x-if="response">
            <div>
                <div class="flex flex-wrap items-center gap-x-5 border-b border-border px-3">
                    <button type="button" @click="showResponseBody()" class="tab" :class="responseTab === 'body' && 'tab-active'">Body</button>
                    <button type="button" @click="responseTab = 'headers'" class="tab" :class="responseTab === 'headers' && 'tab-active'">
                        Headers <span class="text-text-faint" x-text="`(${responseHeaderRows.length})`"></span>
                    </button>
                </div>

                <div class="p-3">
                    <div x-show="responseTab === 'body'">
                        <p class="py-10 text-center text-sm text-text-muted" x-show="!response.raw_body" x-cloak>
                            This response has no body.
                        </p>

                        <div x-show="response.raw_body">
                            <div class="mb-2 flex items-center justify-end" x-show="isHtmlResponse" x-cloak>
                                <label class="flex items-center gap-2">
                                    <span class="text-xs text-text-muted">View</span>
                                    <select x-model="responseBodyView" class="field w-auto px-2 py-1 text-xs" aria-label="Response view">
                                        <option value="raw">Raw</option>
                                        <option value="preview">Preview</option>
                                    </select>
                                </label>
                            </div>

                            <div x-ref="responseEditor" class="overflow-hidden rounded border border-border" x-show="responseBodyView === 'raw'"></div>

                            <iframe x-show="responseBodyView === 'preview'" x-cloak
                                    :srcdoc="response.raw_body"
                                    sandbox=""
                                    class="h-[420px] w-full rounded border border-border bg-white"
                                    title="Response preview"></iframe>
                        </div>
                    </div>

                    <div x-show="responseTab === 'headers'" x-cloak>
                        <table class="kv-table">
                            <thead>
                                <tr>
                                    <th class="w-1/3">Key</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in responseHeaderRows" :key="row.key">
                                    <tr>
                                        <td class="text-text-muted" x-text="row.key"></td>
                                        <td x-text="row.value"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </section>
</div>

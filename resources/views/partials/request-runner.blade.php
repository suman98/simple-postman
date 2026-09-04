@php
    // $runner: ['method'=>, 'url'=>, 'bodyType'=>, 'body'=>, 'params'=>[['key','value']], 'headers'=>[['key','value']], 'persist'=>bool]
@endphp
<div x-data="requestRunner({{ Illuminate\Support\Js::from($runner) }})">

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

            <input type="url" x-model="url" @keydown.enter="send" spellcheck="false"
                   placeholder="https://api.example.com/users"
                   class="field field-mono min-w-0 flex-1" aria-label="Request URL">

            <button @click="send" :disabled="loading || !url" class="btn btn-primary shrink-0">
                <span x-show="!loading">Send</span>
                <span x-show="loading" x-cloak>Sending…</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 border-t border-border px-3">
            <button type="button" @click="activeTab = 'params'" class="tab" :class="activeTab === 'params' && 'tab-active'">
                <span x-text="paramsLabel"></span><span class="text-text-faint" x-show="filledParamCount" x-text="` (${filledParamCount})`" x-cloak></span>
            </button>
            <button type="button" @click="showBodyTab()" x-show="bodyType === 'json'" class="tab" :class="activeTab === 'body' && 'tab-active'">Body</button>
            <button type="button" @click="activeTab = 'headers'" class="tab" :class="activeTab === 'headers' && 'tab-active'">
                Headers<span class="text-text-faint" x-show="filledHeaderCount" x-text="` (${filledHeaderCount})`" x-cloak></span>
            </button>

            <label class="ml-auto flex shrink-0 items-center gap-2 py-1.5">
                <span class="whitespace-nowrap text-xs text-text-muted">Body type</span>
                <select x-model="bodyType" class="field w-auto px-2 py-1 text-xs" aria-label="Body type">
                    <option value="json">JSON</option>
                    <option value="form">Form data</option>
                </select>
            </label>
        </div>

        <div class="border-t border-border p-3">
            <div x-show="activeTab === 'params'">
                <p class="mb-3 text-xs text-text-muted" x-text="paramsHint"></p>
                <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] gap-x-2">
                    <span class="text-xs text-text-muted">Key</span>
                    <span class="text-xs text-text-muted">Value</span>
                </div>
                <template x-for="(row, index) in paramRows" :key="index">
                    <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                        <input type="text" x-model="row.key" placeholder="key" class="field field-mono" spellcheck="false">
                        <input type="text" x-model="row.value" placeholder="value" class="field field-mono" spellcheck="false">
                        <button type="button" @click="removeParam(index)" class="text-text-faint hover:text-danger"
                                :aria-label="`Remove ${row.key || 'empty'} parameter`">&times;</button>
                    </div>
                </template>
                <button type="button" @click="addParam" class="btn btn-link mt-1">+ Add row</button>
            </div>

            <div x-show="activeTab === 'body'" x-cloak>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs text-text-muted">JSON request body</span>
                    <button type="button" @click="formatJson" class="btn btn-secondary btn-sm">Format</button>
                </div>
                <div x-ref="jsonEditor" class="overflow-hidden rounded border border-border"></div>
                <p class="mt-2 text-xs text-danger" x-show="jsonFormatError" x-text="jsonFormatError" x-cloak></p>
            </div>

            <div x-show="activeTab === 'headers'" x-cloak>
                <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] gap-x-2">
                    <span class="text-xs text-text-muted">Key</span>
                    <span class="text-xs text-text-muted">Value</span>
                </div>
                <template x-for="(row, index) in headerRows" :key="index">
                    <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                        <input type="text" x-model="row.key" placeholder="Content-Type" class="field field-mono" spellcheck="false">
                        <input type="text" x-model="row.value" placeholder="application/json" class="field field-mono" spellcheck="false">
                        <button type="button" @click="removeHeader(index)" class="text-text-faint hover:text-danger"
                                :aria-label="`Remove ${row.key || 'empty'} header`">&times;</button>
                    </div>
                </template>
                <button type="button" @click="addHeader" class="btn btn-link mt-1">+ Add row</button>
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
                        <div x-ref="responseEditor" class="overflow-hidden rounded border border-border" x-show="response.raw_body"></div>
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

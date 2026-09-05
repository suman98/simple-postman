@php $endpoint ??= null; @endphp

<div>
    <label for="endpoint-name" class="field-label">Name</label>
    <input id="endpoint-name" type="text" name="name" value="{{ old('name', $endpoint->name ?? '') }}"
           class="field @error('name') border-danger @enderror" placeholder="Create user" required autofocus
           @error('name') aria-invalid="true" aria-describedby="endpoint-name-error" @enderror>
    @error('name')
        <p id="endpoint-name-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>

<div class="space-y-4">
    <div>
        <span class="field-label">Request</span>
        <div class="flex flex-col gap-2 sm:flex-row">
            <select name="method" x-model="method" class="method-select shrink-0" :class="methodClass" aria-label="HTTP method">
                @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>

            <input type="text" inputmode="url" name="url" value="{{ old('url', $endpoint->url ?? '') }}"
                   placeholder="https://api.example.com/users" spellcheck="false" required
                   class="field field-mono min-w-0 flex-1 @error('url') border-danger @enderror"
                   aria-label="Request URL"
                   @error('url') aria-invalid="true" aria-describedby="endpoint-url-error" @enderror>
        </div>
        @error('url')
            <p id="endpoint-url-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="overflow-hidden rounded border border-border">
        <div class="flex flex-wrap items-center gap-x-5 border-b border-border bg-sunken px-3">
            <button type="button" x-show="isGet" @click="activeTab = 'params'" class="tab" :class="activeTab === 'params' && 'tab-active'">Params</button>
            <button type="button" x-show="!isGet" @click="showBodyTab()" class="tab" :class="activeTab === 'body' && 'tab-active'">Body</button>
            <button type="button" @click="showHeadersTab()" class="tab" :class="activeTab === 'headers' && 'tab-active'">Headers</button>
        </div>

        <div class="p-3">
            <div x-show="activeTab === 'params' && isGet">
                <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] gap-x-2">
                    <span class="text-xs text-text-muted">Key</span>
                    <span class="text-xs text-text-muted">Value</span>
                </div>
                <template x-for="(row, index) in paramRows" :key="index">
                    <div class="mb-1.5 grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_28px] items-center gap-x-2">
                        <input type="text" :name="`params[${index}][key]`" :disabled="!isGet" x-model="row.key" placeholder="key" class="field field-mono" spellcheck="false">
                        <input type="text" :name="`params[${index}][value]`" :disabled="!isGet" x-model="row.value" placeholder="value" class="field field-mono" spellcheck="false">
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
                            <select name="body_type" x-model="bodyType" class="field w-auto px-2 py-1 text-xs" aria-label="Body view">
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
                            <input type="text" :name="`params[${index}][key]`" :disabled="isGet || bodyType !== 'form'" x-model="row.key" placeholder="key" class="field field-mono" spellcheck="false">
                            <input type="text" :name="`params[${index}][value]`" :disabled="isGet || bodyType !== 'form'" x-model="row.value" placeholder="value" class="field field-mono" spellcheck="false">
                            <button type="button" @click="removeFormRow(index)" class="text-text-faint hover:text-danger"
                                    :aria-label="`Remove ${row.key || 'empty'} field`">&times;</button>
                        </div>
                    </template>
                    <button type="button" @click="addFormRow" class="btn btn-link mt-1">+ Add row</button>
                </div>
                <p class="mt-2 text-xs text-text-faint">Switching JSON / Form data carries the payload across.</p>
                <textarea name="body" x-model="body" class="hidden" aria-hidden="true" tabindex="-1"></textarea>
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
                            <input type="text" :name="`headers[${index}][key]`" x-model="row.key" placeholder="Content-Type" class="field field-mono" spellcheck="false">
                            <input type="text" :name="`headers[${index}][value]`" x-model="row.value" placeholder="application/json" class="field field-mono" spellcheck="false">
                            <button type="button" @click="removeHeader(index)" class="text-text-faint hover:text-danger"
                                    :aria-label="`Remove ${row.key || 'empty'} header`">&times;</button>
                        </div>
                    </template>
                    <button type="button" @click="addHeader" class="btn btn-link mt-1">+ Add row</button>
                </div>

                <div x-show="headersMode === 'json'">
                    <div x-ref="headersEditor" class="overflow-hidden rounded border border-border"></div>
                    <p class="mt-2 text-xs text-danger" x-show="headersJsonError" x-text="headersJsonError" x-cloak></p>
                    <p class="mt-2 text-xs text-text-faint">Switched back to Rows on save, so this always posts correctly.</p>
                </div>
            </div>
        </div>
    </div>
</div>

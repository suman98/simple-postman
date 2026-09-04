@php
    // $runner: ['method'=>, 'url'=>, 'bodyType'=>, 'body'=>, 'params'=>[['key','value']], 'headers'=>[['key','value']], 'persist'=>bool]
@endphp
<div x-data="requestRunner({{ Illuminate\Support\Js::from($runner) }})" class="bg-white rounded-lg shadow">
    <div class="flex gap-2 p-4 border-b">
        <select x-model="method" class="border rounded px-2 py-2 text-sm font-mono bg-white">
            <option>GET</option>
            <option>POST</option>
            <option>PUT</option>
            <option>PATCH</option>
            <option>DELETE</option>
        </select>
        <input type="text" x-model="url" placeholder="https://api.example.com/resource"
               class="flex-1 border rounded px-3 py-2 text-sm font-mono">
        <button @click="send" :disabled="loading || !url"
                class="bg-blue-600 text-white px-5 py-2 rounded text-sm font-medium disabled:opacity-50">
            <span x-show="!loading">Send</span>
            <span x-show="loading">Sending…</span>
        </button>
    </div>

    @if ($runner['persist'] ?? false)
        <div class="flex gap-3 px-4 py-2 border-b text-sm">
            <button type="button" @click="clearAll" class="text-slate-500 hover:text-red-600">Clear All</button>
            <button type="button" @click="clearUrl" class="text-slate-500 hover:text-red-600">Clear URL</button>
            <button type="button" @click="clearPayload" class="text-slate-500 hover:text-red-600">Clear Payload</button>
        </div>
    @endif

    <div class="px-4 pt-3 flex items-center gap-4 text-sm border-b">
        <button type="button" @click="activeTab = 'params'"
                :class="activeTab === 'params' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                class="pb-2 border-b-2" x-text="paramsLabel"></button>
        <button type="button" @click="showBodyTab()" x-show="bodyType === 'json'"
                :class="activeTab === 'body' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                class="pb-2 border-b-2">Body</button>
        <button type="button" @click="activeTab = 'headers'"
                :class="activeTab === 'headers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                class="pb-2 border-b-2">Headers</button>

        <label class="ml-auto pb-2 flex items-center gap-2 text-slate-500">
            <span>Body type</span>
            <select x-model="bodyType" class="border rounded px-2 py-1 bg-white">
                <option value="json">JSON</option>
                <option value="form">Form Data</option>
            </select>
        </label>
    </div>

    <div class="p-4">
        <div x-show="activeTab === 'params'">
            <template x-for="(row, index) in paramRows" :key="index">
                <div class="flex gap-2 mb-2">
                    <input type="text" x-model="row.key" placeholder="key" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                    <input type="text" x-model="row.value" placeholder="value" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                    <button type="button" @click="removeParam(index)" class="text-slate-400 hover:text-red-600 px-2">&times;</button>
                </div>
            </template>
            <button type="button" @click="addParam" class="text-sm text-blue-600 hover:underline">+ Add row</button>
        </div>

        <div x-show="activeTab === 'body'" x-cloak>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-slate-400">Raw JSON body</span>
                <button type="button" @click="formatJson" class="text-sm text-blue-600 hover:underline">Format</button>
            </div>
            <div x-ref="jsonEditor" class="border rounded overflow-hidden"></div>
            <p class="text-red-600 text-sm mt-1" x-show="jsonFormatError" x-text="jsonFormatError" x-cloak></p>
        </div>

        <div x-show="activeTab === 'headers'" x-cloak>
            <template x-for="(row, index) in headerRows" :key="index">
                <div class="flex gap-2 mb-2">
                    <input type="text" x-model="row.key" placeholder="Header-Name" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                    <input type="text" x-model="row.value" placeholder="value" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                    <button type="button" @click="removeHeader(index)" class="text-slate-400 hover:text-red-600 px-2">&times;</button>
                </div>
            </template>
            <button type="button" @click="addHeader" class="text-sm text-blue-600 hover:underline">+ Add row</button>
        </div>
    </div>

    <div class="px-4 pb-4" x-show="error" x-cloak>
        <div class="bg-red-50 text-red-700 p-3 rounded text-sm" x-text="error"></div>
    </div>

    <div class="px-4 pb-4" x-show="response" x-cloak>
        <div class="flex items-center gap-4 text-sm mb-2 border-t pt-3">
            <span>Status: <strong :class="statusClass" x-text="response?.status"></strong></span>
            <span>Time: <strong x-text="response?.duration_ms"></strong> ms</span>
            <button type="button" @click="copyResponse" class="ml-auto text-slate-500 hover:text-blue-600 flex items-center gap-1">
                <span x-show="!copied">Copy</span>
                <span x-show="copied" class="text-green-600" x-cloak>Copied!</span>
            </button>
        </div>
        <div x-ref="responseEditor" class="border rounded overflow-hidden"></div>
    </div>
</div>

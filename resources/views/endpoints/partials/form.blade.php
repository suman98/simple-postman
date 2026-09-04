@php
    $endpoint ??= null;
    $formConfig = [
        'bodyType' => old('body_type', $endpoint->body_type ?? 'json'),
        'body' => old('body', $endpoint->body ?? ''),
        'params' => old('params', collect($endpoint->params ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray()),
        'headers' => old('headers', $endpoint->headers ?? []),
    ];
@endphp

<div>
    <label class="block text-sm font-medium mb-1">Name</label>
    <input type="text" name="name" value="{{ old('name', $endpoint->name ?? '') }}"
           class="w-full border rounded px-3 py-2 text-sm" required autofocus>
    @error('name')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div x-data="endpointForm({{ Illuminate\Support\Js::from($formConfig) }})">
    <div class="flex gap-2 mb-4">
        <select name="method" class="border rounded px-2 py-2 text-sm font-mono bg-white">
            @foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                <option value="{{ $m }}" @selected(old('method', $endpoint->method ?? 'GET') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <input type="text" name="url" value="{{ old('url', $endpoint->url ?? '') }}"
               placeholder="https://api.example.com/resource"
               class="flex-1 border rounded px-3 py-2 text-sm font-mono" required>
    </div>
    @error('url')
        <p class="text-red-600 text-sm -mt-3 mb-3">{{ $message }}</p>
    @enderror

    <div class="border rounded-lg">
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
                <select name="body_type" x-model="bodyType" class="border rounded px-2 py-1 bg-white">
                    <option value="json">JSON</option>
                    <option value="form">Form Data</option>
                </select>
            </label>
        </div>

        <div class="p-4">
            <div x-show="activeTab === 'params'">
                <template x-for="(row, index) in paramRows" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="`params[${index}][key]`" x-model="row.key" placeholder="key" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                        <input type="text" :name="`params[${index}][value]`" x-model="row.value" placeholder="value" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
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
                <textarea name="body" x-model="body" class="hidden" aria-hidden="true"></textarea>
            </div>

            <div x-show="activeTab === 'headers'" x-cloak>
                <template x-for="(row, index) in headerRows" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="`headers[${index}][key]`" x-model="row.key" placeholder="Header-Name" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                        <input type="text" :name="`headers[${index}][value]`" x-model="row.value" placeholder="value" class="flex-1 border rounded px-2 py-1 text-sm font-mono">
                        <button type="button" @click="removeHeader(index)" class="text-slate-400 hover:text-red-600 px-2">&times;</button>
                    </div>
                </template>
                <button type="button" @click="addHeader" class="text-sm text-blue-600 hover:underline">+ Add row</button>
            </div>
        </div>
    </div>
</div>

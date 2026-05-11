@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Nuevo Traslado" />

    @php
        $productsJson = $products->map(fn ($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'use_batches' => (bool) $p->use_batches,
            'barcodes'    => $p->barcodes->pluck('barcode')->toArray(),
        ])->values()->toArray();

        $stockEndpoint   = route('inventory.transfers.stock');
        $productsEndpoint = route('inventory.transfers.productsByLocation');
    @endphp

    {{-- ═══ Datos para Alpine fuera del atributo HTML para evitar conflictos de comillas ═══ --}}
    <script>
        window.__transferData = {
            products:         @json($productsJson),
            stockEndpoint:    @json($stockEndpoint),
            productsEndpoint: @json($productsEndpoint),
            fromLocationId:   @json(old('from_location_id', '')),
            toLocationId:     @json(old('to_location_id', '')),
        };
    </script>

    {{-- Flash / errors --}}
    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-red-50 border border-red-100 px-5 py-3 text-sm font-bold text-[#e11d48]">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            {{ session('error') }}
        </div>
    @endif

    @php $lineErrorKeys = collect($errors->keys())->filter(fn ($k) => str_starts_with($k, 'lines.')); @endphp
    @if($lineErrorKeys->isNotEmpty())
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-100 px-5 py-4 text-sm text-[#e11d48]">
            <p class="mb-2 font-bold">Revise los errores en las líneas de producto:</p>
            <ul class="list-inside list-disc space-y-0.5 font-medium">
                @foreach($lineErrorKeys as $key)
                    @foreach($errors->get($key) as $msg)<li>{{ $msg }}</li>@endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('inventory.transfers.store') }}"
          x-data="transferForm()"
          class="space-y-6">
        @csrf

        {{-- ── Cabecera ── --}}
        <div class="rounded-[2.5rem] border border-gray-100 bg-white p-8 shadow-sm">
            <div class="mb-6 border-b border-gray-50 pb-6">
                <h3 class="text-2xl font-bold text-[#1e293b]">Nuevo Traslado</h3>
                <p class="text-sm text-gray-500">Mueva mercadería entre ubicaciones del inventario</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Ubicaciones --}}
                <div class="space-y-5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ubicaciones</p>

                    {{-- Origen --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">
                            Origen <span class="text-[#e11d48]">*</span>
                        </label>
                        <div class="relative">
                            <select name="from_location_id"
                                x-model="fromLocationId"
                                @change="onOriginChange()"
                                class="h-11 w-full appearance-none rounded-xl border border-gray-200 bg-white px-4 pr-10 text-sm text-gray-700 focus:border-gray-400 focus:outline-none transition-all {{ $errors->has('from_location_id') ? 'border-[#e11d48]' : '' }}">
                                <option value="">— Seleccionar origen —</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('from_location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </div>
                        @error('from_location_id')
                            <p class="mt-1 text-xs font-bold text-[#e11d48]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Destino --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">
                            Destino <span class="text-[#e11d48]">*</span>
                        </label>
                        <div class="relative">
                            <select name="to_location_id"
                                x-model="toLocationId"
                                class="h-11 w-full appearance-none rounded-xl border border-gray-200 bg-white px-4 pr-10 text-sm text-gray-700 focus:border-gray-400 focus:outline-none transition-all {{ $errors->has('to_location_id') ? 'border-[#e11d48]' : '' }}">
                                <option value="">— Seleccionar destino —</option>
                                <option value="third_party" {{ old('to_location_id') === 'third_party' ? 'selected' : '' }}>🧍 Traslado a tercero</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('to_location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </div>
                        {{-- Nota cuando es traslado a tercero --}}
                        <p x-show="toLocationId === 'third_party'" x-cloak
                            class="mt-2 flex items-center gap-1.5 text-xs font-medium text-amber-600">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                            El stock saldrá del origen sin moverse a otro inventario interno.
                        </p>
                        @error('to_location_id')
                            <p class="mt-1 text-xs font-bold text-[#e11d48]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Información adicional --}}
                <div class="space-y-5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Información Adicional</p>

                    {{-- Cliente externo (opcional) --}}
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 space-y-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            Cliente externo
                            <span class="ml-1 normal-case font-medium text-gray-300">(opcional — para traslados a terceros)</span>
                        </p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Nombre</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                    placeholder="Ej: María González"
                                    class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Celular</label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                    placeholder="Ej: 70012345"
                                    class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Notas</label>
                        <textarea name="notes" rows="3" placeholder="Ej: solicitud de tienda, reposición mensual..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Líneas de productos ── --}}
        <div class="rounded-[2.5rem] border border-gray-100 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-gray-50 px-8 py-6">
                <div>
                    <h3 class="text-xl font-bold text-[#1e293b]">Productos a trasladar</h3>
                    <p class="text-sm text-gray-500">Solo se muestran productos con stock disponible en el origen</p>
                </div>
                <button type="button" @click="addLine()"
                    class="flex h-11 items-center gap-2 rounded-xl bg-[#1e293b] px-6 text-sm font-bold text-white hover:bg-[#334155] transition-all active:scale-95">
                    <span class="text-lg">+</span> Agregar producto
                </button>
            </div>

            {{-- Aviso cuando no se ha elegido origen --}}
            <div x-show="!fromLocationId" class="px-8 py-6">
                <div class="flex items-center gap-3 rounded-xl border border-amber-100 bg-amber-50 px-5 py-3 text-sm font-bold text-amber-700">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    Selecciona el origen para cargar los productos disponibles
                </div>
            </div>

            <div class="divide-y divide-gray-50" x-show="fromLocationId">
                <template x-for="(line, i) in lines" :key="i">
                    <div class="relative px-8 py-6">

                        <div class="mb-4 flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400"
                                  x-text="`Producto #${i + 1}`"></span>
                            <button type="button" @click="removeLine(i)"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-400 hover:bg-red-50 hover:text-[#e11d48] transition-all"
                                :class="lines.length === 1 ? 'opacity-30 cursor-not-allowed' : ''"
                                :disabled="lines.length === 1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Eliminar
                            </button>
                        </div>

                        {{-- Producto: Tom Select + Barcode --}}
                        <div class="mb-5">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">
                                        Producto <span class="text-[#e11d48]">*</span>
                                    </label>
                                    <select :id="`ts-transfer-${i}`" class="w-full"></select>
                                    <input type="hidden" :name="`lines[${i}][product_id]`" :value="line.product_id" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">
                                        Código de barras
                                        <span class="ml-1 text-[10px] font-medium normal-case text-gray-400">(escaneo o manual)</span>
                                    </label>
                                    <input type="text"
                                        :id="`bc-transfer-${i}`"
                                        x-model="line.barcodeQuery"
                                        @keydown.enter.prevent="onBarcodeEnterLine(i)"
                                        @input="onBarcodeInputLine(i)"
                                        placeholder="Escanee o escriba el código..."
                                        autocomplete="off"
                                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 font-mono text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all" />
                                    <p x-show="line.barcodeNotFound" class="mt-1 text-xs font-bold text-[#e11d48]">Código no encontrado en el stock del origen.</p>
                                </div>
                            </div>
                            <p x-show="line.product_id" class="mt-1.5 flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-emerald-600">✓ <span x-text="line.product_name"></span></span>
                                <template x-if="line.loading">
                                    <span class="text-xs text-gray-400">Cargando stock...</span>
                                </template>
                                <template x-if="!line.loading && line.available !== null">
                                    <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-xs font-bold"
                                        :class="line.available > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-[#e11d48]'">
                                        Disponible: <span class="ml-1" x-text="line.available"></span>
                                    </span>
                                </template>
                            </p>
                        </div>

                        {{-- Selector de lote --}}
                        <template x-if="line.use_batches">
                            <div class="mb-5">
                                <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Lote</label>
                                <div class="relative">
                                    <select :name="`lines[${i}][batch_id]`"
                                        x-model="line.batch_id"
                                        @change="fetchStock(i)"
                                        class="h-11 w-full appearance-none rounded-xl border border-gray-200 bg-white px-4 pr-10 text-sm text-gray-700 focus:border-gray-400 focus:outline-none transition-all">
                                        <option value="">— Sin lote específico (PEPS automático) —</option>
                                        <template x-for="batch in line.batches" :key="batch.id">
                                            <option :value="batch.id"
                                                x-text="`${batch.batch_code}${batch.expiration_date ? ' — vence: ' + batch.expiration_date : ''} (disp: ${batch.available})`">
                                            </option>
                                        </template>
                                    </select>
                                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </div>
                            </div>
                        </template>
                        <template x-if="!line.use_batches">
                            <input type="hidden" :name="`lines[${i}][batch_id]`" value="" />
                        </template>

                        {{-- Cantidad --}}
                        <div class="max-w-xs">
                            <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">
                                Cantidad <span class="text-[#e11d48]">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01"
                                :name="`lines[${i}][quantity]`"
                                x-model.number="line.quantity"
                                @change="clampQuantity(i)"
                                :max="line.available ?? undefined"
                                placeholder="0.00"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 focus:border-gray-400 focus:outline-none transition-all" />
                            <template x-if="line.available !== null && parseFloat(line.quantity) > line.available">
                                <p class="mt-1 text-xs font-bold text-[#e11d48]">No puede superar el stock disponible.</p>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="rounded-b-[2.5rem] border-t border-gray-50 bg-gray-50/30 px-8 py-4" x-show="fromLocationId">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400"
                   x-text="`${lines.length} producto${lines.length !== 1 ? 's' : ''} en este traslado`"></p>
            </div>
        </div>

        {{-- ── Acciones ── --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                class="flex h-11 items-center gap-2 rounded-xl bg-[#e11d48] px-8 text-sm font-bold text-white shadow-md transition-all hover:bg-[#be123c] active:scale-95">
                Registrar Traslado
            </button>
            <a href="{{ route('inventory.transfers.index') }}"
               class="flex h-11 items-center rounded-xl bg-gray-100 px-8 text-sm font-bold text-gray-500 hover:bg-gray-200 hover:text-[#1e293b] transition-all">
                Cancelar
            </a>
        </div>
    </form>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
    .ts-transfer + .ts-wrapper .ts-control,
    .ts-transfer.ts-wrapper .ts-control,
    [id^="ts-transfer-"] + .ts-wrapper .ts-control,
    .ts-wrapper.single .ts-control { border-radius:.75rem; border:1px solid #e5e7eb; background:#fff; padding:0 1rem; height:2.75rem; font-size:.875rem; color:#374151; box-shadow:none; cursor:pointer; }
    .ts-wrapper.single .ts-control:focus-within { border-color:#9ca3af; }
    .ts-dropdown { border-radius:.75rem; border:1px solid #f3f4f6; box-shadow:0 10px 25px -5px rgb(0 0 0/.1); z-index:9999; }
    .ts-dropdown .option { padding:.6rem 1rem; font-size:.875rem; }
    .ts-dropdown .option.active { background:#f9fafb; color:#1e293b; }
    .ts-dropdown .option:hover { background:#f3f4f6; }
</style>

<script>
function transferForm() {
    return {
        products:         window.__transferData.products,       // all active products (fallback)
        stockProducts:    [],                                    // products with stock in origin
        stockEndpoint:    window.__transferData.stockEndpoint,
        productsEndpoint: window.__transferData.productsEndpoint,
        fromLocationId:   window.__transferData.fromLocationId,
        toLocationId:     window.__transferData.toLocationId,
        lines:            [],
        __tsInstances:    {},

        init() {
            this.addLine();
            // If old() had a fromLocationId (form re-submit), load products
            if (this.fromLocationId) {
                this.$nextTick(() => this.loadProductsForOrigin());
            }
        },

        newLine() {
            return {
                product_id:    '',
                product_name:  '',
                use_batches:   false,
                barcodeQuery:  '',
                barcodeNotFound: false,
                batch_id:      '',
                batches:       [],
                available:     null,
                loading:       false,
                quantity:      '',
            };
        },

        addLine() {
            const i = this.lines.length;
            this.lines.push(this.newLine());
            this.$nextTick(() => this.initTs(i));
        },

        removeLine(i) {
            if (this.lines.length <= 1) return;
            if (this.__tsInstances[i]) { this.__tsInstances[i].destroy(); delete this.__tsInstances[i]; }
            this.lines.splice(i, 1);
            // Re-init remaining instances with shifted keys
            const old = { ...this.__tsInstances };
            this.__tsInstances = {};
            this.$nextTick(() => {
                this.lines.forEach((_, idx) => {
                    if (old[idx < i ? idx : idx + 1]) {
                        this.__tsInstances[idx] = old[idx < i ? idx : idx + 1];
                    } else {
                        this.initTs(idx);
                    }
                });
            });
        },

        initTs(i) {
            const el = document.getElementById(`ts-transfer-${i}`);
            if (!el) return;
            if (this.__tsInstances[i]) { this.__tsInstances[i].destroy(); }
            const self = this;
            const opts = this.stockProducts.map(p => ({ value: String(p.id), text: p.name }));
            const ts = new TomSelect(el, {
                options:     opts,
                placeholder: this.fromLocationId ? 'Buscar producto...' : 'Selecciona el origen primero...',
                maxOptions:  200,
                create:      false,
                allowEmptyOption: true,
                onChange(value) {
                    if (!value) {
                        self.lines[i].product_id   = '';
                        self.lines[i].product_name = '';
                        self.lines[i].use_batches  = false;
                        self.lines[i].barcodeQuery = '';
                        self.lines[i].available    = null;
                        self.lines[i].batches      = [];
                        return;
                    }
                    const p = self.stockProducts.find(p => String(p.id) === String(value));
                    if (!p) return;
                    self.lines[i].product_id   = p.id;
                    self.lines[i].product_name = p.name;
                    self.lines[i].use_batches  = p.use_batches;
                    self.lines[i].barcodeQuery = p.barcodes.length > 0 ? p.barcodes[0] : '';
                    self.lines[i].barcodeNotFound = false;
                    self.lines[i].batch_id     = '';
                    self.lines[i].batches      = [];
                    self.lines[i].available    = null;
                    self.lines[i].quantity     = '';
                    self.fetchStock(i);
                },
            });
            if (!this.fromLocationId) ts.disable();
            this.__tsInstances[i] = ts;
        },

        rebuildTsOptions() {
            const opts = this.stockProducts.map(p => ({ value: String(p.id), text: p.name }));
            Object.values(this.__tsInstances).forEach(ts => {
                ts.clearOptions();
                ts.addOptions(opts);
                ts.clear(true);
                ts.enable();
                ts.settings.placeholder = 'Buscar producto...';
                ts.inputState();
            });
            // Reset all lines
            this.lines.forEach(line => {
                line.product_id   = '';
                line.product_name = '';
                line.use_batches  = false;
                line.barcodeQuery = '';
                line.barcodeNotFound = false;
                line.batch_id     = '';
                line.batches      = [];
                line.available    = null;
                line.quantity     = '';
            });
        },

        async loadProductsForOrigin() {
            if (!this.fromLocationId) return;
            try {
                const r    = await fetch(`${this.productsEndpoint}?location_id=${this.fromLocationId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await r.json();
                this.stockProducts = data.products ?? [];
            } catch(e) {
                this.stockProducts = [];
            }
            this.rebuildTsOptions();
        },

        onOriginChange() {
            this.loadProductsForOrigin();
        },

        onBarcodeInputLine(i) {
            this.lines[i].barcodeNotFound = false;
            const code = this.lines[i].barcodeQuery.trim();
            if (!code) return;
            const found = this.stockProducts.find(p => p.barcodes.some(b => b === code));
            if (found) this._selectProductOnLine(i, found);
        },

        onBarcodeEnterLine(i) {
            const code = this.lines[i].barcodeQuery.trim();
            if (!code) return;
            const found = this.stockProducts.find(p => p.barcodes.some(b => b === code));
            if (found) { this._selectProductOnLine(i, found); return; }
            this.lines[i].barcodeNotFound = true;
        },

        _selectProductOnLine(i, p) {
            this.lines[i].product_id   = p.id;
            this.lines[i].product_name = p.name;
            this.lines[i].use_batches  = p.use_batches;
            this.lines[i].barcodeNotFound = false;
            if (this.__tsInstances[i]) this.__tsInstances[i].setValue(String(p.id), true);
            this.fetchStock(i);
        },

        async fetchStock(i) {
            const line = this.lines[i];
            if (!line.product_id || !this.fromLocationId) { line.available = null; return; }
            line.loading = true;
            const params = new URLSearchParams({
                product_id:  line.product_id,
                location_id: this.fromLocationId,
                ...(line.batch_id ? { batch_id: line.batch_id } : {}),
            });
            try {
                const r    = await fetch(`${this.stockEndpoint}?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await r.json();
                line.available = data.available;
                line.batches   = data.batches ?? [];
            } catch(e) {
                line.available = null;
            } finally {
                line.loading = false;
            }
        },

        clampQuantity(i) {
            const line = this.lines[i];
            if (line.available !== null && parseFloat(line.quantity) > line.available) {
                line.quantity = line.available;
            }
        },
    };
}
</script>
@endpush
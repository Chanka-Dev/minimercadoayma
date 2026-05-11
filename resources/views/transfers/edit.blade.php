@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Editar Traslado" />

    <div class="mx-auto max-w-2xl space-y-6">

        {{-- Encabezado --}}
        <div class="rounded-[2.5rem] border border-gray-100 bg-white p-8 shadow-sm">
            <div class="mb-6 border-b border-gray-50 pb-5">
                <h3 class="text-2xl font-bold text-[#1e293b]">Editar datos del traslado</h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Traslado registrado el {{ $group->created_at->format('d/m/Y \a \l\a\s H:i') }}
                </p>
            </div>

            {{-- Resumen de ubicaciones (solo lectura) --}}
            @php
                $outMove = $group->movements->first(fn ($m) => $m->from_location_id !== null);
                $inMove  = $group->movements->first(fn ($m) => $m->to_location_id !== null);
            @endphp
            <div class="mb-6 flex flex-wrap items-center gap-3 rounded-xl bg-gray-50 px-5 py-3 text-sm font-medium text-gray-500">
                <span class="font-bold text-[#1e293b]">{{ $outMove?->fromLocation?->name ?? '—' }}</span>
                <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
                <span class="font-bold text-[#1e293b]">{{ $inMove?->toLocation?->name ?? '—' }}</span>
                <span class="ml-auto text-xs text-gray-400">{{ $group->movements->count() / 2 }} producto(s) trasladado(s)</span>
            </div>

            <form method="POST" action="{{ route('inventory.transfers.update', $group->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Cliente externo --}}
                <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-5 space-y-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600">
                            Cliente externo
                            <span class="ml-1 normal-case font-medium text-amber-400">(opcional)</span>
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Nombre</label>
                            <input type="text" name="customer_name"
                                value="{{ old('customer_name', $group->customer_name) }}"
                                placeholder="Ej: María González"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all @error('customer_name') border-[#e11d48] @enderror" />
                            @error('customer_name')
                                <p class="mt-1 text-xs font-bold text-[#e11d48]">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Celular</label>
                            <input type="text" name="customer_phone"
                                value="{{ old('customer_phone', $group->customer_phone) }}"
                                placeholder="Ej: 70012345"
                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all @error('customer_phone') border-[#e11d48] @enderror" />
                            @error('customer_phone')
                                <p class="mt-1 text-xs font-bold text-[#e11d48]">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Notas --}}
                <div>
                    <label class="mb-1.5 block text-sm font-bold text-[#1e293b]">Observaciones</label>
                    <textarea name="notes" rows="3"
                        placeholder="Notas adicionales del traslado..."
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none transition-all @error('notes') border-[#e11d48] @enderror">{{ old('notes', $group->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs font-bold text-[#e11d48]">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="flex h-11 items-center gap-2 rounded-xl bg-[#1e293b] px-8 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#334155] active:scale-95">
                        Guardar cambios
                    </button>
                    <a href="{{ route('inventory.transfers.show', $group->id) }}"
                        class="flex h-11 items-center rounded-xl bg-gray-100 px-6 text-sm font-bold text-gray-500 hover:bg-gray-200 hover:text-[#1e293b] transition-all">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

    </div>
@endsection

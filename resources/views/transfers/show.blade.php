@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Detalle del Traslado" />

    {{-- Notificaciones de Éxito --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-100 px-5 py-3 text-sm font-bold text-emerald-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-red-50 border border-red-100 px-5 py-3 text-sm font-bold text-red-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Cancelado --}}
    @if($group->isCancelled())
        <div class="mb-6 flex items-center gap-4 rounded-[2rem] border border-red-100 bg-red-50 px-8 py-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-red-600">Traslado Cancelado</p>
                <p class="mt-0.5 text-sm font-bold text-[#1e293b]">Este traslado fue cancelado y el stock fue revertido al origen.</p>
                @if($group->cancelled_at)
                    <p class="text-xs font-medium text-red-400">{{ $group->cancelled_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>
    @endif

    @php
        // Lógica de movimientos (sin cambios)
        $outMove = $group->movements->first(fn ($m) => $m->from_location_id !== null);
        $inMove  = $group->movements->first(fn ($m) => $m->to_location_id !== null);
        $uniqueMovements = $group->movements
            ->where('from_location_id', '!=', null)
            ->values();
    @endphp

    {{-- ── Cabecera de Información (Tarjetas Ayma Style) ── --}}
    <div class="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Fecha --}}
        <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Fecha del Traslado</p>
            <p class="mt-1 text-base font-bold text-[#1e293b]">
                {{ $group->created_at->format('d/m/Y') }}
            </p>
            <p class="text-xs font-medium text-[#e11d48]">{{ $group->created_at->format('H:i') }}</p>
        </div>

        {{-- Origen --}}
        <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ubicación Origen</p>
            <p class="mt-1 text-base font-bold text-[#1e293b]">
                {{ $outMove?->fromLocation?->name ?? '—' }}
            </p>
            <span class="mt-2 inline-flex items-center rounded-lg bg-blue-50 px-2 py-0.5 text-[10px] font-black uppercase text-blue-600">Salida</span>
        </div>

        {{-- Destination --}}
        <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ubicación Destino</p>
            @if($inMove?->toLocation)
                <p class="mt-1 text-base font-bold text-[#1e293b]">{{ $inMove->toLocation->name }}</p>
                <span class="mt-2 inline-flex items-center rounded-lg bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-600">Entrada</span>
            @else
                <p class="mt-1 text-base font-bold text-[#1e293b]">Tercero</p>
                <span class="mt-2 inline-flex items-center rounded-lg bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase text-amber-600">🧍 Externo</span>
            @endif
        </div>

        {{-- User --}}
        <div class="rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Registrado por</p>
            <p class="mt-1 text-base font-bold text-[#1e293b]">
                {{ $group->user?->name ?? '—' }}
            </p>
        </div>
    </div>

    {{-- ── Cliente externo (si existe) ── --}}
    @if($group->customer_name || $group->customer_phone)
        <div class="mb-6 flex items-center gap-5 rounded-[2rem] border border-amber-100 bg-amber-50 px-8 py-5 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600">Traslado a cliente externo</p>
                <p class="mt-0.5 text-base font-bold text-[#1e293b]">{{ $group->customer_name ?? '—' }}</p>
                @if($group->customer_phone)
                    <p class="text-sm font-medium text-amber-700">
                        <svg class="inline-block h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ $group->customer_phone }}
                    </p>
                @endif
            </div>
            @if(!$group->isCancelled())
            <a href="{{ route('inventory.transfers.edit', $group->id) }}"
                class="shrink-0 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition-all">
                Editar datos
            </a>
            @endif
        </div>
    @endif

    {{-- ── Tabla de Productos (Estilo Redondeado Ayma) ── --}}
    <div class="overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-50 bg-gray-50/50 px-8 py-5">
            <h3 class="text-sm font-black uppercase tracking-widest text-[#1e293b]">Productos trasladados</h3>
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-[#1e293b] px-4 py-2 text-xs font-bold text-white transition-all hover:bg-[#334155] active:scale-95 print:hidden">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0v5h12v-5"/></svg>
                Imprimir nota
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white">
                        <th class="px-8 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Producto</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Lote</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Vencimiento</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($uniqueMovements as $movement)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-8 py-4 font-bold text-[#1e293b]">
                                {{ $movement->product->name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-gray-500">
                                {{ $movement->batch?->batch_code ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($movement->batch?->expiration_date)
                                    @php $exp = $movement->batch->expiration_date; @endphp
                                    <span class="font-bold {{ $exp->isPast() ? 'text-[#e11d48]' : ($exp->diffInDays() < 30 ? 'text-amber-600' : 'text-gray-500') }}">
                                        {{ $exp->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-8 py-4 text-right font-black text-[#e11d48] text-base">
                                {{ number_format($movement->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Notas --}}
    @if($group->notes)
        <div class="mt-6 rounded-[2rem] border border-gray-100 bg-white px-8 py-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Observaciones</p>
            <p class="mt-1 text-sm font-medium text-gray-600 italic">"{{ $group->notes }}"</p>
        </div>
    @endif

    {{-- Print-only header (hidden on screen) --}}
    <div class="hidden print:block print:mb-8 print:border-b-2 print:border-black print:pb-4">
        <h1 class="text-2xl font-black uppercase tracking-tighter text-black">Micromercado Ayma</h1>
        <h2 class="text-lg font-bold text-gray-700">Nota de Traslado de Inventario</h2>
        <div class="mt-4 grid grid-cols-2 text-sm">
            <p><strong>Origen:</strong> {{ $outMove?->fromLocation?->name }}</p>
            <p><strong>Destino:</strong> {{ $inMove?->toLocation?->name }}</p>
            <p><strong>Fecha:</strong> {{ $group->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Responsable:</strong> {{ $group->user?->name }}</p>
        </div>
    </div>

    {{-- Acciones Finales --}}
    <div class="mt-8 flex items-center gap-4 print:hidden">
        <a href="{{ route('inventory.transfers.index') }}"
           class="inline-flex items-center gap-2 rounded-2xl bg-gray-100 px-8 py-3 text-sm font-black uppercase tracking-widest text-gray-500 transition-all hover:bg-gray-200 hover:text-[#1e293b]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver a Traslados
        </a>
        @if(!$group->isCancelled())
            <a href="{{ route('inventory.transfers.edit', $group->id) }}"
               class="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-500 transition-all hover:bg-gray-50 hover:text-[#1e293b]">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar datos del traslado
            </a>
            <form method="POST" action="{{ route('inventory.transfers.cancel', $group->id) }}"
                onsubmit="return confirm('¿Cancelar este traslado? Se revertirá el stock al origen.')">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl border border-red-100 bg-red-50 px-6 py-3 text-sm font-bold text-red-600 transition-all hover:bg-red-600 hover:text-white">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancelar traslado
                </button>
            </form>
        @endif
    </div>
@endsection
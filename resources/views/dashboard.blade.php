@extends('layouts.app')

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════════
         ENCABEZADO
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-8 flex flex-col gap-1">
        <h1 class="text-3xl font-black tracking-tight text-[#1e293b]">
            Bienvenida, {{ auth()->user()->name }} 👋
        </h1>
        <p class="text-base font-medium text-gray-400">
            {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
        </p>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 1 — ¿QUÉ DEPÓSITO QUIERES VER?
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-8 overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white shadow-sm">

        {{-- Cabecera con franja de color --}}
        <div style="background: #ffffff;" class="px-8 py-6">
            <p style="color: #6d798a; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em;">Paso 1</p>
            <h2 style="color: #1e293b; font-size: 1.5rem; font-weight: 900; margin-top: 4px;">¿Qué depósito quieres ver?</h2>
            <p style="color: #6d798a; font-size: 0.875rem; font-weight: 500; margin-top: 4px;">Elige un depósito y presiona el botón para ver su información</p>
        </div>

        {{-- Formulario selector — fondo blanco, visible en ambos modos --}}
        <form method="GET" action="{{ route('reports.stock') }}" class="border-t border-gray-100 bg-white px-8 py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                {{-- Selector de depósito --}}
                <div class="flex-1">
                    <label class="mb-2 block text-sm font-bold text-gray-700">Seleccionar depósito</label>
                    <div class="relative">
                        <select name="location_id" id="locationSelect"
                            class="h-14 w-full appearance-none rounded-2xl border-2 border-gray-200 bg-white px-5 pr-12 text-base font-bold text-gray-800 focus:border-[#1e293b] focus:outline-none transition-all">
                            <option value="">— Todos los depósitos —</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ $locationId == $loc->id ? 'selected' : '' }}>
                                    📦 {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </div>
                </div>

                {{-- Botón Ver depósito --}}
                <button type="submit"
                    class="flex h-14 items-center justify-center gap-3 rounded-2xl bg-[#e11d48] px-8 text-base font-black uppercase tracking-widest text-white shadow-lg transition-all hover:bg-[#be123c] active:scale-95 whitespace-nowrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Ver este depósito
                </button>
            </div>
        </form>

        {{-- Depósito activo --}}
        @if($location)
            <div class="border-t border-gray-100 bg-gray-50 px-8 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-white">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
                    </span>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600">Viendo ahora</p>
                        <p class="text-lg font-black text-gray-800">{{ $location->name }}</p>
                    </div>
                    @if($stockSummary)
                        <div class="ml-auto flex gap-6 text-right">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Productos</p>
                                <p class="text-2xl font-black text-gray-800">{{ number_format($stockSummary->product_count) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Unidades totales</p>
                                <p class="text-2xl font-black text-gray-800">{{ number_format($stockSummary->total_units, 0) }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ALERTAS DE VENCIMIENTO
    ══════════════════════════════════════════════════════════════════════ --}}
    @if($expiredCount > 0 || $expiringProducts->isNotEmpty())
        <div class="mb-8 overflow-hidden rounded-[2.5rem] border-2 border-amber-200 bg-amber-50 shadow-sm">
            <div class="flex items-start gap-4 px-8 py-6">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-400 text-white shadow-md">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-600">¡Atención!</p>
                    <h3 class="mt-0.5 text-xl font-black text-[#1e293b]">Productos próximos a vencer</h3>
                    <p class="mt-1 text-sm font-medium text-amber-700">
                        @if($expiredCount > 0)
                            <span class="font-black text-[#e11d48]">{{ $expiredCount }} {{ $expiredCount == 1 ? 'producto ya venció' : 'productos ya vencieron' }}</span>
                            @if($expiringProducts->isNotEmpty()) · @endif
                        @endif
                        @if($expiringProducts->isNotEmpty())
                            {{ $expiringProducts->count() }} {{ $expiringProducts->count() == 1 ? 'producto vence' : 'productos vencen' }} en los próximos 30 días
                        @endif
                        {{ $location ? "en {$location->name}" : 'en todos los depósitos' }}
                    </p>
                </div>
                <a href="{{ route('reports.expirations') }}{{ $locationId ? '?location_id='.$locationId : '' }}"
                    class="shrink-0 self-center rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black uppercase tracking-widest text-white transition-all hover:bg-amber-600 active:scale-95">
                    Ver todos
                </a>
            </div>

            @if($expiringProducts->isNotEmpty())
                <div class="border-t border-amber-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-amber-100/60">
                                    <th class="px-8 py-3 text-left text-[10px] font-black uppercase tracking-widest text-amber-700">Producto</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-amber-700">Depósito</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-amber-700">Vence el</th>
                                    <th class="px-6 py-3 text-right text-[10px] font-black uppercase tracking-widest text-amber-700">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-amber-100">
                                @foreach($expiringProducts as $item)
                                    @php
                                        $daysLeft = now()->diffInDays($item->expiration_date, false);
                                        $urgent   = $daysLeft <= 7;
                                    @endphp
                                    <tr class="{{ $urgent ? 'bg-red-50/50' : '' }}">
                                        <td class="px-8 py-3 font-bold text-[#1e293b]">{{ $item->product }}</td>
                                        <td class="px-6 py-3 text-gray-500">{{ $item->location }}</td>
                                        <td class="px-6 py-3">
                                            <span class="font-bold {{ $urgent ? 'text-[#e11d48]' : 'text-amber-600' }}">
                                                {{ \Carbon\Carbon::parse($item->expiration_date)->format('d/m/Y') }}
                                            </span>
                                            <span class="ml-1 text-xs {{ $urgent ? 'text-[#e11d48]' : 'text-gray-400' }}">
                                                ({{ $daysLeft == 0 ? 'hoy' : "en {$daysLeft} días" }})
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right font-black text-[#1e293b]">{{ number_format($item->quantity, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 2 — TABLA ACTIVOS + ACCESOS RÁPIDOS (lado a lado)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid gap-6 lg:grid-cols-5">

        {{-- ── Izquierda: Productos más activos ─────────────────────────────── --}}
        <div class="overflow-hidden rounded-[2.5rem] border border-gray-100 bg-white shadow-sm lg:col-span-3">
            <div class="flex flex-col gap-1 border-b border-gray-50 bg-gray-50/50 px-8 py-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-[#1e293b]">Productos más activos</h3>
                    <p class="text-sm font-medium text-gray-400">
                        Últimos 30 días
                        {{ $location ? "— {$location->name}" : '— todos los depósitos' }}
                    </p>
                </div>
                @if($locationId)
                    <a href="{{ route('reports.stock') }}?location_id={{ $locationId }}"
                        class="shrink-0 inline-flex h-9 items-center rounded-xl bg-gray-100 px-4 text-xs font-black uppercase tracking-widest text-gray-500 hover:bg-[#1e293b] hover:text-white transition-all">
                        Ver stock →
                    </a>
                @endif
            </div>

            @if($topMovedProducts->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-white">
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">#</th>
                                <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-400">Producto</th>
                                <th class="px-4 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Movs.</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-gray-400">Unidades</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topMovedProducts as $i => $item)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-3">
                                        @if($i < 3)
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-black
                                                {{ $i === 0 ? 'bg-amber-100 text-amber-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-50 text-orange-600') }}">
                                                {{ $i + 1 }}
                                            </span>
                                        @else
                                            <span class="text-sm font-bold text-gray-300">{{ $i + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-bold text-[#1e293b]">{{ $item->product }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-black text-blue-600">
                                            {{ number_format($item->movements) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-black text-[#1e293b]">
                                        {{ number_format($item->total_qty, 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center px-8 py-16 text-center">
                    <div class="mb-4 text-gray-200">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                    </div>
                    <p class="text-base font-bold text-gray-300">Sin movimientos en los últimos 30 días</p>
                    <p class="mt-1 text-sm text-gray-200">
                        {{ $location ? "No hubo movimientos en {$location->name}." : 'No hubo movimientos registrados.' }}
                    </p>
                </div>
            @endif
        </div>

        {{-- ── Derecha: Accesos rápidos ──────────────────────────────────────── --}}
        <div class="flex flex-col gap-3 lg:col-span-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">¿Qué quieres hacer?</p>

            {{-- ✅ NUEVA COMPRA — acción principal, blanca con hover rojo --}}
            <a href="{{ route('inventory.purchases.create') }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-[#1e293b] bg-white p-5 shadow-sm transition-all hover:border-[#e11d48] hover:bg-[#e11d48] hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-[#1e293b] transition-all group-hover:bg-white/20 group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 transition-all group-hover:text-white/70">Inventario</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight transition-all group-hover:text-white">Registrar compra</p>
                    <p class="text-xs font-medium text-gray-400 transition-all group-hover:text-white/70">Ingresa mercadería nueva</p>
                </div>
            </a>

            {{-- Ver stock actual --}}
            <a href="{{ route('reports.stock') }}{{ $locationId ? '?location_id='.$locationId : '' }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-emerald-400 hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 transition-all group-hover:bg-emerald-500 group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Reportes</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight">Ver stock actual</p>
                    <p class="text-xs font-medium text-gray-400">Qué hay en los depósitos</p>
                </div>
            </a>

            {{-- Nuevo traslado --}}
            <a href="{{ route('inventory.transfers.create') }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-blue-400 hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition-all group-hover:bg-blue-500 group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Inventario</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight">Registrar traslado</p>
                    <p class="text-xs font-medium text-gray-400">Mover entre depósitos</p>
                </div>
            </a>

            {{-- Historial de compras --}}
            <a href="{{ route('inventory.purchases.index') }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-violet-400 hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 transition-all group-hover:bg-violet-500 group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Inventario</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight">Historial de compras</p>
                    <p class="text-xs font-medium text-gray-400">Compras registradas</p>
                </div>
            </a>

            {{-- Vencimientos --}}
            <a href="{{ route('reports.expirations') }}{{ $locationId ? '?location_id='.$locationId : '' }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-amber-400 hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 transition-all group-hover:bg-amber-400 group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Alertas</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight">Vencimientos</p>
                    <p class="text-xs font-medium text-gray-400">Productos por vencer</p>
                </div>
            </a>

            {{-- Resumen general --}}
            <a href="{{ route('reports.index') }}"
                class="group flex items-center gap-4 rounded-[1.75rem] border-2 border-gray-100 bg-white p-5 shadow-sm transition-all hover:border-gray-400 hover:shadow-md active:scale-95">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-50 text-gray-500 transition-all group-hover:bg-[#1e293b] group-hover:text-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Reportes</p>
                    <p class="text-lg font-black text-[#1e293b] leading-tight">Resumen general</p>
                    <p class="text-xs font-medium text-gray-400">Estadísticas del sistema</p>
                </div>
            </a>

        </div>
    </div>

@endsection

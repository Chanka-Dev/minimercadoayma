@extends('layouts.app')

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $fechaHoy = \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
@endphp

{{-- ── Encabezado ──────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-black text-[#1e293b] dark:text-white">
            {{ $greeting }}, {{ auth()->user()->name }}
        </h1>
        <p class="mt-0.5 text-sm font-medium capitalize text-gray-400">{{ $fechaHoy }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('inventory.purchases.create') }}"
            class="flex items-center gap-2 rounded-xl bg-[#1e293b] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#334155]">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva compra
        </a>
        <a href="{{ route('inventory.transfers.create') }}"
            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Nuevo traslado
        </a>
    </div>
</div>

{{-- ── Alerta crítica: lotes vencidos ──────────────────────────────────────── --}}
@if($expiredCount > 0)
    <div class="mb-6 flex items-start gap-4 rounded-2xl border border-red-200 bg-red-50 px-6 py-5 dark:border-red-900/40 dark:bg-red-950/30">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#e11d48]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="flex-1">
            <p class="font-bold text-[#e11d48]">
                Alerta crítica: {{ $expiredCount }} {{ Str::plural('lote', $expiredCount) }} vencido{{ $expiredCount !== 1 ? 's' : '' }} con stock disponible
            </p>
            <p class="mt-0.5 text-sm text-red-500">Estos productos requieren atención inmediata.</p>
        </div>
        <a href="{{ route('reports.expirations', ['days' => 0]) }}"
            class="shrink-0 rounded-xl bg-[#e11d48] px-5 py-2 text-sm font-bold text-white transition-all hover:bg-[#be123c]">
            Ver vencidos
        </a>
    </div>
@endif

{{-- ── KPIs ─────────────────────────────────────────────────────────────────── --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

    {{-- Compras hoy --}}
    <div class="relative overflow-hidden rounded-2xl bg-[#1e293b] p-6 text-white shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-300">Compras hoy</p>
                <p class="mt-2 font-mono text-4xl font-black">{{ $purchasesToday }}</p>
                <p class="mt-1 text-xs text-slate-300">Bs. {{ number_format($purchasesTodayAmount, 2) }} en total</p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                </svg>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-300">{{ $purchasesWeek }} compras esta semana</p>
    </div>

    {{-- Productos activos --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Productos activos</p>
                <p class="mt-2 font-mono text-4xl font-black text-[#1e293b] dark:text-white">{{ $activeProducts }}</p>
                <p class="mt-1 text-xs text-gray-400">en catálogo</p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
        <a href="{{ route('products.index') }}" class="mt-3 block text-[11px] font-bold text-emerald-600 hover:underline">Ver productos →</a>
    </div>

    {{-- Movimientos hoy --}}
    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Movimientos hoy</p>
                <p class="mt-2 font-mono text-4xl font-black text-[#1e293b] dark:text-white">{{ $todayMovements }}</p>
                <p class="mt-1 text-xs text-gray-400">registros de inventario</p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
        </div>
        <a href="{{ route('reports.movements', ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]) }}"
            class="mt-3 block text-[11px] font-bold text-purple-600 hover:underline">Ver kardex →</a>
    </div>

    {{-- Lotes por vencer --}}
    <div class="rounded-2xl border p-6 shadow-sm {{ $expiringCount > 0 ? 'border-amber-100 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20' : 'border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800' }}">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest {{ $expiringCount > 0 ? 'text-amber-600' : 'text-gray-400' }}">Por vencer (30 días)</p>
                <p class="mt-2 font-mono text-4xl font-black {{ $expiringCount > 0 ? 'text-amber-700' : 'text-gray-500 dark:text-gray-300' }}">{{ $expiringCount }}</p>
                <p class="mt-1 text-xs {{ $expiringCount > 0 ? 'text-amber-500' : 'text-gray-400' }}">lotes próximos a vencer</p>
            </div>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $expiringCount > 0 ? 'bg-amber-100' : 'bg-gray-100 dark:bg-gray-700' }}">
                <svg class="h-6 w-6 {{ $expiringCount > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <a href="{{ route('reports.expirations') }}" class="mt-3 block text-[11px] font-bold {{ $expiringCount > 0 ? 'text-amber-600' : 'text-gray-400' }} hover:underline">Ver vencimientos →</a>
    </div>
</div>

{{-- ── Últimas compras + Accesos rápidos ───────────────────────────────────── --}}
<div class="grid gap-4 lg:grid-cols-3">

    {{-- Últimas compras --}}
    <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <h2 class="font-bold text-[#1e293b] dark:text-white">Últimas compras</h2>
            <a href="{{ route('inventory.purchases.index') }}" class="text-xs font-bold text-[#1e293b] hover:underline dark:text-gray-300">Ver todas →</a>
        </div>

        @if($recentPurchases->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 py-14 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-7 w-7 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-400">No hay compras registradas aún</p>
                <a href="{{ route('inventory.purchases.create') }}"
                    class="rounded-xl bg-[#1e293b] px-5 py-2 text-sm font-bold text-white hover:bg-[#334155]">
                    Registrar compra
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 dark:border-gray-700/50">
                            <th class="px-6 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-gray-400">#</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-gray-400">Ubicación</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-gray-400">Referencia</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest text-gray-400">Fecha</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        @foreach($recentPurchases as $purchase)
                            <tr class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-3.5">
                                    <span class="font-mono text-xs font-bold text-[#1e293b] dark:text-white">
                                        #REC-{{ str_pad($purchase->id, 6, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($purchase->originLocation)
                                        <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $purchase->originLocation->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400">
                                    {{ $purchase->reference_doc ?? '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-xs text-gray-400">
                                    {{ $purchase->created_at->format('d/m H:i') }}
                                    @if($purchase->created_at->isToday())
                                        <span class="ml-1 inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400 align-middle"></span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <a href="{{ route('inventory.purchases.show', $purchase->id) }}"
                                        class="text-xs font-bold text-blue-500 hover:underline">Ver →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Accesos rápidos --}}
    <div class="flex flex-col gap-3">
        <h2 class="font-bold text-[#1e293b] dark:text-white">Accesos rápidos</h2>

        <a href="{{ route('inventory.purchases.create') }}"
            class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-[#1e293b] dark:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-[#1e293b] dark:text-white">Nueva Compra</p>
                <p class="text-xs text-gray-400">Registrar ingreso de mercadería</p>
            </div>
        </a>

        <a href="{{ route('inventory.transfers.create') }}"
            class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-blue-200 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-[#1e293b] dark:text-white">Nuevo Traslado</p>
                <p class="text-xs text-gray-400">Mover stock entre sucursales</p>
            </div>
        </a>

        <a href="{{ route('reports.stock') }}"
            class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-emerald-200 hover:bg-emerald-50/50 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-[#1e293b] dark:text-white">Stock Actual</p>
                <p class="text-xs text-gray-400">Valorización por ubicación</p>
            </div>
        </a>

        <a href="{{ route('reports.expirations') }}"
            class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-amber-200 hover:bg-amber-50/50 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-[#1e293b] dark:text-white">Vencimientos</p>
                <p class="text-xs text-gray-400">Alertas de lotes próximos</p>
            </div>
        </a>

        <a href="{{ route('products.create') }}"
            class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:border-red-200 hover:bg-red-50/50 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-[#e11d48] dark:bg-red-900/40">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-[#1e293b] dark:text-white">Nuevo Producto</p>
                <p class="text-xs text-gray-400">Agregar al catálogo</p>
            </div>
        </a>
    </div>
</div>
@endsection

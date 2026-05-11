<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Location;
use App\Models\StockCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $locations  = Location::orderBy('name')->get();
        $locationId = $request->input('location_id') ? (int) $request->input('location_id') : null;
        $location   = $locationId ? $locations->firstWhere('id', $locationId) : null;

        $today = now()->toDateString();
        $in30  = now()->addDays(30)->toDateString();

        // ── Alertas de vencimiento (general o por depósito) ───────────────────
        $expiringQuery = DB::table('batches as b')
            ->join('stock_cache as sc', 'sc.batch_id', '=', 'b.id')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->join('locations as l', 'l.id', '=', 'sc.location_id')
            ->where('sc.quantity', '>', 0)
            ->whereNotNull('b.expiration_date')
            ->where('b.expiration_date', '>=', $today)
            ->where('b.expiration_date', '<=', $in30)
            ->when($locationId, fn ($q) => $q->where('sc.location_id', $locationId))
            ->select('p.name as product', 'b.expiration_date', 'l.name as location', 'sc.quantity', 'b.batch_code')
            ->orderBy('b.expiration_date')
            ->limit(8)
            ->get();

        $expiredCount = DB::table('batches as b')
            ->join('stock_cache as sc', 'sc.batch_id', '=', 'b.id')
            ->where('sc.quantity', '>', 0)
            ->whereNotNull('b.expiration_date')
            ->where('b.expiration_date', '<', $today)
            ->when($locationId, fn ($q) => $q->where('sc.location_id', $locationId))
            ->count();

        // ── Productos más movidos ─────────────────────────────────────────────
        // Cuenta cuántas veces aparece cada producto en movimientos de los últimos 30 días
        $topMovedProducts = DB::table('inventory_movements as im')
            ->join('products as p', 'p.id', '=', 'im.product_id')
            ->when($locationId, fn ($q) => $q->where(function ($q2) use ($locationId) {
                $q2->where('im.from_location_id', $locationId)
                   ->orWhere('im.to_location_id', $locationId);
            }))
            ->whereDate('im.created_at', '>=', now()->subDays(30))
            ->groupBy('im.product_id', 'p.name')
            ->selectRaw('im.product_id, p.name as product, COUNT(*) as movements, SUM(im.quantity) as total_qty')
            ->orderByDesc('movements')
            ->limit(10)
            ->get();

        // ── Stock actual en el depósito seleccionado ──────────────────────────
        $stockSummary = null;
        if ($locationId) {
            $stockSummary = DB::table('stock_cache as sc')
                ->join('products as p', 'p.id', '=', 'sc.product_id')
                ->where('sc.location_id', $locationId)
                ->where('sc.quantity', '>', 0)
                ->selectRaw('COUNT(DISTINCT sc.product_id) as product_count, SUM(sc.quantity) as total_units')
                ->first();
        }

        return view('dashboard', compact(
            'locations',
            'locationId',
            'location',
            'expiredCount',
            'topMovedProducts',
            'stockSummary'
        ))->with('expiringProducts', $expiringQuery);
    }
}

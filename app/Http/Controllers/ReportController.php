<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Enums\RoleName;
use App\Models\Batch;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\MovementGroup;
use App\Models\Product;
use App\Models\StockCache;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ── Helper: does the current user see cost columns? ───────────────────────
    private function canSeeCosts(): bool
    {
        $role = auth()->user()?->role?->name;
        return in_array($role, [RoleName::Master, RoleName::Admin, RoleName::WarehouseManager], true);
    }

    // =========================================================================
    // 1. DASHBOARD  GET /reports
    // =========================================================================
    public function dashboard()
    {
        $today = now()->toDateString();
        $in30  = now()->addDays(30)->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        // ── Alertas de vencimiento ────────────────────────────────────────────
        $expiringCount = DB::table('batches as b')
            ->join('stock_cache as sc', 'sc.batch_id', '=', 'b.id')
            ->where('sc.quantity', '>', 0)
            ->whereDate('b.expiration_date', '<=', $in30)
            ->whereDate('b.expiration_date', '>=', $today)
            ->whereNotNull('b.expiration_date')
            ->count();

        $expiredCount = DB::table('batches as b')
            ->join('stock_cache as sc', 'sc.batch_id', '=', 'b.id')
            ->where('sc.quantity', '>', 0)
            ->whereDate('b.expiration_date', '<', $today)
            ->whereNotNull('b.expiration_date')
            ->count();

        // ── Catálogo ──────────────────────────────────────────────────────────
        $activeProducts = Product::where('active', true)->count();

        // ── Movimientos de hoy ────────────────────────────────────────────────
        $todayMovements = InventoryMovement::whereDate('created_at', $today)->count();

        // ── Compras de hoy ────────────────────────────────────────────────────
        $purchasesToday = MovementGroup::where('type', MovementType::Purchase)
            ->whereDate('created_at', $today)
            ->count();

        $purchasesTodayAmount = DB::table('movement_groups as mg')
            ->join('inventory_movements as im', 'im.group_id', '=', 'mg.id')
            ->where('mg.type', MovementType::Purchase->value)
            ->whereDate('mg.created_at', $today)
            ->whereNotNull('im.unit_cost')
            ->selectRaw('SUM(im.quantity * im.unit_cost) as total')
            ->value('total') ?? 0;

        $purchasesWeek = MovementGroup::where('type', MovementType::Purchase)
            ->whereDate('created_at', '>=', $weekStart)
            ->count();

        // ── Últimas 8 compras ─────────────────────────────────────────────────
        $recentPurchases = MovementGroup::where('type', MovementType::Purchase)
            ->with(['originLocation:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('reports.dashboard', compact(
            'expiringCount',
            'expiredCount',
            'activeProducts',
            'todayMovements',
            'purchasesToday',
            'purchasesTodayAmount',
            'purchasesWeek',
            'recentPurchases'
        ));
    }

    // =========================================================================
    // 2. EXPIRATION ALERTS  GET /reports/expirations
    // =========================================================================
    public function expirations(Request $request)
    {
        $days       = max(1, (int) $request->input('days', 30));
        $locationId = $request->input('location_id');
        $export     = $request->boolean('export');

        $query = DB::table('batches as b')
            ->join('stock_cache as sc', 'sc.batch_id', '=', 'b.id')
            ->join('products as p', 'p.id', '=', 'b.product_id')
            ->join('locations as l', 'l.id', '=', 'sc.location_id')
            ->where('sc.quantity', '>', 0)
            ->whereNotNull('b.expiration_date')
            ->whereRaw("b.expiration_date <= CURRENT_DATE + INTERVAL '{$days} days'")
            ->when($locationId, fn ($q) => $q->where('sc.location_id', $locationId))
            ->select(
                'b.id as batch_id',
                'b.batch_code',
                'b.expiration_date',
                'p.name as product',
                'l.name as location',
                'sc.quantity'
            )
            ->orderBy('b.expiration_date');

        if ($export) {
            $rows = $query->get();
            return $this->exportCsv(
                'vencimientos_' . now()->format('Ymd') . '.csv',
                ['Lote', 'Producto', 'Ubicación', 'Vencimiento', 'Stock'],
                $rows->map(fn ($r) => [
                    $r->batch_code,
                    $r->product,
                    $r->location,
                    $r->expiration_date,
                    $r->quantity,
                ])->toArray()
            );
        }

        $batches   = $query->get();
        $locations = Location::where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('reports.expirations', compact('batches', 'locations', 'days', 'locationId'));
    }

    // =========================================================================
    // 3. STOCK VALUATION  GET /reports/stock
    // =========================================================================
    public function stock(Request $request)
    {
        $locationId = $request->input('location_id');
        $categoryId = $request->input('category_id');
        $productId  = $request->input('product_id');
        $export     = $request->boolean('export');
        $canCost    = $this->canSeeCosts();

        // Per-batch stock query with supplier, purchase date, expiration date, per-batch unit cost
        $query = DB::table('stock_cache as sc')
            ->join('products as p', 'p.id', '=', 'sc.product_id')
            ->join('locations as l', 'l.id', '=', 'sc.location_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('batches as b', 'b.id', '=', 'sc.batch_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
            ->where('sc.quantity', '>', 0)
            ->when($locationId, fn ($q) => $q->where('sc.location_id', $locationId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))
            ->when($productId,  fn ($q) => $q->where('sc.product_id', $productId))
            ->select(
                'p.id as product_id',
                'p.name as product',
                'p.unit_of_measure',
                'c.name as category',
                'l.id as location_id',
                'l.name as location',
                'sc.batch_id',
                'b.batch_code',
                'b.expiration_date',
                's.name as supplier',
                'sc.quantity',
                DB::raw("(
                    SELECT im.unit_cost
                    FROM inventory_movements im
                    JOIN movement_groups mg ON mg.id = im.group_id
                    WHERE mg.type = 'purchase'
                      AND im.product_id = sc.product_id
                      AND (sc.batch_id IS NULL OR im.batch_id = sc.batch_id)
                      AND im.unit_cost IS NOT NULL
                    ORDER BY im.id DESC
                    LIMIT 1
                ) as unit_cost"),
                DB::raw("(
                    SELECT mg.created_at
                    FROM inventory_movements im
                    JOIN movement_groups mg ON mg.id = im.group_id
                    WHERE mg.type = 'purchase'
                      AND im.product_id = sc.product_id
                      AND (sc.batch_id IS NULL OR im.batch_id = sc.batch_id)
                    ORDER BY mg.created_at ASC
                    LIMIT 1
                ) as purchase_date")
            )
            ->orderBy('l.name')
            ->orderBy('p.name')
            ->orderByRaw('b.expiration_date ASC NULLS LAST');

        if ($export) {
            $rows    = $query->get();
            $headers = ['Producto', 'Categoría', 'Ubicación', 'Lote', 'Proveedor', 'F. Compra', 'Vencimiento', 'Cantidad', 'Unidad'];
            if ($canCost) {
                $headers[] = 'Costo Unit.';
                $headers[] = 'Valor Total';
            }
            return $this->exportCsv(
                'stock_' . now()->format('Ymd') . '.csv',
                $headers,
                $rows->map(function ($r) use ($canCost) {
                    $row = [
                        $r->product,
                        $r->category  ?? '—',
                        $r->location,
                        $r->batch_code ?? '—',
                        $r->supplier   ?? '—',
                        $r->purchase_date    ? \Carbon\Carbon::parse($r->purchase_date)->format('d/m/Y')    : '—',
                        $r->expiration_date  ? \Carbon\Carbon::parse($r->expiration_date)->format('d/m/Y') : '—',
                        $r->quantity,
                        $r->unit_of_measure ?? '',
                    ];
                    if ($canCost) {
                        $row[] = number_format($r->unit_cost ?? 0, 2);
                        $row[] = number_format(($r->quantity ?? 0) * ($r->unit_cost ?? 0), 2);
                    }
                    return $row;
                })->toArray()
            );
        }

        $rows       = $query->paginate(50)->withQueryString();
        $locations  = Location::where('active', true)->orderBy('name')->get(['id', 'name']);
        $categories = Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $products   = Product::with('barcodes:product_id,barcode')
                        ->where('active', true)
                        ->orderBy('name')
                        ->get(['id', 'name']);

        // Total quantity and (if canCost) total value using per-batch unit cost
        $totalsBase = DB::table('stock_cache as sc')
            ->join('products as p', 'p.id', '=', 'sc.product_id')
            ->where('sc.quantity', '>', 0)
            ->when($locationId, fn ($q) => $q->where('sc.location_id', $locationId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))
            ->when($productId,  fn ($q) => $q->where('sc.product_id', $productId));

        if ($canCost) {
            $totals = (clone $totalsBase)->selectRaw("
                SUM(sc.quantity) as total_qty,
                SUM(sc.quantity * COALESCE((
                    SELECT im.unit_cost
                    FROM inventory_movements im
                    JOIN movement_groups mg ON mg.id = im.group_id
                    WHERE mg.type = 'purchase'
                      AND im.product_id = sc.product_id
                      AND (sc.batch_id IS NULL OR im.batch_id = sc.batch_id)
                      AND im.unit_cost IS NOT NULL
                    ORDER BY im.id DESC
                    LIMIT 1
                ), 0)) as total_value
            ")->first();
        } else {
            $totals = (clone $totalsBase)->selectRaw("SUM(sc.quantity) as total_qty")->first();
        }

        return view('reports.stock', compact(
            'rows', 'locations', 'categories', 'products',
            'locationId', 'categoryId', 'productId', 'canCost', 'totals'
        ));
    }

    // =========================================================================
    // 4. MOVEMENTS KARDEX  GET /reports/movements
    // =========================================================================
    public function movements(Request $request)
    {
        $productId  = $request->input('product_id');
        $locationId = $request->input('location_id');
        $type       = $request->input('type');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $export     = $request->boolean('export');
        $canCost    = $this->canSeeCosts();

        $query = InventoryMovement::with([
            'product:id,name',
            'batch:id,batch_code',
            'fromLocation:id,name',
            'toLocation:id,name',
            'group:id,type,reference_doc,user_id',
            'group.user:id,name',
        ])
            ->when($productId,  fn ($q) => $q->where('product_id', $productId))
            ->when($locationId, fn ($q) => $q->where(function ($q2) use ($locationId) {
                $q2->where('from_location_id', $locationId)
                   ->orWhere('to_location_id', $locationId);
            }))
            ->when($type, fn ($q) => $q->whereHas('group', fn ($q2) => $q2->where('type', $type)))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->select('inventory_movements.*');

        if ($export) {
            $rows    = $query->get();
            $headers = ['Fecha', 'Tipo', 'Producto', 'Lote', 'Origen', 'Destino', 'Cantidad', 'Usuario', 'Referencia'];
            if ($canCost) {
                $headers[] = 'Costo Unit.';
            }
            return $this->exportCsv(
                'movimientos_' . now()->format('Ymd') . '.csv',
                $headers,
                $rows->map(function ($m) use ($canCost) {
                    $row = [
                        $m->created_at->format('d/m/Y H:i'),
                        $m->group?->type?->value ?? '',
                        $m->product?->name ?? '',
                        $m->batch?->batch_code ?? '',
                        $m->fromLocation?->name ?? '',
                        $m->toLocation?->name ?? '',
                        $m->quantity,
                        $m->group?->user?->name ?? '',
                        $m->group?->reference_doc ?? '',
                    ];
                    if ($canCost) {
                        $row[] = number_format($m->unit_cost ?? 0, 2);
                    }
                    return $row;
                })->toArray()
            );
        }

        $movements  = $query->paginate(50)->withQueryString();
        $locations  = Location::where('active', true)->orderBy('name')->get(['id', 'name']);
        $products   = Product::where('active', true)->orderBy('name')->get(['id', 'name']);
        $types      = MovementType::cases();

        return view('reports.movements', compact(
            'movements', 'locations', 'products', 'types',
            'productId', 'locationId', 'type', 'dateFrom', 'dateTo', 'canCost'
        ));
    }

    // =========================================================================
    // Private: CSV streaming helper
    // =========================================================================
    private function exportCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

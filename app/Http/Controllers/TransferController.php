<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Requests\StoreTransferRequest;
use App\Models\Batch;
use App\Models\Location;
use App\Models\MovementGroup;
use App\Models\Product;
use App\Models\StockCache;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $locations = Location::orderBy('name')->get(['id', 'name']);

        $transfers = MovementGroup::where('type', MovementType::Transfer)
            ->with([
                'movements.product',
                'movements.fromLocation',
                'movements.toLocation',
                'user',
            ])
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->from_location_id, fn ($q, $v) =>
                $q->whereHas('movements', fn ($q) => $q->where('from_location_id', $v))
            )
            ->when($request->to_location_id, fn ($q, $v) =>
                $q->whereHas('movements', fn ($q) => $q->where('to_location_id', $v))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('transfers.index', compact('transfers', 'locations'));
    }

    public function create()
    {
        $locations = Location::where('active', true)->orderBy('name')->get(['id', 'name', 'type']);

        $products = Product::with('barcodes:product_id,barcode')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'use_batches']);

        return view('transfers.create', compact('locations', 'products'));
    }

    public function store(StoreTransferRequest $request)
    {
        $validated = $request->validated();

        $toRaw = $validated['to_location_id'] ?? null;
        $destLocationId = ($toRaw === 'third_party' || empty($toRaw)) ? null : (int) $toRaw;

        // Extra validation: if not third_party, must be a valid location different from origin
        if ($destLocationId !== null) {
            if ($destLocationId === (int) $validated['from_location_id']) {
                return back()->withInput()->withErrors(['to_location_id' => 'El destino no puede ser igual al origen.']);
            }
            if (!\App\Models\Location::where('id', $destLocationId)->exists()) {
                return back()->withInput()->withErrors(['to_location_id' => 'Ubicación de destino no válida.']);
            }
        }

        $data = [
            'from_location_id' => (int) $validated['from_location_id'],
            'origin_location_id' => (int) $validated['from_location_id'],
            'dest_location_id' => $destLocationId,
            'user_id'          => auth()->id(),
            'notes'            => $validated['notes'] ?? null,
            'customer_name'    => $validated['customer_name'] ?? null,
            'customer_phone'   => $validated['customer_phone'] ?? null,
            'lines'            => array_map(function ($line) {
                return [
                    'product_id' => (int) $line['product_id'],
                    'batch_id'   => isset($line['batch_id']) && $line['batch_id'] !== '' ? (int) $line['batch_id'] : null,
                    'quantity'   => (float) $line['quantity'],
                ];
            }, $validated['lines']),
        ];

        try {
            $group = $this->inventory->registerTransfer($data);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Error al registrar el traslado: ' . $e->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $group->id)
            ->with('success', 'Traslado registrado correctamente.');
    }

    public function show(int $id)
    {
        $group = MovementGroup::where('type', MovementType::Transfer)
            ->with([
                'movements.product',
                'movements.batch',
                'movements.fromLocation',
                'movements.toLocation',
                'user',
            ])
            ->findOrFail($id);

        return view('transfers.show', compact('group'));
    }

    public function edit(int $id)
    {
        $group = MovementGroup::where('type', MovementType::Transfer)
            ->with(['movements.fromLocation', 'movements.toLocation'])
            ->findOrFail($id);

        return view('transfers.edit', compact('group'));
    }

    public function update(Request $request, int $id)
    {
        $group = MovementGroup::where('type', MovementType::Transfer)
            ->findOrFail($id);

        $validated = $request->validate([
            'customer_name'  => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'notes'          => ['nullable', 'string'],
        ]);

        $group->update($validated);

        return redirect()
            ->route('inventory.transfers.show', $group->id)
            ->with('success', 'Datos del traslado actualizados correctamente.');
    }

    /**
     * AJAX: return products that have stock > 0 at a given location.
     * GET /inventory/transfers/products-by-location?location_id=X
     */
    public function productsByLocation(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $locationId = (int) $request->location_id;

        $products = Product::with('barcodes:product_id,barcode')
            ->whereHas('stockCache', fn ($q) =>
                $q->where('location_id', $locationId)->where('quantity', '>', 0)
            )
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'use_batches'])
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'use_batches' => (bool) $p->use_batches,
                'barcodes'    => $p->barcodes->pluck('barcode')->values()->toArray(),
            ]);

        return response()->json(['products' => $products]);
    }

    /**
     * AJAX: return available stock for a product (+ optional batch) at a location.
     * GET /inventory/transfers/stock?product_id=&location_id=&batch_id=
     */
    public function getAvailableStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'batch_id'    => ['nullable', 'integer', 'exists:batches,id'],
        ]);

        $productId  = (int) $request->product_id;
        $locationId = (int) $request->location_id;
        $batchId    = $request->batch_id ? (int) $request->batch_id : null;

        // If requesting a specific batch, return that row.
        // If no batch, sum all rows (covers both no-batch products and totals).
        if ($batchId !== null) {
            $available = StockCache::where('location_id', $locationId)
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->value('quantity') ?? 0;
        } else {
            $available = StockCache::where('location_id', $locationId)
                ->where('product_id', $productId)
                ->sum('quantity');
        }

        // Also return batches with stock > 0 for this product+location (for the selector)
        $batches = Batch::whereHas('stockCache', fn ($q) =>
            $q->where('location_id', $locationId)
              ->where('product_id', $productId)
              ->where('quantity', '>', 0)
        )
        ->with(['stockCache' => fn ($q) =>
            $q->where('location_id', $locationId)
              ->where('product_id', $productId)
              ->where('quantity', '>', 0)
        ])
        ->get()
        ->map(fn ($b) => [
            'id'              => $b->id,
            'batch_code'      => $b->batch_code,
            'expiration_date' => $b->expiration_date?->format('d/m/Y'),
            'available'       => (float) ($b->stockCache->first()?->quantity ?? 0),
        ]);

        return response()->json([
            'available' => (float) $available,
            'batches'   => $batches,
        ]);
    }

    public function cancel(int $id)
    {
        $group = MovementGroup::where('type', MovementType::Transfer)->findOrFail($id);

        if ($group->isCancelled()) {
            return redirect()
                ->route('inventory.transfers.show', $id)
                ->with('error', 'Este traslado ya fue cancelado.');
        }

        try {
            $this->inventory->cancelTransfer($group);
        } catch (\Throwable $e) {
            return redirect()
                ->route('inventory.transfers.show', $id)
                ->with('error', 'Error al cancelar el traslado: ' . $e->getMessage());
        }

        return redirect()
            ->route('inventory.transfers.show', $id)
            ->with('success', 'Traslado cancelado y stock revertido correctamente.');
    }
}

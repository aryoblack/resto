<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::withCount('inventory')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $paginator = $query->paginate($request->integer('per_page', 10));

        return response()->json([
            'message' => 'Daftar supplier berhasil diambil.',
            'data' => $paginator->getCollection()->map(fn (Supplier $supplier) => $this->formatSupplier($supplier))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSupplier($request);

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'Supplier berhasil ditambahkan.',
            'data' => $this->formatSupplier($supplier->loadCount('inventory')),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'message' => 'Detail supplier berhasil diambil.',
            'data' => $this->formatSupplier($supplier->loadCount('inventory')),
        ]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $this->validateSupplier($request, $supplier);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier berhasil diperbarui.',
            'data' => $this->formatSupplier($supplier->fresh()->loadCount('inventory')),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->inventory()->exists()) {
            return response()->json([
                'message' => 'Supplier tidak dapat dihapus karena masih dipakai bahan baku.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier berhasil dihapus.',
        ]);
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        $supplierId = $supplier?->id ?? 'NULL';

        return $request->validate([
            'name' => "required|string|max:255|unique:suppliers,name,{$supplierId}",
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    private function formatSupplier(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'contact_person' => $supplier->contact_person,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'address' => $supplier->address,
            'notes' => $supplier->notes,
            'is_active' => $supplier->is_active,
            'inventory_count' => $supplier->inventory_count ?? $supplier->inventory()->count(),
            'created_at' => $supplier->created_at,
            'updated_at' => $supplier->updated_at,
        ];
    }
}

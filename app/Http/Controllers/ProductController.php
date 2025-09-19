<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // ✅ 1. Lister tous les produits
    public function index()
    {
        return response()->json(Product::with('category')->get());
    }

    // ✅ 2. Créer un produit
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'daily_price' => 'required|numeric',
            'replacement_cost' => 'nullable|numeric',
            'is_active' => 'boolean',
            'id_category' => 'nullable|exists:categories,id_category',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    // ✅ 3. Voir un produit
    public function show($id)
    {
        $product = Product::with(['category', 'bundles', 'inventories', 'reservations'])->findOrFail($id);
        return response()->json($product);
    }

    // ✅ 4. Modifier un produit
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'daily_price' => 'sometimes|required|numeric',
            'replacement_cost' => 'nullable|numeric',
            'is_active' => 'boolean',
            'id_category' => 'nullable|exists:categories,id_category',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    // Supprimer un produit
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}

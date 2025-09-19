<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Product;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    // Liste de tous les bundles
    public function index()
    {
        $bundles = Bundle::with('products')->get();
        return response()->json($bundles);
    }

    // Ajouter un bundle
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'daily_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'products' => 'nullable|array',
            'products.*.id_product' => 'required|exists:products,id_product',
            'products.*.quantity' => 'required|integer|min:1'
        ]);

        $bundle = Bundle::create($request->only('name', 'description', 'daily_price', 'is_active'));

        // Attacher les produits
        if ($request->has('products')) {
            foreach ($request->products as $p) {
                $bundle->products()->attach($p['id_product'], ['quantity' => $p['quantity']]);
            }
        }

        return response()->json([
            'message' => 'Bundle créé avec succès',
            'bundle' => $bundle->load('products')
        ], 201);
    }

    // Afficher un bundle spécifique
    public function show($id)
    {
        $bundle = Bundle::with('products')->findOrFail($id);
        return response()->json($bundle);
    }

    // Mettre à jour un bundle
    public function update(Request $request, $id)
    {
        $bundle = Bundle::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'daily_price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'boolean',
            'products' => 'nullable|array',
            'products.*.id_product' => 'required|exists:products,id_product',
            'products.*.quantity' => 'required|integer|min:1'
        ]);

        $bundle->update($request->only('name', 'description', 'daily_price', 'is_active'));

        if ($request->has('products')) {
            // Supprimer les anciens liens
            $bundle->products()->detach();
            // Attacher les nouveaux produits
            foreach ($request->products as $p) {
                $bundle->products()->attach($p['id_product'], ['quantity' => $p['quantity']]);
            }
        }

        return response()->json([
            'message' => 'Bundle mis à jour avec succès',
            'bundle' => $bundle->load('products')
        ]);
    }

    // Supprimer un bundle
    public function destroy($id)
    {
        $bundle = Bundle::findOrFail($id);
        $bundle->products()->detach(); // détacher les produits
        $bundle->delete();

        return response()->json(['message' => 'Bundle supprimé avec succès']);
    }
}

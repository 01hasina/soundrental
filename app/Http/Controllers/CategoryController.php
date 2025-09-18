<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // Afficher toutes les catégories
    public function index()
    {
        return response()->json(Category::all());
    }

    // Afficher une catégorie par son id
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Catégorie introuvable'], 404);
        }

        return response()->json($category);
    }

    // Créer une nouvelle catégorie
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'category' => $category
        ], 201);
    }

    // Mettre à jour une catégorie
    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Catégorie introuvable'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id . ',id_category',
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'category' => $category
        ]);
    }

    // Supprimer une catégorie
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Catégorie introuvable'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès']);
    }
}

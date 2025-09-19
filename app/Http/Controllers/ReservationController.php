<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Product;
use App\Models\Bundle;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;


class ReservationController extends Controller
{
    // Liste toutes les réservations
    // public function index()
    // {
    //     $reservations = Reservation::with(['user', 'products', 'bundles'])->get();
    //     return response()->json($reservations);
    // }

    // Créer une réservation
    public function store(Request $request)
    {
        $request->validate([
            'event_date' => 'required|date',
            'event_time' => 'required',
            'duration_hours' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'products' => 'nullable|array',
            'products.*.id_product' => 'required|integer|exists:products,id_product',
            'products.*.quantity' => 'required|integer|min:1',
            'bundles' => 'nullable|array',
            'bundles.*.id_bundle' => 'required|integer|exists:bundles,id_bundle',
            'bundles.*.quantity' => 'required|integer|min:1',
        ]);

        // Construire la table des quantités nécessaires par produit
        $requiredPerProduct = [];

        if ($request->filled('products')) {
            foreach ($request->input('products') as $p) {
                $pid = (int) $p['id_product'];
                $qty = (int) $p['quantity'];
                if ($qty <= 0) continue;
                if (!isset($requiredPerProduct[$pid])) $requiredPerProduct[$pid] = 0;
                $requiredPerProduct[$pid] += $qty;
            }
        }

        if ($request->filled('bundles')) {
            foreach ($request->input('bundles') as $b) {
                $bundle = Bundle::with('products')->find($b['id_bundle']);
                if (!$bundle) {
                    return response()->json(['success' => false, 'message' => "Bundle id {$b['id_bundle']} introuvable."], 404);
                }
                $bundleQty = (int) $b['quantity'];
                if ($bundleQty <= 0) continue;
                foreach ($bundle->products as $bp) {
                    $prodId = (int) $bp->id_product;
                    $perBundleQty = (int) $bp->pivot->quantity; // quantité de ce produit par bundle
                    $needed = $perBundleQty * $bundleQty;
                    if (!isset($requiredPerProduct[$prodId])) $requiredPerProduct[$prodId] = 0;
                    $requiredPerProduct[$prodId] += $needed;
                }
            }
        }

        DB::beginTransaction();
        try {
            // Vérification atomique de la disponibilité : lockForUpdate sur les lignes d'inventaire pertinentes
            foreach ($requiredPerProduct as $prodId => $neededQty) {
                // compter les inventaires disponibles et verrouiller les lignes
                $availableCount = DB::table('inventory')
                    ->where('id_product', $prodId)
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->count();

                if ($availableCount < $neededQty) {
                    DB::rollBack();
                    $product = Product::find($prodId);
                    $name = $product ? $product->name : "ID $prodId";
                    return response()->json([
                        'success' => false,
                        'message' => "Indisponible : le produit '{$name}' nécessite {$neededQty} exemplaire(s) mais seulement {$availableCount} disponible(s)."
                    ], 409);
                }
            }

            // Tout est disponible -> créer la réservation
            $user = $request->user();
            $reservation = Reservation::create([
                'id_user' => $user->id_user,
                'event_date' => $request->event_date,
                'event_time' => $request->event_time,
                'duration_hours' => $request->duration_hours,
                'location' => $request->location,
                'status' => 'pending',
            ]);

            // Attacher produits directs (pivot reservation_products)
            if ($request->filled('products')) {
                foreach ($request->input('products') as $p) {
                    $reservation->products()->attach($p['id_product'], ['quantity' => $p['quantity']]);
                }
            }

            // Attacher bundles (pivot reservation_bundles)
            if ($request->filled('bundles')) {
                foreach ($request->input('bundles') as $b) {
                    $reservation->bundles()->attach($b['id_bundle'], ['quantity' => $b['quantity']]);
                }
            }

            // Maintenant marquer les inventaires réservés : utiliser requiredPerProduct pour ne faire la mise à jour qu'une seule fois par produit
            foreach ($requiredPerProduct as $prodId => $neededQty) {
                // on récupère les N premières lignes disponibles verrouillées et on les met à false
                $inventories = DB::table('inventory')
                    ->where('id_product', $prodId)
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->limit($neededQty)
                    ->get();

                // Sécurité : si, pour une raison quelconque, on a moins de lignes (improbable ici), rollback
                if ($inventories->count() < $neededQty) {
                    DB::rollBack();
                    $product = Product::find($prodId);
                    $name = $product ? $product->name : "ID $prodId";
                    return response()->json([
                        'success' => false,
                        'message' => "Erreur de réservation : inventaire insuffisant pour '{$name}' (attendu {$neededQty}, trouvé {$inventories->count()})."
                    ], 500);
                }

                // Mettre à jour chaque ligne d'inventaire
                foreach ($inventories as $inv) {
                    DB::table('inventory')->where('id_inventory', $inv->id_inventory)->update([
                        'is_available' => false
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès.',
                'reservation' => $reservation->load('products', 'bundles', 'user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Afficher une réservation spécifique
    public function show($id)
    {
        $reservation = Reservation::with(['user', 'products', 'bundles'])->findOrFail($id);
        return response()->json($reservation);
    }

    // Mettre à jour une réservation
    public function update(Request $request, $id)
    {
        $reservation = Reservation::with('products', 'bundles')->findOrFail($id);

        $request->validate([
            'event_date' => 'nullable|date',
            'event_time' => 'nullable',
            'duration_hours' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'products' => 'nullable|array',
            'products.*.id_product' => 'required|integer|exists:products,id_product',
            'products.*.quantity' => 'required|integer|min:1',
            'bundles' => 'nullable|array',
            'bundles.*.id_bundle' => 'required|integer|exists:bundles,id_bundle',
            'bundles.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Rendre disponible les inventaires précédemment réservés
            foreach ($reservation->products as $prod) {
                $inventories = Inventory::where('id_product', $prod->id_product)
                    ->where('is_available', false)
                    ->take($prod->pivot->quantity)
                    ->get();
                foreach ($inventories as $inv) {
                    $inv->is_available = true;
                    $inv->save();
                }
            }

            foreach ($reservation->bundles as $bundle) {
                foreach ($bundle->products as $bp) {
                    $requiredQty = $bp->pivot->quantity * $bundle->pivot->quantity;
                    $inventories = Inventory::where('id_product', $bp->id_product)
                        ->where('is_available', false)
                        ->take($requiredQty)
                        ->get();
                    foreach ($inventories as $inv) {
                        $inv->is_available = true;
                        $inv->save();
                    }
                }
            }

            // Vérification disponibilité des nouveaux produits
            if ($request->products) {
                foreach ($request->products as $p) {
                    $availableCount = Inventory::where('id_product', $p['id_product'])
                        ->where('is_available', true)
                        ->count();
                    if ($availableCount < $p['quantity']) {
                        return response()->json([
                            'message' => "Le produit ID {$p['id_product']} n'a pas assez de stock disponible."
                        ], 400);
                    }
                }
            }

            // Vérification disponibilité des nouveaux bundles
            if ($request->bundles) {
                foreach ($request->bundles as $b) {
                    $bundle = Bundle::findOrFail($b['id_bundle']);
                    foreach ($bundle->products as $bp) {
                        $requiredQty = $bp->pivot->quantity * $b['quantity'];
                        $availableCount = Inventory::where('id_product', $bp->id_product)
                            ->where('is_available', true)
                            ->count();
                        if ($availableCount < $requiredQty) {
                            return response()->json([
                                'message' => "Le produit '{$bp->name}' dans le bundle '{$bundle->name}' n'a pas assez de stock disponible."
                            ], 400);
                        }
                    }
                }
            }

            // Mise à jour des informations de réservation
            $reservation->update($request->only(['event_date', 'event_time', 'duration_hours', 'location']));

            // Mise à jour des produits
            $reservation->products()->detach();
            if ($request->products) {
                foreach ($request->products as $p) {
                    $reservation->products()->attach($p['id_product'], ['quantity' => $p['quantity']]);

                    $inventories = Inventory::where('id_product', $p['id_product'])
                        ->where('is_available', true)
                        ->take($p['quantity'])
                        ->get();
                    foreach ($inventories as $inv) {
                        $inv->is_available = false;
                        $inv->save();
                    }
                }
            }

            // Mise à jour des bundles
            $reservation->bundles()->detach();
            if ($request->bundles) {
                foreach ($request->bundles as $b) {
                    $bundle = Bundle::findOrFail($b['id_bundle']);
                    $reservation->bundles()->attach($b['id_bundle'], ['quantity' => $b['quantity']]);

                    foreach ($bundle->products as $bp) {
                        $requiredQty = $bp->pivot->quantity * $b['quantity'];
                        $inventories = Inventory::where('id_product', $bp->id_product)
                            ->where('is_available', true)
                            ->take($requiredQty)
                            ->get();
                        foreach ($inventories as $inv) {
                            $inv->is_available = false;
                            $inv->save();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Réservation mise à jour avec succès',
                'reservation' => $reservation->load('products', 'bundles')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une réservation
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->products()->detach();
        $reservation->bundles()->detach();
        $reservation->delete();

        return response()->json(['message' => 'Réservation supprimée']);
    }

    //annulation reservation
    public function cancel($id)
    {
        $reservation = Reservation::with('products', 'bundles')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Rendre disponibles les produits réservés
            foreach ($reservation->products as $prod) {
                $inventories = Inventory::where('id_product', $prod->id_product)
                    ->where('is_available', false)
                    ->take($prod->pivot->quantity)
                    ->get();

                foreach ($inventories as $inv) {
                    $inv->is_available = true;
                    $inv->save();
                }
            }

            // Rendre disponibles les produits dans les bundles réservés
            foreach ($reservation->bundles as $bundle) {
                foreach ($bundle->products as $bp) {
                    $requiredQty = $bp->pivot->quantity * $bundle->pivot->quantity;
                    $inventories = Inventory::where('id_product', $bp->id_product)
                        ->where('is_available', false)
                        ->take($requiredQty)
                        ->get();

                    foreach ($inventories as $inv) {
                        $inv->is_available = true;
                        $inv->save();
                    }
                }
            }

            // Mettre à jour le statut de la réservation
            $reservation->status = 'cancelled';
            $reservation->save();

            DB::commit();

            return response()->json([
                'message' => 'Réservation annulée avec succès',
                'reservation' => $reservation->load('products', 'bundles')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l’annulation de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

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
    public function index()
    {
        $reservations = Reservation::with(['user', 'products', 'bundles', 'payments', 'quotes', 'invoices'])->get();
        return response()->json($reservations);
    }

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

        // Calcul des quantités nécessaires par produit
        $requiredPerProduct = [];

        if ($request->filled('products')) {
            foreach ($request->input('products') as $p) {
                $pid = (int) $p['id_product'];
                $qty = (int) $p['quantity'];
                if ($qty <= 0) continue;
                $requiredPerProduct[$pid] = ($requiredPerProduct[$pid] ?? 0) + $qty;
            }
        }

        if ($request->filled('bundles')) {
            foreach ($request->input('bundles') as $b) {
                $bundle = Bundle::with('products')->findOrFail($b['id_bundle']);
                $bundleQty = (int) $b['quantity'];
                foreach ($bundle->products as $bp) {
                    $prodId = (int) $bp->id_product;
                    $requiredPerProduct[$prodId] = ($requiredPerProduct[$prodId] ?? 0) + ($bp->pivot->quantity * $bundleQty);
                }
            }
        }

        DB::beginTransaction();
        try {
            $reservedInventories = [];

            // Vérification disponibilité inventaire
            foreach ($requiredPerProduct as $prodId => $neededQty) {
                $inventories = Inventory::where('id_product', $prodId)
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->limit($neededQty)
                    ->get();

                if ($inventories->count() < $neededQty) {
                    DB::rollBack();
                    $product = Product::find($prodId);
                    $name = $product ? $product->name : "ID $prodId";
                    return response()->json([
                        'success' => false,
                        'message' => "Indisponible : le produit '{$name}' nécessite {$neededQty} exemplaire(s) mais seulement {$inventories->count()} disponible(s)."
                    ], 409);
                }

                $reservedInventories[$prodId] = $inventories;
            }

            // Créer la réservation
            $user = $request->user();
            $reservation = Reservation::create([
                'id_user' => $user->id_user,
                'event_date' => $request->event_date,
                'event_time' => $request->event_time,
                'duration_hours' => $request->duration_hours,
                'location' => $request->location,
                'status' => 'pending',
            ]);

            // Marquer les inventaires comme réservés
            foreach ($reservedInventories as $prodId => $inventories) {
                foreach ($inventories as $inv) {
                    DB::table('reservation_inventory')->insert([
                        'id_reservation' => $reservation->id_reservation,
                        'id_inventory' => $inv->id_inventory
                    ]);
                    $inv->is_available = false;
                    $inv->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès.',
                'reservation' => $reservation->load('user', 'products', 'bundles', 'payments', 'quotes', 'invoices')
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
        $reservation = Reservation::with(['user', 'products', 'bundles', 'payments', 'quotes', 'invoices'])->findOrFail($id);
        return response()->json($reservation);
    }

    // Mettre à jour une réservation
    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

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
            // Rendre disponibles les inventaires précédemment réservés
            $previousInventories = DB::table('reservation_inventory')
                ->where('id_reservation', $reservation->id_reservation)
                ->get();

            foreach ($previousInventories as $inv) {
                Inventory::where('id_inventory', $inv->id_inventory)->update(['is_available' => true]);
            }

            // Supprimer les anciennes réservations d'inventaire
            DB::table('reservation_inventory')->where('id_reservation', $reservation->id_reservation)->delete();

            // Recalcul des besoins par produit
            $requiredPerProduct = [];

            if ($request->filled('products')) {
                foreach ($request->input('products') as $p) {
                    $pid = (int) $p['id_product'];
                    $qty = (int) $p['quantity'];
                    $requiredPerProduct[$pid] = ($requiredPerProduct[$pid] ?? 0) + $qty;
                }
            }

            if ($request->filled('bundles')) {
                foreach ($request->input('bundles') as $b) {
                    $bundle = Bundle::with('products')->findOrFail($b['id_bundle']);
                    $bundleQty = (int) $b['quantity'];
                    foreach ($bundle->products as $bp) {
                        $prodId = (int) $bp->id_product;
                        $requiredPerProduct[$prodId] = ($requiredPerProduct[$prodId] ?? 0) + ($bp->pivot->quantity * $bundleQty);
                    }
                }
            }

            // Vérification inventaires disponibles
            $reservedInventories = [];
            foreach ($requiredPerProduct as $prodId => $neededQty) {
                $inventories = Inventory::where('id_product', $prodId)
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->limit($neededQty)
                    ->get();

                if ($inventories->count() < $neededQty) {
                    DB::rollBack();
                    $product = Product::find($prodId);
                    $name = $product ? $product->name : "ID $prodId";
                    return response()->json([
                        'success' => false,
                        'message' => "Indisponible : le produit '{$name}' nécessite {$neededQty} exemplaire(s) mais seulement {$inventories->count()} disponible(s)."
                    ], 409);
                }

                $reservedInventories[$prodId] = $inventories;
            }

            // Mise à jour de la réservation
            $reservation->update($request->only(['event_date', 'event_time', 'duration_hours', 'location', 'status']));

            // Réservation inventaires
            foreach ($reservedInventories as $prodId => $inventories) {
                foreach ($inventories as $inv) {
                    DB::table('reservation_inventory')->insert([
                        'id_reservation' => $reservation->id_reservation,
                        'id_inventory' => $inv->id_inventory
                    ]);
                    $inv->is_available = false;
                    $inv->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Réservation mise à jour avec succès.',
                'reservation' => $reservation->load('user', 'products', 'bundles', 'payments', 'quotes', 'invoices')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la réservation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Supprimer une réservation
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        // Rendre les inventaires disponibles
        foreach ($reservation->products as $prod) {
            $inventories = Inventory::where('id_product', $prod->id_product)->take($prod->pivot->quantity)->get();
            foreach ($inventories as $inv) {
                $inv->is_available = true;
                $inv->save();
            }
        }

        $reservation->products()->detach();
        $reservation->bundles()->detach();
        $reservation->delete();

        return response()->json(['message' => 'Réservation supprimée']);
    }

    // Annuler une réservation
    public function cancel($id)
    {
        $reservation = Reservation::with(['products', 'bundles'])->findOrFail($id);

        DB::beginTransaction();
        try {
            // Rendre disponibles les inventaires produits et bundles
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

            // Changer le statut
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

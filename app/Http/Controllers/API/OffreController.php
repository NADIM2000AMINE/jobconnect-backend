<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Offre;
use App\Models\Competence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OffreController extends Controller
{
    /**
     * Afficher toutes les offres avec pagination et filtres
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Offre::with(['entreprise.user', 'competences']);

        // Filtres
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('entreprise', function($q) use ($search) {
                      $q->where('nom_entreprise', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        if ($request->has('localisation') && !empty($request->localisation)) {
            $query->where('localisation', 'like', "%{$request->localisation}%");
        }

        if ($request->has('competences') && !empty($request->competences)) {
            $competences = explode(',', $request->competences);
            $query->whereHas('competences', function($q) use ($competences) {
                $q->whereIn('nom', $competences);
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $offres = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $offres->items(),
            'pagination' => [
                'current_page' => $offres->currentPage(),
                'last_page' => $offres->lastPage(),
                'per_page' => $offres->perPage(),
                'total' => $offres->total(),
                'from' => $offres->firstItem(),
                'to' => $offres->lastItem(),
            ],
            'offres' => $offres->items(), // Ajout d'une clé alternative
            'total' => $offres->total(),
            'current_page' => $offres->currentPage(),
            'last_page' => $offres->lastPage()
        ]);
    }

    /**
     * Afficher toutes les offres avec une structure simplifiée
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexSimple(Request $request)
    {
        $query = Offre::with(['entreprise.user', 'competences']);

        // Filtres
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('entreprise', function($q) use ($search) {
                      $q->where('nom_entreprise', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        if ($request->has('localisation') && !empty($request->localisation)) {
            $query->where('localisation', 'like', "%{$request->localisation}%");
        }

        if ($request->has('competences') && !empty($request->competences)) {
            $competences = explode(',', $request->competences);
            $query->whereHas('competences', function($q) use ($competences) {
                $q->whereIn('nom', $competences);
            });
        }

        $offres = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'offres' => $offres,
            'total' => $offres->count()
        ]);
    }

    /**
     * Afficher une offre spécifique
     *
     * @param  \App\Models\Offre  $offre
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Offre $offre)
    {
        $offre->load(['entreprise.user', 'competences', 'tests']);
        
        return response()->json([
            'success' => true,
            'data' => $offre
        ]);
    }

    /**
     * Créer une nouvelle offre
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:stage,emploi,alternance',
            'localisation' => 'required|string|max:255',
            'remuneration' => 'nullable|numeric',
            'date_debut' => 'required|date',
            'duree' => 'nullable|integer',
            'competences' => 'nullable|array',
            'competences.*' => 'exists:competences,id'
        ]);

        $offre = Offre::create([
            'entreprise_id' => $request->user()->entreprise->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'niveau_requis' => $request->niveau_requis,
            'competences_requises' => $request->competences_requises,
            'localisation' => $request->localisation,
            'remuneration' => $request->remuneration,
            'date_debut' => $request->date_debut,
            'duree' => $request->duree,
            'test_requis' => $request->test_requis ?? false,
            'statut' => 'active'
        ]);

        if ($request->has('competences')) {
            $offre->competences()->attach($request->competences);
        }

        $offre->load(['entreprise.user', 'competences']);

        return response()->json([
            'success' => true,
            'message' => 'Offre créée avec succès',
            'data' => $offre
        ], 201);
    }

    /**
     * Mettre à jour une offre
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Offre  $offre
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Offre $offre)
    {
        // Vérifier que l'utilisateur est propriétaire de l'offre
        if ($offre->entreprise_id !== $request->user()->entreprise->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:stage,emploi,alternance',
            'localisation' => 'sometimes|required|string|max:255',
            'remuneration' => 'nullable|numeric',
            'date_debut' => 'sometimes|required|date',
            'duree' => 'nullable|integer',
            'statut' => 'sometimes|required|in:active,inactive,cloturee'
        ]);

        $offre->update($request->only([
            'titre', 'description', 'type', 'niveau_requis', 'competences_requises',
            'localisation', 'remuneration', 'date_debut', 'duree', 'test_requis', 'statut'
        ]));

        if ($request->has('competences')) {
            $offre->competences()->sync($request->competences);
        }

        $offre->load(['entreprise.user', 'competences']);

        return response()->json([
            'success' => true,
            'message' => 'Offre mise à jour avec succès',
            'data' => $offre
        ]);
    }

    /**
     * Supprimer une offre
     *
     * @param  \App\Models\Offre  $offre
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Offre $offre)
    {
        // Vérifier que l'utilisateur est propriétaire de l'offre
        if ($offre->entreprise_id !== request()->user()->entreprise->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $offre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Offre supprimée avec succès'
        ]);
    }
} 
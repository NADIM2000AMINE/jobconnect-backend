<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends Controller
{
    /**
     * Afficher les candidatures de l'étudiant connecté
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function candidatures(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'etudiant') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $user->etudiant;
        
        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        // Récupérer les candidatures de l'étudiant
        $query = Candidature::with([
            'offre.entreprise.user',
            'offre.competences'
        ])->where('etudiant_id', $etudiant->id);

        // Filtres
        if ($request->has('statut') && !empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $candidatures = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $candidatures->items(),
            'pagination' => [
                'current_page' => $candidatures->currentPage(),
                'last_page' => $candidatures->lastPage(),
                'per_page' => $candidatures->perPage(),
                'total' => $candidatures->total(),
                'from' => $candidatures->firstItem(),
                'to' => $candidatures->lastItem(),
            ]
        ]);
    }

    /**
     * Créer une nouvelle candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postuler(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'etudiant') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $user->etudiant;
        
        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        $request->validate([
            'offre_id' => 'required|exists:offres,id',
            'lettre_motivation' => 'nullable|string'
        ]);

        // Vérifier si l'étudiant a déjà postulé à cette offre
        $candidatureExistante = Candidature::where('etudiant_id', $etudiant->id)
            ->where('offre_id', $request->offre_id)
            ->first();

        if ($candidatureExistante) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà postulé à cette offre'
            ], 400);
        }

        // Créer la candidature
        $candidature = Candidature::create([
            'etudiant_id' => $etudiant->id,
            'offre_id' => $request->offre_id,
            'lettre_motivation' => $request->lettre_motivation,
            'statut' => 'en_attente'
        ]);

        $candidature->load(['offre.entreprise.user', 'offre.competences']);

        return response()->json([
            'success' => true,
            'message' => 'Candidature envoyée avec succès',
            'data' => $candidature
        ], 201);
    }

    /**
     * Annuler une candidature
     *
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\JsonResponse
     */
    public function annulerCandidature(Candidature $candidature)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'etudiant') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Vérifier que la candidature appartient à cet étudiant
        if ($candidature->etudiant_id !== $user->etudiant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        // Vérifier que la candidature est encore en attente
        if ($candidature->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une candidature déjà traitée'
            ], 400);
        }

        $candidature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Candidature annulée avec succès'
        ]);
    }
} 
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Candidature;
use App\Models\Offre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntrepriseController extends Controller
{
    /**
     * Afficher les candidatures reçues par l'entreprise connectée
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function candidatures(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'entreprise') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $entreprise = $user->entreprise;
        
        if (!$entreprise) {
            return response()->json([
                'success' => false,
                'message' => 'Profil entreprise non trouvé'
            ], 404);
        }

        // Récupérer les offres de l'entreprise
        $offresIds = Offre::where('entreprise_id', $entreprise->id)->pluck('id');
        
        // Récupérer les candidatures pour ces offres
        $query = Candidature::with([
            'etudiant.user',
            'offre.entreprise.user',
            'offre.competences'
        ])->whereIn('offre_id', $offresIds);

        // Filtres
        if ($request->has('statut') && !empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('offre_id') && !empty($request->offre_id)) {
            $query->where('offre_id', $request->offre_id);
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
     * Mettre à jour le statut d'une candidature
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidature  $candidature
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCandidature(Request $request, Candidature $candidature)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'entreprise') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Vérifier que la candidature appartient à une offre de cette entreprise
        if ($candidature->offre->entreprise_id !== $user->entreprise->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $request->validate([
            'statut' => 'required|in:en_attente,vue,entretien,acceptee,refusee'
        ]);

        $candidature->update([
            'statut' => $request->statut
        ]);

        $candidature->load(['etudiant.user', 'offre.entreprise.user']);

        return response()->json([
            'success' => true,
            'message' => 'Statut de la candidature mis à jour',
            'data' => $candidature
        ]);
    }

    /**
     * Afficher les statistiques des candidatures
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistiques()
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'entreprise') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $entreprise = $user->entreprise;
        $offresIds = Offre::where('entreprise_id', $entreprise->id)->pluck('id');

        $stats = [
            'total_candidatures' => Candidature::whereIn('offre_id', $offresIds)->count(),
            'en_attente' => Candidature::whereIn('offre_id', $offresIds)->where('statut', 'en_attente')->count(),
            'vues' => Candidature::whereIn('offre_id', $offresIds)->where('statut', 'vue')->count(),
            'entretiens' => Candidature::whereIn('offre_id', $offresIds)->where('statut', 'entretien')->count(),
            'acceptees' => Candidature::whereIn('offre_id', $offresIds)->where('statut', 'acceptee')->count(),
            'refusees' => Candidature::whereIn('offre_id', $offresIds)->where('statut', 'refusee')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
} 
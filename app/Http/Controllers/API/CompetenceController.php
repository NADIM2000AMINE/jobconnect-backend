<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Competence;
use Illuminate\Http\Request;

class CompetenceController extends Controller
{
    /**
     * Afficher toutes les compétences
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $competences = Competence::orderBy('nom')->get();
        
        return response()->json([
            'success' => true,
            'data' => $competences
        ]);
    }

    /**
     * Afficher une compétence spécifique
     *
     * @param  \App\Models\Competence  $competence
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Competence $competence)
    {
        return response()->json([
            'success' => true,
            'data' => $competence
        ]);
    }
} 
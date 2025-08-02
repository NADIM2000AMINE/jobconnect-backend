<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CompetenceController;
use App\Http\Controllers\API\OffreController;
use App\Http\Controllers\API\EntrepriseController;
use App\Http\Controllers\API\EtudiantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Routes protégées qui nécessitent une authentification
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
    });
    
    // Routes qui nécessitent une authentification ET une vérification d'email
    Route::middleware(['auth:sanctum', 'verified.api'])->group(function () {
        // Ajoutez ici les routes qui requièrent un email vérifié
        // Par exemple:
        // Route::post('update-profile', [ProfileController::class, 'update']);
    });
});

// Routes publiques pour les compétences et offres
Route::get('competences', [CompetenceController::class, 'index']);
Route::get('offres', [OffreController::class, 'index']);
Route::get('offres-simple', [OffreController::class, 'indexSimple']); // Version simplifiée
Route::get('offres/{offre}', [OffreController::class, 'show']);

// Routes protégées pour les offres (création, modification, suppression)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('offres', [OffreController::class, 'store']);
    Route::put('offres/{offre}', [OffreController::class, 'update']);
    Route::delete('offres/{offre}', [OffreController::class, 'destroy']);
    
    // Routes pour les entreprises
    Route::prefix('entreprise')->group(function () {
        Route::get('candidatures', [EntrepriseController::class, 'candidatures']);
        Route::put('candidatures/{candidature}', [EntrepriseController::class, 'updateCandidature']);
        Route::get('statistiques', [EntrepriseController::class, 'statistiques']);
    });
    
    // Routes pour les étudiants
    Route::prefix('etudiant')->group(function () {
        Route::get('candidatures', [EtudiantController::class, 'candidatures']);
        Route::post('postuler', [EtudiantController::class, 'postuler']);
        Route::delete('candidatures/{candidature}', [EtudiantController::class, 'annulerCandidature']);
    });
});
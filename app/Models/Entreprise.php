<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreprise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_entreprise',
        'description',
        'secteur_activite',
        'taille',
        'site_web',
        'adresse',
        'ville',
        'code_postal',
        'pays',
        'est_verifie',
    ];

    protected $casts = [
        'est_verifie' => 'boolean',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les offres d'emploi
     */
    public function offres()
    {
        return $this->hasMany(Offre::class);
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'offre_id',
        'titre',
        'description',
        'duree_minutes',
        'niveau_difficulte',
    ];

    /**
     * Get the offre that owns the test.
     */
    public function offre()
    {
        return $this->belongsTo(Offre::class);
    }

    /**
     * Get the questions for the test.
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
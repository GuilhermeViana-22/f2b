<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pergunta extends Model
{
    use HasFactory; // Esta trait é essencial para usar factories

    protected $fillable = [
        'pergunta',
        'tipo_pergunta_id',
        'tipo_periodicidade_id'
    ];


    public function tipoPergunta()
{
    return $this->belongsTo(TipoPergunta::class, 'tipo_pergunta_id');
}
}

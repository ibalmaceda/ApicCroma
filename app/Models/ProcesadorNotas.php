<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcesadorNotas extends Model
{
    use HasFactory;
    protected $table = 'procesador_notas';
    protected $fillable = ['url', 'codigo_nota', 'estado_procesamiento'];
}

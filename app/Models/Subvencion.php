<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subvencion extends Model
{
    use HasFactory;

    protected $table = 'subvenciones';

    protected $fillable = [
        'anio', 'mes', 'codigo_ensenanza', 'grado', 'letra',
        'ens', 'nivel', 'glosa', 'subvencion_base',
        'tipo', 'curso', 'archivo_origen'
    ];
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subvenciones', function (Blueprint $table) {
            $table->id();
            
            // Periodo
            $table->integer('anio');
            $table->integer('mes');
            
            // Datos del curso
            $table->string('codigo_ensenanza', 20);
            $table->integer('grado');
            $table->string('letra', 5)->nullable();
            $table->string('ens', 10)->nullable();
            $table->integer('nivel')->nullable();
            $table->text('glosa')->nullable();
            
            // Valores
            $table->decimal('subvencion_base', 15, 2)->default(0);
            
            // Clasificación
            $table->enum('tipo', ['GENERAL', 'PIE_CURSO', 'PIE_ALUMNOS'])->default('GENERAL');
            
            // Curso generado
            $table->string('curso')->nullable();
            
            // Metadatos
            $table->string('archivo_origen')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['anio', 'mes']);
            $table->index('ens');
            $table->index('tipo');
            $table->index('curso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subvenciones');
    }
};
<?php

namespace App\Services;

use App\Models\Subvencion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubvencionService
{
    const GENERAL = [9, 10, 110, 310];
    const PIE_CURSO = [1009, 1010, 1110, 1310];

    public function procesarArchivo(string $filePath, int $anio, int $mes, string $nombreArchivo): array
    {
        Log::info('📄 Procesando archivo: ' . $nombreArchivo);
        Log::info('📅 Periodo: ' . $mes . '/' . $anio);

        $nombreArchivo = preg_replace('/[^a-zA-Z0-9_.-]/', '', $nombreArchivo);

        $rows = $this->leerArchivoHTML($filePath);
        Log::info('📊 Filas leídas: ' . count($rows));

        $headerInfo = $this->encontrarEncabezados($rows);
        $startRow = $headerInfo['fila'] + 1;
        
        // 🔥 MAPEO DINÁMICO DE COLUMNAS
        $indices = $this->mapearColumnas($headerInfo['header']);
        
        // 🔥 Si no se encontró 'base' en el mapeo, usar fallback
        if (!isset($indices['base'])) {
            $baseIndex = $this->getBaseColumnIndex($mes, $anio);
            $indices['base'] = $baseIndex;
        }
        
        Log::info('📊 StartRow: ' . $startRow);
        Log::info("📊 Mapeo de columnas:", $indices);

        $this->validarColumnas($indices);
        
        $resultado = DB::transaction(function () use ($anio, $mes, $rows, $startRow, $indices, $nombreArchivo) {
            $eliminados = Subvencion::where('anio', $anio)->where('mes', $mes)->delete();
            Log::info('🗑️ Registros eliminados: ' . $eliminados);

            $resultado = $this->procesarFilas($rows, $startRow, $indices, [
                'anio' => $anio,
                'mes' => $mes,
                'archivo' => $nombreArchivo
            ]);

            if ($resultado['total_registros'] === 0) {
                throw new \RuntimeException('El archivo no contiene registros válidos para importar.');
            }

            return $resultado;
        });

        Log::info('✅ Total registros importados: ' . $resultado['total_registros']);
        return $resultado;
    }

    protected function leerArchivoHTML(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        if (strpos($content, '<table') !== false || strpos($content, '<TR') !== false) {
            return $this->extraerTablaHTML($content);
        }
        
        throw new \Exception('No se encontró una tabla en el archivo HTML');
    }

    protected function extraerTablaHTML(string $html): array
    {
        preg_match('/<table[^>]*>(.*?)<\/table>/is', $html, $tableMatch);
        
        if (empty($tableMatch[1])) {
            preg_match('/<TABLE[^>]*>(.*?)<\/TABLE>/is', $html, $tableMatch);
        }
        
        if (empty($tableMatch[1])) {
            throw new \Exception('No se encontró una tabla en el archivo HTML');
        }
        
        $tableContent = $tableMatch[1];
        $rows = [];
        
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableContent, $trMatches);
        
        foreach ($trMatches[1] as $trContent) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $trContent, $tdMatches);
            
            if (!empty($tdMatches[1])) {
                $row = [];
                foreach ($tdMatches[1] as $cell) {
                    $clean = strip_tags($cell);
                    $clean = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $clean);
                    $clean = trim(html_entity_decode($clean));
                    $row[] = $clean;
                }
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }
        
        if (empty($rows)) {
            throw new \Exception('No se encontraron datos en la tabla HTML');
        }
        
        Log::info('📊 Filas extraídas: ' . count($rows));
        return $rows;
    }

    protected function encontrarEncabezados(array $rows): array
    {
        Log::info('🔍 Buscando encabezados...');
        
        for ($i = 0; $i < min(50, count($rows)); $i++) {
            $row = $rows[$i];
            if (empty($row)) continue;
            
            $col0 = trim((string) ($row[0] ?? ''));
            $col0Normalizado = $this->normalizarTexto($col0);
            
            $variantes = [
                'cod ens', 'cod. ens', 'codigo ens', 'codigo enseñanza',
                'cod ensenanza', 'cod. ens.', 'cod ens.', 'código enseñanza',
                'código ens', 'cod. ense',
            ];
            
            foreach ($variantes as $variante) {
                if (str_contains($col0Normalizado, $variante)) {
                    Log::info("✅ Encabezados encontrados en FILA {$i}");
                    Log::info("   Columna 0: '{$col0}'");
                    Log::info("   Vista previa: " . json_encode(array_slice($row, 0, 10)));
                    return ['fila' => $i, 'header' => $row];
                }
            }
        }
        
        Log::error('❌ No se encontraron encabezados. Primeras 5 filas:');
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            Log::error("   Fila {$i}: " . json_encode(array_slice($rows[$i], 0, 8)));
        }
        
        throw new \Exception('No se encontraron los encabezados del archivo.');
    }

    /**
     * 🔥 DETECCIÓN AUTOMÁTICA DE COLUMNAS (CORREGIDO)
     */
    protected function mapearColumnas(array $header): array
    {
        $indices = [];
        
        Log::info('🔍 ========== INICIANDO MAPEO DE COLUMNAS ==========');
        
        Log::info('📋 ENCABEZADO DEL ARCHIVO:');
        foreach ($header as $i => $col) {
            $columna = trim((string) $col);
            $columna = str_replace(["\n", "\r", "\t"], ' ', $columna);
            $columna = preg_replace('/\s+/', ' ', $columna);
            Log::info("   Columna [{$i}]: '{$columna}'");
        }
        
        Log::info('🔍 Buscando columnas necesarias...');

        foreach ($header as $i => $titulo) {
            $t = $this->normalizarTexto($titulo);
            $tOriginal = trim((string) $titulo);
            
            Log::debug("Analizando columna {$i}: '{$tOriginal}' → '{$t}'");

            // 1. COD. ENS.
            if (str_contains($t, 'cod ens') || str_contains($t, 'cod. ens') || str_contains($t, 'codigo ens') || str_contains($t, 'codigo enseñanza')) {
                $indices['codigo'] = $i;
                Log::info("✅ 'Código ENS' → columna {$i}: '{$tOriginal}'");
            }
            // 2. GRADO
            elseif (str_contains($t, 'grado') && !str_contains($t, 'promedio')) {
                $indices['grado'] = $i;
                Log::info("✅ 'Grado' → columna {$i}: '{$tOriginal}'");
            }
            // 3. LETRA
            elseif (str_contains($t, 'letra') && !str_contains($t, 'subvencion')) {
                $indices['letra'] = $i;
                Log::info("✅ 'Letra' → columna {$i}: '{$tOriginal}'");
            }
            // 4. ENS
            elseif ($t === 'ens' || (str_contains($t, 'ens') && !str_contains($t, 'cod') && !str_contains($t, 'codigo'))) {
                $indices['ens'] = $i;
                Log::info("✅ 'ENS' → columna {$i}: '{$tOriginal}'");
            }
            // 5. NIVEL
            elseif (str_contains($t, 'nivel')) {
                $indices['nivel'] = $i;
                Log::info("✅ 'Nivel' → columna {$i}: '{$tOriginal}'");
            }
            // 6. GLOSA SUBVENCIÓN
            elseif (str_contains($t, 'glosa')) {
                $indices['glosa'] = $i;
                Log::info("✅ 'Glosa' → columna {$i}: '{$tOriginal}'");
            }
            // 7. SUBVENCIÓN BASE
            elseif (str_contains($t, 'subvencion') || str_contains($t, 'subvención')) {
                if (str_contains($t, 'base') && !str_contains($t, 'ruralidad') && !str_contains($t, 'incremento')) {
                    $indices['base'] = $i;
                    Log::info("✅ 'Subvención Base' → columna {$i}: '{$tOriginal}'");
                }
            }
            // 8. FACTOR USE
            elseif (str_contains($t, 'factor use') || str_contains($t, 'factor')) {
                $indices['factor'] = $i;
                Log::info("✅ 'Factor USE' → columna {$i}: '{$tOriginal}'");
            }
            // 9. PROMEDIO ASISTENCIA
            elseif (str_contains($t, 'promedio')) {
                $indices['promedio'] = $i;
                Log::info("✅ 'Promedio' → columna {$i}: '{$tOriginal}'");
            }
        }

        // FALLBACK para "Subvención Base"
        if (!isset($indices['base'])) {
            Log::warning("⚠️ No se encontró 'Subvención Base' por nombre. Buscando por posición...");
            $posiblesIndices = [9, 10, 11, 12, 13, 14];
            foreach ($posiblesIndices as $idx) {
                if (isset($header[$idx])) {
                    $columna = trim((string) $header[$idx]);
                    if (preg_match('/[0-9,.]/', $columna)) {
                        $indices['base'] = $idx;
                        Log::info("✅ 'Subvención Base' encontrada por posición en columna {$idx}: '{$columna}'");
                        break;
                    }
                }
            }
        }

        // FALLBACK para "Factor USE"
        if (!isset($indices['factor'])) {
            Log::warning("⚠️ No se encontró 'Factor USE' por nombre. Buscando por posición...");
            foreach ([11, 10, 9] as $idx) {
                if (isset($header[$idx]) && $idx != ($indices['base'] ?? -1)) {
                    $indices['factor'] = $idx;
                    Log::info("✅ 'Factor USE' encontrado por posición en columna {$idx}");
                    break;
                }
            }
        }

        // FALLBACK para "Glosa"
        if (!isset($indices['glosa']) && isset($header[6])) {
            $indices['glosa'] = 6;
            Log::info("⚠️ 'Glosa' usando fallback en columna 6");
        }

        // FALLBACK para "Promedio"
        if (!isset($indices['promedio'])) {
            foreach ([10, 9, 8] as $idx) {
                if (isset($header[$idx]) && $idx != ($indices['base'] ?? -1) && $idx != ($indices['factor'] ?? -1)) {
                    $indices['promedio'] = $idx;
                    Log::info("✅ 'Promedio' encontrado por posición en columna {$idx}");
                    break;
                }
            }
        }

        // FALLBACK FINAL para "Subvención Base"
        if (!isset($indices['base'])) {
            $indices['base'] = 12;
            Log::warning("⚠️ Usando índice 12 como fallback para 'Subvención Base'");
        }

        // ============================================================
        // 🔥 MOSTRAR RESUMEN FINAL (CORREGIDO)
        // ============================================================
        Log::info('📊 ========== RESUMEN DE MAPEO ==========');
        
        $valor = $header[$indices['codigo']] ?? 'N/A';
        Log::info("   'Código ENS'    → columna {$indices['codigo']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['grado']] ?? 'N/A';
        Log::info("   'Grado'         → columna {$indices['grado']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['letra']] ?? 'N/A';
        Log::info("   'Letra'         → columna {$indices['letra']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['ens']] ?? 'N/A';
        Log::info("   'ENS'           → columna {$indices['ens']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['nivel']] ?? 'N/A';
        Log::info("   'Nivel'         → columna {$indices['nivel']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['glosa']] ?? 'N/A';
        Log::info("   'Glosa'         → columna {$indices['glosa']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['base']] ?? 'N/A';
        Log::info("   'Subvención Base' → columna {$indices['base']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['factor']] ?? 'N/A';
        Log::info("   'Factor USE'    → columna {$indices['factor']}  (Valor: '{$valor}')");
        
        $valor = $header[$indices['promedio']] ?? 'N/A';
        Log::info("   'Promedio'      → columna {$indices['promedio']}  (Valor: '{$valor}')");
        
        Log::info('📊 ==========================================');

        return $indices;
    }

    /**
     * Normaliza texto: mayúsculas, sin acentos, sin espacios extras
     */
    private function normalizarTexto($texto): string
    {
        $texto = trim((string) $texto);
        $texto = mb_strtoupper($texto);
        
        $acentos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            'Ã' => 'A', 'Õ' => 'O',
        ];
        $texto = str_replace(array_keys($acentos), array_values($acentos), $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        
        return trim($texto);
    }

    protected function validarColumnas(array $indices): void
    {
        $requeridas = ['codigo', 'grado', 'letra', 'ens', 'nivel', 'glosa', 'base'];
        $faltantes = [];
        
        foreach ($requeridas as $campo) {
            if (!isset($indices[$campo])) {
                $faltantes[] = $campo;
            }
        }
        
        if (!empty($faltantes)) {
            throw new \Exception('No se encontraron las siguientes columnas: ' . implode(', ', $faltantes));
        }
        
        Log::info("✅ Columnas validadas correctamente");
    }

    protected function procesarFilas(array $rows, int $startRow, array $indices, array $metadata): array
    {
        Log::info('📊 Iniciando procesamiento de filas desde: ' . $startRow);
        Log::info('📊 Total de filas: ' . count($rows));

        $contador = 0;
        $totales = [
            'total_base' => 0,
            'general' => 0,
            'pie_curso' => 0,
            'pie_alumnos' => 0,
            'total_registros' => 0
        ];

        $codigoIdx = $indices['codigo'] ?? 0;
        $gradoIdx = $indices['grado'] ?? 1;
        $letraIdx = $indices['letra'] ?? 3;
        $ensIdx = $indices['ens'] ?? 4;
        $nivelIdx = $indices['nivel'] ?? 5;
        $glosaIdx = $indices['glosa'] ?? 6;
        $baseIdx = $indices['base'] ?? 12;

        Log::info("📊 Usando índices: Cod={$codigoIdx}, Grado={$gradoIdx}, Letra={$letraIdx}, ENS={$ensIdx}, Base={$baseIdx}");

        for ($i = $startRow; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty($row) || count($row) < 10) {
                continue;
            }

            $codigo = trim((string) ($row[$codigoIdx] ?? ''));
            if ($codigo === '' || !is_numeric($codigo)) {
                continue;
            }

            $glosa = trim((string) ($row[$glosaIdx] ?? ''));
            
            if (stripos($glosa, 'Total') === 0) {
                Log::debug("⏭️ Saltando fila de total: {$glosa}");
                continue;
            }

            $base = $this->limpiarNumero($row[$baseIdx] ?? 0);
            if ($base == 0) {
                continue;
            }

            $ens = trim((string) ($row[$ensIdx] ?? ''));
            $ensNumerico = is_numeric($ens) ? (int) $ens : null;
            if ($ensNumerico === null) {
                continue;
            }

            if (in_array($ensNumerico, self::GENERAL)) {
                $tipo = 'GENERAL';
            } elseif (in_array($ensNumerico, self::PIE_CURSO)) {
                $tipo = 'PIE_CURSO';
            } else {
                $tipo = 'PIE_ALUMNOS';
            }

            $grado = isset($row[$gradoIdx]) ? (int) $row[$gradoIdx] : 0;
            $letra = trim((string) ($row[$letraIdx] ?? 'A'));
            $nivel = isset($row[$nivelIdx]) ? (int) $row[$nivelIdx] : null;

            $curso = $this->generarCurso($codigo, $grado, $letra);

            $totales['total_base'] += $base;
            if ($tipo === 'GENERAL') $totales['general'] += $base;
            elseif ($tipo === 'PIE_CURSO') $totales['pie_curso'] += $base;
            elseif ($tipo === 'PIE_ALUMNOS') $totales['pie_alumnos'] += $base;

            Subvencion::create([
                'anio' => $metadata['anio'],
                'mes' => $metadata['mes'],
                'codigo_ensenanza' => $codigo,
                'grado' => $grado,
                'letra' => $letra,
                'ens' => $ens,
                'nivel' => $nivel,
                'glosa' => $glosa,
                'subvencion_base' => $base,
                'tipo' => $tipo,
                'curso' => $curso,
                'archivo_origen' => $metadata['archivo']
            ]);
            $contador++;
            
            if ($contador % 50 == 0) {
                Log::info("📝 Procesadas {$contador} filas...");
            }
        }

        $totales['total_registros'] = $contador;

        Log::info("✅ Total registros importados: {$contador}");
        Log::info("💰 Total Base: " . number_format($totales['total_base'], 0, ',', '.'));
        Log::info("💰 General: " . number_format($totales['general'], 0, ',', '.'));
        Log::info("💰 PIE Curso: " . number_format($totales['pie_curso'], 0, ',', '.'));
        Log::info("💰 PIE Alumnos: " . number_format($totales['pie_alumnos'], 0, ',', '.'));

        return $totales;
    }

    protected function generarCurso($codigo, $grado, $letra): string
    {
        $niveles = [
            '10' => [4 => 'Pre Kinder', 5 => 'Kinder'],
            '110' => [
                1 => '1 Básico', 2 => '2 Básico', 3 => '3 Básico',
                4 => '4 Básico', 5 => '5 Básico', 6 => '6 Básico',
                7 => '7 Básico', 8 => '8 Básico'
            ],
            '310' => [
                1 => '1 Medio', 2 => '2 Medio', 3 => '3 Medio', 4 => '4 Medio'
            ]
        ];

        $nombre = $niveles[$codigo][$grado] ?? 'Curso';
        return $nombre . ' ' . $letra;
    }

    protected function limpiarNumero($valor): float
    {
        if ($valor === null || $valor === '' || $valor === '&nbsp;') {
            return 0.0;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $valorOriginal = $valor;
        $valor = html_entity_decode(trim((string) $valor));
        $valor = str_replace(["\xc2\xa0"], ' ', $valor);
        $valor = str_replace(['$', '€', '£'], '', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor);
        $valor = trim($valor);
        
        if ($valor === '' || $valor === '-') {
            return 0.0;
        }

        $hasDot = str_contains($valor, '.');
        $hasComma = str_contains($valor, ',');

        if ($hasComma && !$hasDot) {
            $parts = explode(',', $valor);
            if (count($parts) === 2) {
                $decimalPart = $parts[1];
                if (strlen($decimalPart) >= 2 && strlen($decimalPart) <= 5) {
                    $valor = str_replace(',', '.', $valor);
                } else {
                    $valor = str_replace(',', '', $valor);
                }
            } else {
                $valor = str_replace(',', '', $valor);
            }
        }
        elseif ($hasDot && !$hasComma) {
            $parts = explode('.', $valor);
            $lastPart = end($parts);
            
            if (count($parts) === 2 && strlen($lastPart) === 2) {
                // mantener
            } 
            elseif (count($parts) >= 2 && strlen($lastPart) === 3) {
                $valor = str_replace('.', '', $valor);
            }
            elseif (count($parts) === 2 && strlen($lastPart) >= 3 && strlen($lastPart) <= 5) {
                // mantener
            }
            elseif (strlen($lastPart) === 3 && count($parts) >= 3) {
                $valor = str_replace('.', '', $valor);
            }
            else {
                $valor = str_replace('.', '', $valor);
            }
        }
        elseif ($hasDot && $hasComma) {
            $lastDot = strrpos($valor, '.');
            $lastComma = strrpos($valor, ',');
            
            if ($lastComma > $lastDot) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } else {
                $valor = str_replace(',', '', $valor);
            }
        }

        $valor = preg_replace('/[^0-9.\-]/', '', $valor);
        
        if ($valor === '' || $valor === '-' || $valor === '.') {
            return 0.0;
        }

        $valorNumerico = floatval($valor);
        
        if (abs($valorNumerico - 0) > 0.01) {
            Log::debug("💰 Valor limpiado: '{$valorOriginal}' → {$valorNumerico}");
        }
        
        return $valorNumerico;
    }

    /**
     * 🔥 FALLBACK: Solo se usa si mapearColumnas() no encontró la columna
     */
    private function getBaseColumnIndex(int $mes, int $anio): int
    {
        $index = 12;
        Log::info("📍 Fallback: usando índice {$index} para columna Base (mes {$mes}/{$anio})");
        return $index;
    }
}
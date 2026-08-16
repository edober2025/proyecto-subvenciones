<?php

namespace App\Http\Controllers;

use App\Models\Subvencion;
use App\Services\SubvencionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected SubvencionService $subvencionService;
    
    protected $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];

    public function __construct(SubvencionService $subvencionService)
    {
        $this->subvencionService = $subvencionService;
    }

    public function index()
    {
        return view('dashboard.index');
    }

    private function validarFechaFutura($anio, $mes): void
    {
        $fechaActual = now();
        $anioActual = $fechaActual->year;
        $mesActual = $fechaActual->month;

        if ($anio > $anioActual || ($anio === $anioActual && $mes > $mesActual)) {
            throw new \Exception("No se pueden consultar datos para fechas futuras ({$mes}/{$anio})");
        }
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|extensions:xls,xlsx|max:20480',
                'anio' => 'required|integer|min:2020|max:2030',
                'mes' => 'required|integer|between:1,12',
                'confirmar' => 'nullable|boolean'
            ]);

            $anio = $request->anio;
            $mes = $request->mes;
            $confirmar = $request->confirmar ?? false;
            $nombreMes = $this->meses[$mes] ?? $mes;

            $this->validarFechaFutura($anio, $mes);

            Log::info('📊 Archivo subido', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'archivo' => $request->file('archivo')->getClientOriginalName(),
                'periodo' => "{$mes}/{$anio}"
            ]);

            $existen = Subvencion::where('anio', $anio)
                                 ->where('mes', $mes)
                                 ->exists();

            if ($existen && !$confirmar) {
                return response()->json([
                    'success' => false,
                    'requires_confirmation' => true,
                    'message' => "⚠️ Ya existen datos para {$nombreMes} {$anio}. ¿Quieres reemplazarlos?",
                    'data' => [
                        'anio' => $anio,
                        'mes' => $mes,
                        'registros_existentes' => Subvencion::where('anio', $anio)->where('mes', $mes)->count()
                    ]
                ], 409);
            }

            $archivo = $request->file('archivo');
            $nombreArchivo = $archivo->getClientOriginalName();
            $filePath = $archivo->getPathname();

            Log::info('🚀 Procesando archivo: ' . $nombreArchivo);
            Log::info("📅 Periodo: {$nombreMes} {$anio}" . ($existen ? ' (Reemplazando)' : ' (Nuevo)'));

            $resultado = $this->subvencionService->procesarArchivo(
                $filePath,
                $anio,
                $mes,
                $nombreArchivo
            );

            Cache::forget('meses_disponibles');
            Cache::forget("resumen_{$anio}_{$mes}");
            Cache::forget("detalle_{$anio}_{$mes}");
            Cache::forget("grafico_{$anio}_{$mes}");
            Cache::forget('evolucion');

            $accion = $existen ? 'reemplazados' : 'importados';
            return response()->json([
                'success' => true,
                'message' => "✅ Archivo procesado. Se {$accion} {$resultado['total_registros']} registros para {$nombreMes} {$anio}.",
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en upload: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '❌ ' . $e->getMessage()
            ], 500);
        }
    }

    public function resumen(Request $request)
    {
        try {
            $anio = $request->input('anio');
            $mes = $request->input('mes');

            Log::info('📊 resumen() llamado con anio=' . $anio . ', mes=' . $mes);

            if (!$anio || !$mes) {
                $ultimo = Subvencion::select('anio', 'mes')
                    ->distinct()
                    ->orderBy('anio', 'desc')
                    ->orderBy('mes', 'desc')
                    ->first();
                
                if ($ultimo) {
                    $anio = $ultimo->anio;
                    $mes = $ultimo->mes;
                } else {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'total_base' => 0,
                            'general' => 0,
                            'pie_curso' => 0,
                            'pie_alumnos' => 0,
                            'total_registros' => 0
                        ]
                    ]);
                }
            }

            $this->validarFechaFutura($anio, $mes);

            $cacheKey = "resumen_{$anio}_{$mes}";
            $data = Cache::remember($cacheKey, 3600, function() use ($anio, $mes) {
                $totalRegistros = Subvencion::where('anio', $anio)->where('mes', $mes)->count();

                if ($totalRegistros > 0) {
                    $totalBase = Subvencion::where('anio', $anio)->where('mes', $mes)->sum('subvencion_base');
                    $general = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'GENERAL')->sum('subvencion_base');
                    $pieCurso = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'PIE_CURSO')->sum('subvencion_base');
                    $pieAlumnos = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'PIE_ALUMNOS')->sum('subvencion_base');
                } else {
                    $totalBase = 0;
                    $general = 0;
                    $pieCurso = 0;
                    $pieAlumnos = 0;
                }

                return [
                    'total_base' => $totalBase,
                    'general' => $general,
                    'pie_curso' => $pieCurso,
                    'pie_alumnos' => $pieAlumnos,
                    'total_registros' => $totalRegistros
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en resumen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function detalle(Request $request)
    {
        try {
            $anio = $request->input('anio');
            $mes = $request->input('mes');

            Log::info('📊 detalle() llamado con anio=' . $anio . ', mes=' . $mes);

            if (!$anio || !$mes) {
                $ultimo = Subvencion::select('anio', 'mes')
                    ->distinct()
                    ->orderBy('anio', 'desc')
                    ->orderBy('mes', 'desc')
                    ->first();
                
                if ($ultimo) {
                    $anio = $ultimo->anio;
                    $mes = $ultimo->mes;
                } else {
                    Log::warning('⚠️ No hay datos en la base de datos');
                    return response()->json([
                        'success' => true,
                        'data' => []
                    ]);
                }
            }

            $this->validarFechaFutura($anio, $mes);

            $cacheKey = "detalle_{$anio}_{$mes}";
            $resultado = Cache::remember($cacheKey, 3600, function() use ($anio, $mes) {
                $totalRegistros = Subvencion::where('anio', $anio)->where('mes', $mes)->count();
                Log::info('📊 Total registros para el periodo: ' . $totalRegistros);

                if ($totalRegistros == 0) {
                    Log::warning('⚠️ No hay registros para el periodo ' . $mes . '/' . $anio);
                    return [];
                }

                $todosLosCursos = Subvencion::select(
                        'curso',
                        DB::raw('SUM(subvencion_base) as total_base'),
                        DB::raw('SUM(CASE WHEN tipo = "GENERAL" THEN subvencion_base ELSE 0 END) as general'),
                        DB::raw('SUM(CASE WHEN tipo = "PIE_CURSO" THEN subvencion_base ELSE 0 END) as pie_curso'),
                        DB::raw('SUM(CASE WHEN tipo = "PIE_ALUMNOS" THEN subvencion_base ELSE 0 END) as pie_alumnos')
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->groupBy('curso')
                    ->get();

                Log::info('📋 Cursos encontrados: ' . $todosLosCursos->count());

                $sedesConfig = [
                    'Sede Jardín' => $this->cursosPorSede('jardin'),
                    'Sede 1 a 4 Básico' => $this->cursosPorSede('basica_1_4'),
                    'Sede 5 a 6 Básico' => $this->cursosPorSede('basica_5_6'),
                    'Sede 7 a 8 Básico' => $this->cursosPorSede('basica_7_8'),
                    'Sede Ed. Media' => $this->cursosPorSede('media')
                ];

                $resultado = [];
                $totalGeneralBase = 0;
                $totalGeneralGeneral = 0;
                $totalGeneralPieCurso = 0;
                $totalGeneralPieAlumnos = 0;

                foreach ($sedesConfig as $nombreSede => $cursosSede) {
                    $totalBase = 0;
                    $general = 0;
                    $pieCurso = 0;
                    $pieAlumnos = 0;
                    
                    foreach ($cursosSede as $curso) {
                        $encontrado = $todosLosCursos->firstWhere('curso', $curso);
                        
                        if ($encontrado) {
                            $totalBase += $encontrado->total_base ?? 0;
                            $general += $encontrado->general ?? 0;
                            $pieCurso += $encontrado->pie_curso ?? 0;
                            $pieAlumnos += $encontrado->pie_alumnos ?? 0;
                        }
                    }
                    
                    if ($totalBase > 0 || $general > 0 || $pieCurso > 0 || $pieAlumnos > 0) {
                        $resultado[] = [
                            'sede' => $nombreSede,
                            'total_base' => $totalBase,
                            'general' => $general,
                            'pie_curso' => $pieCurso,
                            'pie_alumnos' => $pieAlumnos
                        ];
                        
                        $totalGeneralBase += $totalBase;
                        $totalGeneralGeneral += $general;
                        $totalGeneralPieCurso += $pieCurso;
                        $totalGeneralPieAlumnos += $pieAlumnos;
                    }
                }

                $resultado[] = [
                    'sede' => 'TOTALES',
                    'total_base' => $totalGeneralBase,
                    'general' => $totalGeneralGeneral,
                    'pie_curso' => $totalGeneralPieCurso,
                    'pie_alumnos' => $totalGeneralPieAlumnos,
                    'es_total' => true
                ];

                Log::info('📊 Resultado final: ' . count($resultado) . ' filas');
                return $resultado;
            });

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en detalle: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function grafico(Request $request)
    {
        try {
            $anio = $request->input('anio');
            $mes = $request->input('mes');

            if (!$anio || !$mes) {
                $ultimo = Subvencion::select('anio', 'mes')
                    ->distinct()
                    ->orderBy('anio', 'desc')
                    ->orderBy('mes', 'desc')
                    ->first();
                
                if ($ultimo) {
                    $anio = $ultimo->anio;
                    $mes = $ultimo->mes;
                } else {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'labels' => [],
                            'values' => []
                        ]
                    ]);
                }
            }

            $this->validarFechaFutura($anio, $mes);

            $cacheKey = "grafico_{$anio}_{$mes}";
            $data = Cache::remember($cacheKey, 3600, function() use ($anio, $mes) {
                $totalRegistros = Subvencion::where('anio', $anio)->where('mes', $mes)->count();

                if ($totalRegistros == 0) {
                    return [
                        'labels' => [],
                        'values' => []
                    ];
                }

                $datos = Subvencion::select(
                        'tipo',
                        DB::raw('SUM(subvencion_base) as total')
                    )
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->groupBy('tipo')
                    ->get();

                $labels = [];
                $values = [];

                $nombresTipos = [
                    'GENERAL' => 'General',
                    'PIE_CURSO' => 'PIE Curso',
                    'PIE_ALUMNOS' => 'PIE Alumnos'
                ];

                foreach ($datos as $item) {
                    $labels[] = $nombresTipos[$item->tipo] ?? $item->tipo;
                    $values[] = (float) $item->total;
                }

                return [
                    'labels' => $labels,
                    'values' => $values
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en grafico: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function mesesDisponibles()
    {
        try {
            $resultado = Cache::remember('meses_disponibles', 3600, function() {
                $fechaActual = now();
                $anioActual = $fechaActual->year;
                $mesActual = $fechaActual->month;

                $meses = Subvencion::select('anio', 'mes')
                    ->distinct()
                    ->where(function($query) use ($anioActual, $mesActual) {
                        $query->where('anio', '<', $anioActual)
                              ->orWhere(function($q) use ($anioActual, $mesActual) {
                                  $q->where('anio', $anioActual)
                                    ->where('mes', '<=', $mesActual);
                              });
                    })
                    ->orderBy('anio', 'desc')
                    ->orderBy('mes', 'desc')
                    ->get();

                $resultado = [];
                foreach ($meses as $item) {
                    $nombreMes = $this->meses[$item->mes] ?? $item->mes;
                    $resultado[] = [
                        'anio' => $item->anio,
                        'mes' => $item->mes,
                        'label' => $nombreMes . ' ' . $item->anio,
                        'value' => $item->anio . '-' . str_pad($item->mes, 2, '0', STR_PAD_LEFT)
                    ];
                }
                return $resultado;
            });

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en mesesDisponibles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los meses disponibles'
            ], 500);
        }
    }

    public function evolucion()
    {
        try {
            $data = Cache::remember('evolucion', 3600, function() {
                $meses = Subvencion::select('anio', 'mes')
                    ->distinct()
                    ->orderBy('anio', 'asc')
                    ->orderBy('mes', 'asc')
                    ->get();

                if ($meses->isEmpty()) {
                    return [
                        'labels' => [],
                        'datasets' => []
                    ];
                }

                $labels = [];
                $totalBaseData = [];
                $generalData = [];
                $pieCursoData = [];
                $pieAlumnosData = [];

                foreach ($meses as $periodo) {
                    $anio = $periodo->anio;
                    $mes = $periodo->mes;
                    $nombreMes = $this->meses[$mes] ?? $mes;
                    $label = substr($nombreMes, 0, 3) . ' ' . $anio;
                    $labels[] = $label;

                    $totalBase = Subvencion::where('anio', $anio)->where('mes', $mes)->sum('subvencion_base');
                    $general = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'GENERAL')->sum('subvencion_base');
                    $pieCurso = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'PIE_CURSO')->sum('subvencion_base');
                    $pieAlumnos = Subvencion::where('anio', $anio)->where('mes', $mes)->where('tipo', 'PIE_ALUMNOS')->sum('subvencion_base');

                    $totalBaseData[] = (float) $totalBase;
                    $generalData[] = (float) $general;
                    $pieCursoData[] = (float) $pieCurso;
                    $pieAlumnosData[] = (float) $pieAlumnos;
                }

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Total Base',
                            'data' => $totalBaseData,
                            'borderColor' => '#4f46e5',
                            'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                            'pointRadius' => 2,
                            'pointBackgroundColor' => '#4f46e5',
                            'borderWidth' => 1.5
                        ],
                        [
                            'label' => 'General',
                            'data' => $generalData,
                            'borderColor' => '#10b981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                            'pointRadius' => 2,
                            'pointBackgroundColor' => '#10b981',
                            'borderWidth' => 1.5
                        ],
                        [
                            'label' => 'PIE Curso',
                            'data' => $pieCursoData,
                            'borderColor' => '#38bdf8',
                            'backgroundColor' => 'rgba(56, 189, 248, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                            'pointRadius' => 2,
                            'pointBackgroundColor' => '#38bdf8',
                            'borderWidth' => 1.5
                        ],
                        [
                            'label' => 'PIE Alumnos',
                            'data' => $pieAlumnosData,
                            'borderColor' => '#f59e0b',
                            'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                            'pointRadius' => 2,
                            'pointBackgroundColor' => '#f59e0b',
                            'borderWidth' => 1.5
                        ]
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en evolucion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos de evolución'
            ], 500);
        }
    }

    private function cursosPorSede($sede): array
    {
        $cursos = [
            'jardin' => [
                'Pre Kinder A', 'Pre Kinder B', 'Pre Kinder C', 'Pre Kinder D',
                'Kinder A', 'Kinder B', 'Kinder C', 'Kinder D', 'Kinder E', 'Kinder F'
            ],
            'basica_1_4' => [
                '1 Básico A', '1 Básico B', '1 Básico C', '1 Básico D',
                '2 Básico A', '2 Básico B', '2 Básico C', '2 Básico D',
                '3 Básico A', '3 Básico B', '3 Básico C', '3 Básico D',
                '4 Básico A', '4 Básico B', '4 Básico C', '4 Básico D'
            ],
            'basica_5_6' => [
                '5 Básico A', '5 Básico B', '5 Básico C', '5 Básico D',
                '6 Básico A', '6 Básico B', '6 Básico C', '6 Básico D'
            ],
            'basica_7_8' => [
                '7 Básico A', '7 Básico B', '7 Básico C', '7 Básico D',
                '8 Básico A', '8 Básico B', '8 Básico C', '8 Básico D',
                '8 Básico E'
            ],
            'media' => [
                '1 Medio A', '1 Medio B', '1 Medio C', '1 Medio D', '1 Medio E',
                '2 Medio A', '2 Medio B', '2 Medio C', '2 Medio D', '2 Medio E',
                '3 Medio A', '3 Medio B', '3 Medio C', '3 Medio D', '3 Medio E',
                '4 Medio A', '4 Medio B', '4 Medio C', '4 Medio D', '4 Medio E'
            ]
        ];

        return $cursos[$sede] ?? [];
    }

}
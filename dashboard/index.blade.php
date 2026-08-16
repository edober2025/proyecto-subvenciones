@extends('layouts.app')

@section('title', 'Dashboard - Subvenciones')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-0">
            <i class="fas fa-chart-line text-primary me-2"></i>Resumen de Subvenciones
        </h2>
        <p class="text-muted mb-0">Subvención Base, General y PIE</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select id="selectorPeriodo" class="form-select rounded-pill" style="min-width: 200px;">
            <option value="">Cargando meses...</option>
        </select>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Subir Archivo
        </button>
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" onclick="limpiarCache()" title="Limpiar caché">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</div>

<!-- Tarjetas de resumen -->
<div class="row g-4 mb-4" id="tarjetasResumen">
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card card-hover border-0 shadow-sm text-white bg-gradient-primary rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Total Base</h6>
                        <h2 class="fw-bold mb-0" id="totalBase">$0</h2>
                    </div>
                    <i class="fas fa-database fa-2x text-white-50"></i>
                </div>
                <small class="text-white-50"><i class="fas fa-arrow-up me-1"></i> Subvención total</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card card-hover border-0 shadow-sm text-white bg-gradient-success rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">General</h6>
                        <h2 class="fw-bold mb-0" id="totalGeneral">$0</h2>
                    </div>
                    <i class="fas fa-users fa-2x text-white-50"></i>
                </div>
                <small class="text-white-50"><i class="fas fa-check-circle me-1"></i> Subvención normal</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card card-hover border-0 shadow-sm text-white bg-gradient-info rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">PIE Curso</h6>
                        <h2 class="fw-bold mb-0" id="totalPieCurso">$0</h2>
                    </div>
                    <i class="fas fa-chalkboard fa-2x text-white-50"></i>
                </div>
                <small class="text-white-50"><i class="fas fa-star me-1"></i> Subvención por curso PIE</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6">
        <div class="card card-hover border-0 shadow-sm text-white bg-gradient-warning rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">PIE Alumnos</h6>
                        <h2 class="fw-bold mb-0" id="totalPieAlumnos">$0</h2>
                    </div>
                    <i class="fas fa-user-graduate fa-2x text-white-50"></i>
                </div>
                <small class="text-white-50"><i class="fas fa-graduation-cap me-1"></i> Subvención por alumno PIE</small>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico + Tabla -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-table me-2 text-primary"></i>Detalle por Sede</h5>
                <span class="badge bg-light text-dark" id="totalRegistros">0 registros</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-custom" id="tablaCursos">
                        <thead>
                            <tr>
                                <th>Sede</th>
                                <th class="text-end">Total Base</th>
                                <th class="text-end">General</th>
                                <th class="text-end">PIE Curso</th>
                                <th class="text-end">PIE Alumnos</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-cloud-upload-alt fa-2x d-block mb-2"></i>
                                    Sube un archivo para ver los datos
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Distribución</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-center" style="min-height: 300px; max-height: 400px;">
                <canvas id="chartPie" height="250"></canvas>
                <div id="sinDatos" class="text-center text-muted py-4">
                    <i class="fas fa-chart-pie fa-2x d-block mb-2"></i>
                    Sin datos para mostrar
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico de líneas - Evolución mensual (COMPACTA) -->
<div class="row g-1 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-1 pb-0">
                <h6 class="fw-bold mb-0" style="font-size: 0.85rem;"><i class="fas fa-chart-line me-1 text-primary"></i>Evolución Mensual</h6>
                <span class="badge bg-light text-dark" id="totalMeses" style="font-size: 0.7rem;">0 meses</span>
            </div>
            <div class="card-body py-0 px-1">
                <canvas id="chartLine" height="110"></canvas>
                <div id="sinDatosLinea" class="text-center text-muted py-1">
                    <i class="fas fa-chart-line fa-1x d-block mb-1"></i>
                    <span style="font-size: 0.7rem;">Sube archivos de diferentes meses para ver la evolución</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SUBIR ARCHIVO -->
@include('partials.modal_upload')

@endsection

@push('scripts')
<script>
// ==========================================
// VARIABLE GLOBAL
// ==========================================
let periodoActual = null;
let chartInstance = null;
let lineChartInstance = null;
let cargando = false;

// ==========================================
// CARGAR MESES DISPONIBLES
// ==========================================
function cargarMeses() {
    console.log('🔄 Cargando meses disponibles...');
    $('#selectorPeriodo').html('<option value="">Cargando...</option>');
    
    $.ajax({
        url: '{{ route("meses.disponibles") }}',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta meses:', response);
            if (response.success && response.data.length > 0) {
                const fechaActual = new Date();
                const anioActual = fechaActual.getFullYear();
                const mesActual = fechaActual.getMonth() + 1;
                
                let html = '<option value="">Seleccionar Mes</option>';
                response.data.forEach(item => {
                    // 🔥 FILTRO FRONTEND: Solo mostrar meses hasta el actual
                    if (item.anio < anioActual || 
                        (item.anio === anioActual && item.mes <= mesActual)) {
                        html += `<option value="${item.anio}-${String(item.mes).padStart(2, '0')}">
                                    ${item.label}
                                </option>`;
                    }
                });
                $('#selectorPeriodo').html(html);
                
                if (response.data.length > 0) {
                    let primerValido = null;
                    for (let item of response.data) {
                        if (item.anio < anioActual || 
                            (item.anio === anioActual && item.mes <= mesActual)) {
                            primerValido = item;
                            break;
                        }
                    }
                    
                    if (primerValido) {
                        const valor = primerValido.anio + '-' + String(primerValido.mes).padStart(2, '0');
                        $('#selectorPeriodo').val(valor);
                        periodoActual = valor;
                        cargarDatosPorPeriodo(primerValido.anio, primerValido.mes);
                    } else {
                        $('#selectorPeriodo').html('<option value="">Sin datos válidos</option>');
                        mostrarToast('⚠️ No hay datos para meses válidos', 'warning');
                    }
                }
                console.log('✅ Meses cargados:', response.data.length);
            } else {
                $('#selectorPeriodo').html('<option value="">Sin datos</option>');
            }
        },
        error: function(xhr) {
            console.error('❌ Error al cargar meses:', xhr);
            $('#selectorPeriodo').html('<option value="">Error al cargar</option>');
        }
    });
}

// ==========================================
// CARGAR DATOS POR PERIODO
// ==========================================
function cargarDatosPorPeriodo(anio, mes) {
    if (cargando) return;
    cargando = true;
    
    console.log(`🔄 Cargando datos para ${mes}/${anio}...`);
    
    // 🔥 VALIDACIÓN FRONTEND: No permitir fechas futuras
    const fechaActual = new Date();
    const anioActual = fechaActual.getFullYear();
    const mesActual = fechaActual.getMonth() + 1;
    
    if (anio > anioActual || (anio === anioActual && mes > mesActual)) {
        console.warn(`⚠️ Fecha futura detectada: ${mes}/${anio}`);
        mostrarToast(`⚠️ No hay datos disponibles para fechas futuras (${mes}/${anio})`, 'warning');
        $('#totalBase').text('$0');
        $('#totalGeneral').text('$0');
        $('#totalPieCurso').text('$0');
        $('#totalPieAlumnos').text('$0');
        $('#totalRegistros').text('0 registros');
        $('#cuerpoTabla').html(`<tr><td colspan="5" class="text-center text-muted py-4">
            <i class="fas fa-calendar-times fa-2x d-block mb-2"></i>
            No hay datos para fechas futuras
        </td></tr>`);
        $('#sinDatos').show();
        cargando = false;
        return;
    }
    
    // Mostrar spinner en tarjetas
    $('#tarjetasResumen .card-body').addClass('opacity-50');
    
    const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $('.badge.bg-primary').text(meses[mes-1] + ' ' + anio);

    // ==========================================
    // CARGAR TOTALES
    // ==========================================
    $.ajax({
        url: '{{ route("resumen") }}',
        type: 'GET',
        data: { anio: anio, mes: mes },
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta resumen:', response);
            if (response.success) {
                const d = response.data;
                $('#totalBase').text(formatoMoneda(d.total_base));
                $('#totalGeneral').text(formatoMoneda(d.general));
                $('#totalPieCurso').text(formatoMoneda(d.pie_curso));
                $('#totalPieAlumnos').text(formatoMoneda(d.pie_alumnos));
                $('#totalRegistros').text(d.total_registros + ' registros');
                console.log('✅ Totales actualizados');
            } else {
                console.error('❌ Error en resumen:', response.message);
                mostrarToast('❌ ' + response.message, 'danger');
            }
            $('#tarjetasResumen .card-body').removeClass('opacity-50');
        },
        error: function(xhr) {
            console.error('❌ Error al cargar totales:', xhr);
            mostrarToast('❌ Error al cargar los totales', 'danger');
            $('#tarjetasResumen .card-body').removeClass('opacity-50');
        }
    });

    // ==========================================
    // CARGAR DETALLE POR SEDE
    // ==========================================
    $.ajax({
        url: '{{ route("detalle") }}',
        type: 'GET',
        data: { anio: anio, mes: mes },
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta detalle:', response);
            let html = '';

            if (response.success && response.data && response.data.length > 0) {
                response.data.forEach(row => {
                    const totalBase = parseFloat(row.total_base) || 0;
                    const general = parseFloat(row.general) || 0;
                    const pieCurso = parseFloat(row.pie_curso) || 0;
                    const pieAlumnos = parseFloat(row.pie_alumnos) || 0;

                    if (row.es_total) {
                        html += `<tr class="table-success fw-bold">
                            <td><strong>${row.sede}</strong></td>
                            <td class="text-end">${formatoMoneda(totalBase)}</td>
                            <td class="text-end">${formatoMoneda(general)}</td>
                            <td class="text-end">${formatoMoneda(pieCurso)}</td>
                            <td class="text-end">${formatoMoneda(pieAlumnos)}</td>
                        </tr>`;
                    } else {
                        html += `<tr>
                            <td><strong>${row.sede}</strong></td>
                            <td class="text-end">${formatoMoneda(totalBase)}</td>
                            <td class="text-end">${formatoMoneda(general)}</td>
                            <td class="text-end">${formatoMoneda(pieCurso)}</td>
                            <td class="text-end">${formatoMoneda(pieAlumnos)}</td>
                        </tr>`;
                    }
                });
                
                $('#sinDatos').hide();
            } else {
                html = `<tr><td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-cloud-upload-alt fa-2x d-block mb-2"></i>
                    No hay datos para este período
                </td></tr>`;
                $('#sinDatos').show();
            }
            $('#cuerpoTabla').html(html);
            console.log('✅ Detalle actualizado');
            cargando = false;
        },
        error: function(xhr) {
            console.error('❌ Error al cargar detalle:', xhr);
            mostrarToast('❌ Error al cargar el detalle', 'danger');
            cargando = false;
        }
    });

    // ==========================================
    // CARGAR GRÁFICO
    // ==========================================
    $.ajax({
        url: '{{ route("grafico") }}',
        type: 'GET',
        data: { anio: anio, mes: mes },
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta gráfico:', response);
            if (response.success && response.data.labels && response.data.labels.length > 0) {
                const ctx = document.getElementById('chartPie').getContext('2d');
                if (chartInstance) chartInstance.destroy();
                chartInstance = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.data.labels,
                        datasets: [{
                            data: response.data.values,
                            backgroundColor: ['#4f46e5', '#10b981', '#38bdf8', '#f59e0b'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { 
                                position: 'bottom', 
                                labels: { 
                                    boxWidth: 14, 
                                    padding: 12, 
                                    font: { 
                                        size: 13,
                                        weight: 'bold' 
                                    } 
                                } 
                            }
                        },
                        cutout: '55%'
                    }
                });
                $('#sinDatos').hide();
                console.log('✅ Gráfico actualizado');
            } else {
                $('#sinDatos').show();
                console.log('ℹ️ Sin datos para el gráfico');
            }
        },
        error: function(xhr) {
            console.error('❌ Error al cargar gráfico:', xhr);
            mostrarToast('❌ Error al cargar el gráfico', 'danger');
        }
    });
}

// ==========================================
// CARGAR EVOLUCIÓN MENSUAL
// ==========================================
function cargarEvolucion() {
    console.log('📈 Cargando evolución mensual...');
    $.ajax({
        url: '{{ route("evolucion") }}',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta evolución:', response);
            if (response.success && response.data.labels && response.data.labels.length > 0) {
                const ctx = document.getElementById('chartLine').getContext('2d');
                
                if (lineChartInstance) {
                    lineChartInstance.destroy();
                }
                
                lineChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.data.labels,
                        datasets: response.data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    padding: 4,
                                    font: { 
                                        size: 12,
                                        weight: 'bold' 
                                    },
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                titleFont: { size: 13 },
                                bodyFont: { size: 12 },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + 
                                            Number(context.parsed.y).toLocaleString('es-CL');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 280000000,
                                ticks: {
                                    font: { 
                                        size: 10,
                                        weight: 'bold' 
                                    },
                                    maxTicksLimit: 5,
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return (value / 1000000).toFixed(1) + 'M';
                                        } else if (value >= 1000) {
                                            return (value / 1000).toFixed(0) + 'K';
                                        }
                                        return value;
                                    }
                                },
                                grid: {
                                    drawBorder: false,
                                    color: 'rgba(0,0,0,0.04)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: { 
                                        size: 10,
                                        weight: 'bold' 
                                    },
                                    maxRotation: 0
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        elements: {
                            line: {
                                borderWidth: 1.5
                            },
                            point: {
                                radius: 2.5,
                                hoverRadius: 4
                            }
                        },
                        layout: {
                            padding: {
                                top: 2,
                                bottom: 2,
                                left: 2,
                                right: 2
                            }
                        }
                    }
                });
                
                $('#sinDatosLinea').hide();
                $('#totalMeses').text(response.data.labels.length + ' meses');
                console.log('✅ Evolución actualizada');
            } else {
                $('#sinDatosLinea').show();
                $('#totalMeses').text('0 meses');
            }
        },
        error: function(xhr) {
            console.error('❌ Error al cargar evolución:', xhr);
            $('#sinDatosLinea').show();
            mostrarToast('❌ Error al cargar la evolución', 'danger');
        }
    });
}

// ==========================================
// FUNCIONES DE APOYO
// ==========================================
function formatoMoneda(valor) {
    if (isNaN(valor) || valor === null || valor === undefined) {
        return '$0';
    }
    return '$' + Number(valor).toLocaleString('es-CL');
}

function mostrarToast(mensaje, tipo) {
    console.log('📢 Toast:', mensaje, tipo);
    const toastHtml = `
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-${tipo} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${mensaje}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    `;
    $('body').append(toastHtml);
    setTimeout(function() {
        $('.toast').toast('hide');
        setTimeout(function() {
            $('.toast').remove();
        }, 1000);
    }, 5000);
}

// ==========================================
// LIMPIAR CACHÉ
// ==========================================
function limpiarCache() {
    if (!confirm('¿Estás seguro de que quieres limpiar la caché?')) return;
    
    console.log('🧹 Limpiando caché...');
    $.ajax({
        url: '{{ route("cache.clear") }}',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('📊 Respuesta limpieza caché:', response);
            if (response.success) {
                mostrarToast('✅ Caché limpiada correctamente', 'success');
                cargarMeses();
                cargarEvolucion();
            } else {
                mostrarToast('❌ ' + response.message, 'danger');
            }
        },
        error: function(xhr) {
            console.error('❌ Error al limpiar caché:', xhr);
            mostrarToast('❌ Error al limpiar la caché', 'danger');
        }
    });
}

// ==========================================
// CARGA INICIAL
// ==========================================
$(document).ready(function() {
    console.log('🚀 Dashboard inicializado');
    cargarMeses();
    cargarEvolucion();

    $('#selectorPeriodo').on('change', function() {
        const valor = $(this).val();
        if (valor) {
            const partes = valor.split('-');
            const anio = parseInt(partes[0]);
            const mes = parseInt(partes[1]);
            periodoActual = valor;
            cargarDatosPorPeriodo(anio, mes);
        }
    });
});

// Exponer funciones globalmente
window.cargarMeses = cargarMeses;
window.cargarDatosPorPeriodo = cargarDatosPorPeriodo;
window.cargarEvolucion = cargarEvolucion;
window.formatoMoneda = formatoMoneda;
window.mostrarToast = mostrarToast;
window.limpiarCache = limpiarCache;

console.log('✅ Dashboard scripts cargados');
</script>
@endpush
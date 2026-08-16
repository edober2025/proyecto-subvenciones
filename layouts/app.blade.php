<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - Subvenciones')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 64px;
        }
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #2d3436 0%, #1a1a2e 100%);
            color: #fff;
            padding: 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-brand h4 {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .sidebar-brand small {
            color: #a0aec0;
            font-size: 0.75rem;
        }
        .sidebar-menu {
            padding: 1rem 0;
        }
        .sidebar-menu .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e0;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        .sidebar-menu .nav-item:hover,
        .sidebar-menu .nav-item.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left-color: #4f46e5;
        }
        .sidebar-menu .nav-item i {
            width: 22px;
            text-align: center;
            font-size: 1.1rem;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .top-header {
            height: var(--header-height);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
        }
        .content-wrapper {
            padding: 2rem;
        }
        .bg-gradient-primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
        .bg-gradient-success { background: linear-gradient(135deg, #059669, #10b981); }
        .bg-gradient-info { background: linear-gradient(135deg, #0284c7, #38bdf8); }
        .bg-gradient-warning { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        .drop-zone {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8fafc;
        }
        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .table-custom th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-school me-2"></i>Subvenciones</h4>
            <small>Control Financiero</small>
        </div>
        <nav class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="nav-item active">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="#" class="nav-item" id="sidebarUploadBtn" onclick="abrirModalDesdeSidebar()">
                <i class="fas fa-upload"></i> Subir Archivo
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-history"></i> Historial
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i> Configuración
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- HEADER -->
        <header class="top-header">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-bars d-md-none fs-4" style="cursor:pointer;" onclick="toggleSidebar()"></i>
                <h5 class="mb-0 fw-bold d-none d-sm-block">Dashboard</h5>
                <span class="badge bg-primary rounded-pill">Junio 2026</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-bell text-secondary fs-5" style="cursor:pointer;"></i>
                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff&size=32" class="rounded-circle" alt="Avatar">
                    <span class="fw-semibold d-none d-sm-block">Admin</span>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL DE CONFIRMACIÓN PARA REEMPLAZAR DATOS                   -->
    <!-- ============================================================ -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Reemplazo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage" class="fs-5">⚠️ Ya existen datos para este mes. ¿Quieres reemplazarlos?</p>
                    <p class="text-muted small" id="confirmDetail">Registros existentes: 0</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="confirmNo" data-bs-dismiss="modal" onclick="cancelarReemplazo()">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-warning rounded-pill px-4" id="confirmYes" onclick="confirmarReemplazo()">
                        <i class="fas fa-check me-2"></i>Sí, Reemplazar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 1. PRIMERO: JQUERY (SIEMPRE PRIMERO)                          -->
    <!-- ============================================================ -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- ============================================================ -->
    <!-- 2. SEGUNDO: BOOTSTRAP (DEPENDE DE JQUERY)                    -->
    <!-- ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- ============================================================ -->
    <!-- 3. TERCERO: CHART.JS Y OTRAS LIBRERÍAS                       -->
    <!-- ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ============================================================ -->
    <!-- 4. CUARTO: TU CÓDIGO PERSONALIZADO (AQUÍ YA FUNCIONA $)      -->
    <!-- ============================================================ -->
    <script>
        function toggleSidebar() {
            $('.sidebar').toggleClass('active');
        }

        function abrirModalDesdeSidebar() {
            console.log('🔘 Abrir modal desde sidebar');
            if (typeof bootstrap !== 'undefined') {
                try {
                    var modal = new bootstrap.Modal(document.getElementById('uploadModal'));
                    modal.show();
                    console.log('✅ Modal abierto');
                } catch (e) {
                    console.error('❌ Error al abrir modal:', e);
                }
            }
        }

        function confirmarReemplazo() {
            console.log('✅ Usuario confirmó reemplazo');
            var modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            if (modal) modal.hide();
            if (window.formDataPendiente) {
                window.formDataPendiente.append('confirmar', '1');
                if (typeof enviarConConfirmacion === 'function') {
                    enviarConConfirmacion(window.formDataPendiente);
                }
            }
        }

        function cancelarReemplazo() {
            console.log('❌ Usuario canceló reemplazo');
            window.pendienteConfirmacion = false;
            window.formDataPendiente = null;
            var modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            if (modal) modal.hide();
            if (typeof mostrarToast === 'function') {
                mostrarToast('⏹️ Operación cancelada', 'warning');
            }
        }

        $(document).ready(function() {
            console.log('=========================================');
            console.log('✅ jQuery cargado:', typeof $ !== 'undefined');
            console.log('✅ Bootstrap cargado:', typeof bootstrap !== 'undefined');
            console.log('✅ Chart.js cargado:', typeof Chart !== 'undefined');
            console.log('=========================================');

            $(document).on('click', function(e) {
                if ($(window).width() < 768) {
                    if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.fa-bars').length) {
                        $('.sidebar').removeClass('active');
                    }
                }
            });
        });

        console.log('✅ Scripts cargados correctamente');
    </script>
    
    @stack('scripts')
</body>
</html>
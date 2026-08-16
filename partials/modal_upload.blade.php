<!-- MODAL DE SUBIDA DE ARCHIVOS -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="uploadModalLabel">
                    <i class="fas fa-upload text-primary me-2"></i>Subir Archivo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formUpload" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="drop-zone" id="dropZone">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3" id="dropZoneIcon"></i>
                        <h6 class="fw-bold">Arrastra tu archivo aquí</h6>
                        <p class="text-muted small mb-2">o haz clic para seleccionar</p>
                        <label for="fileInput" class="btn btn-outline-primary rounded-pill">
                            <i class="fas fa-folder-open me-2"></i>Seleccionar Archivo
                        </label>
                        <input type="file" name="archivo" accept=".xls,.xlsx" class="d-none" id="fileInput" required>
                        <span class="badge bg-light text-dark rounded-pill d-block mt-2">.xls .xlsx</span>
                    </div>
                    <div id="fileName" class="text-center text-primary fw-semibold mt-2 d-none"></div>

                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Año</label>
                            <input type="number" name="anio" value="{{ date('Y') }}" 
                                   class="form-control form-control-lg rounded-3" min="2020" max="2030" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mes</label>
                            <select name="mes" class="form-select form-select-lg rounded-3" required>
                                @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $nombre)
                                    <option value="{{ $i+1 }}" {{ $i+1 == date('n') ? 'selected' : '' }}>
                                        {{ $nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="progressBar" class="mt-3 d-none">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 style="width:0%; transition: width 0.3s;"></div>
                        </div>
                        <small class="text-muted" id="progressText">0%</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="btnSubir">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Subir Archivo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
console.log('✅ Modal script cargado');

var fileInput = document.getElementById('fileInput');
var btnSubir = document.getElementById('btnSubir');
var fileName = document.getElementById('fileName');
var dropZoneIcon = document.getElementById('dropZoneIcon');
var progressBar = document.getElementById('progressBar');
var progressBarInner = document.querySelector('#progressBar .progress-bar');
var progressText = document.getElementById('progressText');
var formUpload = document.getElementById('formUpload');
var uploadModal = document.getElementById('uploadModal');

// Obtener token CSRF
var csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    csrfToken = csrfToken.getAttribute('content');
} else {
    console.error('❌ CSRF token no encontrado');
    csrfToken = '';
}

console.log('✅ CSRF Token:', csrfToken ? 'Presente' : 'No encontrado');

// Variables globales para confirmación
window.pendienteConfirmacion = false;
window.formDataPendiente = null;

// ==========================================
// SELECCIONAR ARCHIVO
// ==========================================
if (fileInput) {
    fileInput.addEventListener('change', function() {
        var file = this.files[0];
        console.log('📄 Archivo seleccionado:', file ? file.name : 'Ninguno');
        if (file) {
            fileName.textContent = '📄 ' + file.name;
            fileName.classList.remove('d-none');
            dropZoneIcon.classList.remove('fa-cloud-upload-alt');
            dropZoneIcon.classList.add('fa-file-excel', 'text-success');
        } else {
            fileName.classList.add('d-none');
            dropZoneIcon.classList.remove('fa-file-excel', 'text-success');
            dropZoneIcon.classList.add('fa-cloud-upload-alt');
        }
    });
}

// ==========================================
// DRAG & DROP
// ==========================================
var dropZone = document.getElementById('dropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        var file = e.dataTransfer.files[0];
        if (file) {
            fileInput.files = e.dataTransfer.files;
            fileName.textContent = '📄 ' + file.name;
            fileName.classList.remove('d-none');
            dropZoneIcon.classList.remove('fa-cloud-upload-alt');
            dropZoneIcon.classList.add('fa-file-excel', 'text-success');
        }
    });
}

// ==========================================
// MOSTRAR TOAST
// ==========================================
function mostrarToast(mensaje, tipo) {
    console.log('📢 Toast:', mensaje, tipo);
    var toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="toast align-items-center text-white bg-${tipo} border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(function() {
        if (toast.parentNode) toast.remove();
    }, 5000);
}

// ==========================================
// MOSTRAR MODAL DE CONFIRMACIÓN
// ==========================================
function mostrarConfirmacion(mensaje, detalles) {
    console.log('🔔 Mostrando modal de confirmación');
    
    var msgElement = document.getElementById('confirmMessage');
    var detailElement = document.getElementById('confirmDetail');
    
    if (msgElement) msgElement.textContent = mensaje;
    if (detailElement) detailElement.textContent = 'Registros existentes: ' + (detalles || 0);
    
    var confirmModalElement = document.getElementById('confirmModal');
    if (confirmModalElement) {
        var confirmModal = new bootstrap.Modal(confirmModalElement);
        confirmModal.show();
        console.log('✅ Modal de confirmación mostrado');
    } else {
        console.error('❌ Modal de confirmación no encontrado');
        if (confirm(mensaje)) {
            if (window.formDataPendiente) {
                window.formDataPendiente.append('confirmar', '1');
                enviarConConfirmacion(window.formDataPendiente);
            }
        }
    }
}

// ==========================================
// FUNCIÓN PARA ENVIAR CON CONFIRMACIÓN
// ==========================================
function enviarConConfirmacion(formData) {
    console.log('📤 Enviando con confirmación...');
    
    progressBar.classList.remove('d-none');
    progressBarInner.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
    btnSubir.disabled = true;
    btnSubir.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("upload") }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            var pct = Math.round((e.loaded / e.total) * 100);
            progressBarInner.style.width = pct + '%';
            if (progressText) progressText.textContent = pct + '%';
        }
    });

    xhr.onload = function() {
        console.log('✅ Petición completada. Status:', xhr.status);
        if (xhr.status === 419) {
            mostrarToast('⚠️ La sesión expiró. Recarga la página e intenta de nuevo.', 'warning');
            resetProgressBar();
            return;
        }
        
        try {
            var response = JSON.parse(xhr.responseText);
            if (response.requires_confirmation) {
                console.log('⚠️ Se requiere confirmación para reemplazar');
                window.pendienteConfirmacion = true;
                window.formDataPendiente = formData;
                mostrarConfirmacion(response.message, response.data?.registros_existentes);
                resetProgressBar();
                return;
            }
            
            if (response.success) {
                var modal = bootstrap.Modal.getInstance(uploadModal);
                if (modal) modal.hide();
                if (typeof cargarMeses === 'function') cargarMeses();
                mostrarToast('✅ ' + response.message, 'success');
            } else {
                mostrarToast('❌ ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('❌ Error al parsear respuesta:', error);
            mostrarToast('❌ Error al procesar la respuesta', 'danger');
        }
        resetProgressBar();
    };

    xhr.onerror = function() {
        console.error('❌ Error en la petición');
        mostrarToast('❌ Error al subir el archivo', 'danger');
        resetProgressBar();
    };

    xhr.send(formData);
}

function resetProgressBar() {
    progressBar.classList.add('d-none');
    progressBarInner.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
    btnSubir.disabled = false;
    btnSubir.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i>Subir Archivo';
}

// ==========================================
// BOTÓN SUBIR ARCHIVO
// ==========================================
if (btnSubir) {
    btnSubir.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('🔘 Click en botón "Subir Archivo"');

        if (!fileInput.files || fileInput.files.length === 0) {
            mostrarToast('⚠️ Por favor, selecciona un archivo', 'warning');
            return;
        }

        var formData = new FormData(formUpload);
        var btn = this;

        console.log('📄 Archivo:', fileInput.files[0].name);
        console.log('📅 Año:', document.querySelector('input[name="anio"]').value);
        console.log('📅 Mes:', document.querySelector('select[name="mes"]').value);

        progressBar.classList.remove('d-none');
        progressBarInner.style.width = '0%';
        if (progressText) progressText.textContent = '0%';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route("upload") }}', true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 100);
                progressBarInner.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';
            }
        });

        xhr.onload = function() {
            console.log('✅ Petición completada. Status:', xhr.status);
            if (xhr.status === 419) {
                mostrarToast('⚠️ La sesión expiró. Recarga la página e intenta de nuevo.', 'warning');
                resetProgressBar();
                return;
            }
            
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.requires_confirmation) {
                    console.log('⚠️ Se requiere confirmación para reemplazar');
                    window.pendienteConfirmacion = true;
                    window.formDataPendiente = formData;
                    mostrarConfirmacion(response.message, response.data?.registros_existentes);
                    resetProgressBar();
                    return;
                }
                
                if (response.success) {
                    var modal = bootstrap.Modal.getInstance(uploadModal);
                    if (modal) modal.hide();
                    if (typeof cargarMeses === 'function') cargarMeses();
                    mostrarToast('✅ ' + response.message, 'success');
                } else {
                    mostrarToast('❌ ' + response.message, 'danger');
                }
            } catch (error) {
                console.error('❌ Error al parsear respuesta:', error);
                mostrarToast('❌ Error al procesar la respuesta', 'danger');
            }
            resetProgressBar();
        };

        xhr.onerror = function() {
            console.error('❌ Error en la petición');
            mostrarToast('❌ Error al subir el archivo', 'danger');
            resetProgressBar();
        };

        xhr.send(formData);
    });
}

// ==========================================
// CONFIRMACIÓN - SÍ (JavaScript puro)
// ==========================================
var confirmYesBtn = document.getElementById('confirmYes');
if (confirmYesBtn) {
    confirmYesBtn.addEventListener('click', function() {
        console.log('✅ Usuario confirmó reemplazar');
        var modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
        if (modal) modal.hide();
        if (window.formDataPendiente) {
            window.formDataPendiente.append('confirmar', '1');
            enviarConConfirmacion(window.formDataPendiente);
        } else {
            console.warn('⚠️ No hay datos pendientes para enviar');
        }
    });
}

// ==========================================
// CONFIRMACIÓN - NO (JavaScript puro)
// ==========================================
var confirmNoBtn = document.getElementById('confirmNo');
if (confirmNoBtn) {
    confirmNoBtn.addEventListener('click', function() {
        console.log('❌ Usuario canceló reemplazo');
        window.pendienteConfirmacion = false;
        window.formDataPendiente = null;
        var modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
        if (modal) modal.hide();
        mostrarToast('⏹️ Operación cancelada', 'warning');
    });
}

console.log('✅ Modal script inicializado correctamente');
</script>
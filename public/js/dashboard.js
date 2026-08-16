$(document).ready(function() {
    cargarDatos();

    // Subir archivo con AJAX
    $('#btnSubir').click(function(e) {
        e.preventDefault();
        let formData = new FormData($('#formUpload')[0]);

        $('#progressBar').removeClass('d-none');
        $('#btnSubir').prop('disabled', true);

        $.ajax({
            url: '/upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                let xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percent = Math.round((e.loaded / e.total) * 100);
                        $('.progress-bar').css('width', percent + '%').text(percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $('#uploadModal').modal('hide');
                    cargarDatos(); // Recargar dashboard
                    mostrarToast('✅ ' + response.message);
                }
            },
            error: function(xhr) {
                mostrarToast('❌ ' + xhr.responseJSON?.message || 'Error al subir');
            },
            complete: function() {
                $('#progressBar').addClass('d-none');
                $('.progress-bar').css('width', '0%');
                $('#btnSubir').prop('disabled', false);
            }
        });
    });
});

function cargarDatos() {
    // Cargar totales y tabla
    $.get('/resumen', function(response) {
        if (response.success) {
            const d = response.data;
            $('#totalBase').text('$' + d.total_base.toLocaleString('es-CL'));
            $('#totalGeneral').text('$' + d.general.toLocaleString('es-CL'));
            $('#totalPieCurso').text('$' + d.pie_curso.toLocaleString('es-CL'));
            $('#totalPieAlumnos').text('$' + d.pie_alumnos.toLocaleString('es-CL'));
        }
    });

    $.get('/detalle', function(response) {
        if (response.success) {
            let html = '';
            response.data.forEach(row => {
                html += `<tr>
                    <td>${row.curso}</td>
                    <td class="text-end">$${row.total_base.toLocaleString('es-CL')}</td>
                    <td class="text-end">$${row.general.toLocaleString('es-CL')}</td>
                    <td class="text-end">$${row.pie_curso.toLocaleString('es-CL')}</td>
                    <td class="text-end">$${row.pie_alumnos.toLocaleString('es-CL')}</td>
                </tr>`;
            });
            $('#cuerpoTabla').html(html);
        }
    });
}

function mostrarToast(mensaje) {
    // Implementar toast con MD Bootstrap
}
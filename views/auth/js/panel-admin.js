$(document).ready(function() {
    // Variables globales
    let datosEstudiantes = null;
    let datosDocentes = null;
    let primeraCargar = true;
    let votosCandidatosActuales = [];
    let votosPersonerosActuales = [];
    let votosRepresentantesActuales = [];
    
    // Cargar datos iniciales
    cargarDatosEstadisticas();
    
    // Actualizar datos cada 30 segundos
    setInterval(cargarDatosEstadisticas, 30000);
    
    // Función principal para cargar datos
    function cargarDatosEstadisticas() {
        console.log("Cargando datos de estadísticas...");
        
        // Obtener datos de estudiantes
        $.ajax({
            url: '/Login/api/estadisticas.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(data) {
                console.log("Datos de estudiantes recibidos:", data);
                datosEstudiantes = data;
                actualizarEstadisticasGenerales(data);
                mostrarVotosRecientesEstudiantes(data.votosRecientes);
                
                // Verificar si tenemos datos de personeros y representantes
                const personeros = data.personeros || [];
                const representantes = data.representantes || [];
                
                console.log("Personeros recibidos:", personeros);
                console.log("Representantes recibidos:", representantes);
                
                // Verificar si han cambiado los datos
                const personerosCambiados = hanCambiadoVotos(votosPersonerosActuales, personeros);
                const representantesCambiados = hanCambiadoVotos(votosRepresentantesActuales, representantes);
                
                if (primeraCargar || personerosCambiados || representantesCambiados) {
                    // Actualizar datos actuales
                    votosPersonerosActuales = personeros;
                    votosRepresentantesActuales = representantes;
                    
                    // Mostrar tablas de votos
                    mostrarVotosPorTipoCandidato(personeros, 'personeros');
                    mostrarVotosPorTipoCandidato(representantes, 'representantes');
                    
                    // Crear gráficos separados
                    crearGraficosPorTipo(personeros, 'personeros');
                    crearGraficosPorTipo(representantes, 'representantes');
                    
                    primeraCargar = false;
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos de estudiantes:", error);
                console.log("Respuesta:", xhr.responseText);
                if (primeraCargar) {
                    mostrarMensajeError();
                    primeraCargar = false;
                }
            }
        });
        
        // Obtener datos de docentes
        $.ajax({
            url: '/Login/api/estadisticas_docentes.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(data) {
                console.log("Datos de docentes recibidos:", data);
                datosDocentes = data;
                actualizarEstadisticasDocentes(data);
                mostrarVotosRecientesDocentes(data.votosRecientes);
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos de docentes:", error);
                console.log("Respuesta:", xhr.responseText);
                if (primeraCargar) {
                    mostrarMensajeError();
                }
            }
        });
        
        // Obtener datos de administrativos
        $.ajax({
            url: '/Login/api/estadisticas_administrativos.php',
            type: 'GET',
            dataType: 'text', // Cambiar a text para manejar warnings de PHP
            cache: false,
            success: function(response) {
                try {
                    // Intentar extraer el JSON de la respuesta, ignorando warnings de PHP
                    let jsonStart = response.indexOf('{');
                    if (jsonStart !== -1) {
                        let jsonString = response.substring(jsonStart);
                        let data = JSON.parse(jsonString);
                        console.log("Datos de administrativos recibidos:", data);
                        mostrarVotosRecientesAdministrativos(data.votosRecientes);
                    } else {
                        throw new Error("No se encontró JSON válido en la respuesta");
                    }
                } catch (e) {
                    console.error("Error al procesar datos de administrativos:", e);
                    console.log("Respuesta completa:", response);
                    $('#votos-recientes-administrativos').html('<li class="list-group-item text-center"><p class="text-muted mb-0">Error al cargar datos</p></li>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos de administrativos:", error);
                console.log("Respuesta:", xhr.responseText);
                $('#votos-recientes-administrativos').html('<li class="list-group-item text-center"><p class="text-muted mb-0">Error al cargar datos</p></li>');
            }
        });
    }
    
    // Función para mostrar mensaje de error
    function mostrarMensajeError() {
        $('#votos-personeros-container').html('<p class="text-danger text-center my-3">Error al cargar datos</p>');
        $('#votos-representantes-container').html('<p class="text-danger text-center my-3">Error al cargar datos</p>');
        $('#votos-recientes-estudiantes').html('<li class="list-group-item text-center"><p class="text-danger mb-0">Error al cargar datos</p></li>');
        $('#votos-recientes-docentes').html('<li class="list-group-item text-center"><p class="text-danger mb-0">Error al cargar datos</p></li>');
        $('#votos-recientes-administrativos').html('<li class="list-group-item text-center"><p class="text-danger mb-0">Error al cargar datos</p></li>');
    }
    
    // Función para actualizar estadísticas generales
    function actualizarEstadisticasGenerales(data) {
        if (!data || !data.estadisticas) {
            console.error("Datos de estadísticas inválidos:", data);
            return;
        }
        
        // Actualizar estadísticas generales
        $("#total-estudiantes").text(formatearNumero(data.estadisticas.totalEstudiantes || 0));
        $("#total-estudiantes-stats").text(formatearNumero(data.estadisticas.totalEstudiantes || 0));
        $("#total-votos").text(formatearNumero(data.estadisticas.totalVotos || 0));
        $("#total-votos-estudiantes").text(formatearNumero(data.estadisticas.totalVotos || 0));
        $("#total-candidatos").text(formatearNumero(data.estadisticas.totalCandidatos || 0));
        
        // Formatear porcentaje
        const porcentaje = data.estadisticas.porcentajeParticipacion || 0;
        $("#porcentaje-participacion").text(porcentaje.toFixed(1) + "%");
        $("#porcentaje-participacion-estudiantes").text(porcentaje.toFixed(1) + "%");
        
        // Actualizar votos en blanco
        if (data.estadisticas.votosBlanco !== undefined) {
            $("#total-votos-blanco-estudiantes").text(formatearNumero(data.estadisticas.votosBlanco));
        }
    }
    
    // Función para actualizar estadísticas de docentes
    function actualizarEstadisticasDocentes(data) {
        if (!data || !data.estadisticas) {
            console.error("Datos de estadísticas de docentes inválidos:", data);
            return;
        }
        
        $("#total-docentes").text(formatearNumero(data.estadisticas.total_docentes || 0));
        $("#total-votos-docentes").text(formatearNumero(data.estadisticas.total_votos || 0));
    }
    
    // Función para mostrar votos recientes de estudiantes
    function mostrarVotosRecientesEstudiantes(votosRecientes) {
        if (!votosRecientes || !Array.isArray(votosRecientes) || votosRecientes.length === 0) {
            $('#votos-recientes-estudiantes').html('<li class="list-group-item text-center"><p class="text-muted mb-0">No hay actividad reciente</p></li>');
            return;
        }
        
        let html = '';
        votosRecientes.forEach(function(voto) {
            if (!voto.nombre_estudiante && !voto.id_estudiante) return;
            
            const nombreEstudiante = voto.nombre_estudiante || '';
            const apellidoEstudiante = voto.apellido_estudiante || '';
            const nombreCompleto = nombreEstudiante + (apellidoEstudiante ? ' ' + apellidoEstudiante : '');
            
            const tipoVoto = voto.tipo_voto || 'PERSONERO';
            const badgeClass = tipoVoto === 'PERSONERO' ? 'primary' : 'success';
            const tipoTexto = tipoVoto === 'PERSONERO' ? 'Personero' : 'Representante';
            
            let textoVoto = 'Votó';
            if (voto.voto_blanco || voto.id_candidato === null) {
                textoVoto += ' en blanco';
            } else if (voto.candidato_nombre) {
                textoVoto += ' por ' + escapeHTML(voto.candidato_nombre);
                if (voto.candidato_apellido) {
                    textoVoto += ' ' + escapeHTML(voto.candidato_apellido);
                }
            }
            
            const hora = formatearHora(voto.fecha_voto);
            
            html += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                            <h6 class="mb-0">${escapeHTML(nombreCompleto)}</h6>
                            <small class="text-muted">${textoVoto}</small>
                    </div>
                    <small class="text-muted">${hora}</small>
                </div>
                </li>
            `;
        });
        
        $('#votos-recientes-estudiantes').html(html);
    }
    
    // Función para mostrar votos recientes de docentes
    function mostrarVotosRecientesDocentes(votosRecientes) {
        if (!votosRecientes || !Array.isArray(votosRecientes) || votosRecientes.length === 0) {
            $('#votos-recientes-docentes').html('<li class="list-group-item text-center"><p class="text-muted mb-0">No hay actividad reciente</p></li>');
            return;
        }
        
        let html = '';
        votosRecientes.forEach(function(voto) {
            // Verificar que tenemos un nombre de docente
            if (!voto.nombre_docente && !voto.nombre_completo) return;
            
            // Usar nombre_completo si está disponible, de lo contrario usar nombre_docente
            const nombreCompleto = voto.nombre_completo || voto.nombre_docente || 'Docente';
            
            // Obtener la información del rol
            const rol = voto.rol || 'Docente';
            
            // Obtener información sobre el voto
            const infoVoto = voto.info_voto || '';
            
            // Obtener la fecha formateada
            const hora = voto.fecha || formatearHora(voto.fecha_voto) || 'Reciente';
            
            html += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">${escapeHTML(nombreCompleto)}</h6>
                        <small class="text-muted">${escapeHTML(rol)}</small>
                        ${infoVoto ? `<small class="d-block text-primary">${escapeHTML(infoVoto)}</small>` : ''}
                    </div>
                    <small class="text-muted">${hora}</small>
                </div>
                </li>
            `;
        });
        
        $('#votos-recientes-docentes').html(html);
        
        // Log para depuración
        console.log("Votos recientes de docentes mostrados:", votosRecientes.length);
    }
    
    // Función para mostrar votos recientes de administrativos
    function mostrarVotosRecientesAdministrativos(votosRecientes) {
        if (!votosRecientes || !Array.isArray(votosRecientes) || votosRecientes.length === 0) {
            $('#votos-recientes-administrativos').html('<li class="list-group-item text-center"><p class="text-muted mb-0">No hay actividad reciente</p></li>');
            return;
        }
        
        let html = '';
        votosRecientes.forEach(function(voto) {
            // Verificar que tenemos un nombre de administrativo
            if (!voto.nombre_administrativo && !voto.nombre_completo) return;
            
            // Usar nombre_completo si está disponible, de lo contrario usar nombre_administrativo
            const nombreCompleto = voto.nombre_completo || voto.nombre_administrativo || 'Administrativo';
            
            // Obtener la información del rol
            const rol = voto.rol || 'Administrativo';
            
            // Obtener información sobre el voto
            const infoVoto = voto.info_voto || '';
            
            // Obtener la fecha formateada
            const hora = voto.fecha || formatearHora(voto.fecha_voto) || 'Reciente';
            
            html += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">${escapeHTML(nombreCompleto)}</h6>
                        <small class="text-muted">${escapeHTML(rol)}</small>
                        ${infoVoto ? `<small class="d-block text-primary">${escapeHTML(infoVoto)}</small>` : ''}
                    </div>
                    <small class="text-muted">${hora}</small>
                </div>
                </li>
            `;
        });
        
        $('#votos-recientes-administrativos').html(html);
        
        // Log para depuración
        console.log("Votos recientes de administrativos mostrados:", votosRecientes.length);
    }
    
    // Función para mostrar votos por tipo de candidato
    function mostrarVotosPorTipoCandidato(candidatos, tipo) {
        const containerId = `votos-${tipo}-container`;
        
        if (!candidatos || !Array.isArray(candidatos) || candidatos.length === 0) {
            $(`#${containerId}`).html('<p class="text-muted text-center my-3">No hay datos disponibles</p>');
            return;
        }
        
        let html = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Candidato</th>
                        <th class="text-end">Votos</th>
                        <th class="text-end">%</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        candidatos.forEach(function(candidato) {
            const nombre = candidato.nombre || '';
            const votos = candidato.total_votos || 0;
            const porcentaje = candidato.porcentaje || 0;
            
            html += `
                <tr>
                    <td>${escapeHTML(nombre)}</td>
                    <td class="text-end">${formatearNumero(votos)}</td>
                    <td class="text-end">${porcentaje.toFixed(1)}%</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        $(`#${containerId}`).html(html);
    }

    // Función para crear gráficos por tipo de candidato
    function crearGraficosPorTipo(candidatos, tipo) {
        if (!candidatos || !Array.isArray(candidatos) || candidatos.length === 0) return;
        
        const labels = candidatos.map(c => c.nombre || '');
        const votos = candidatos.map(c => c.total_votos || 0);
        
        // Gráfico circular
        const ctxCircular = document.getElementById(`grafico-circular-${tipo}`).getContext('2d');
        if (window[`graficoCircular${tipo}`]) {
            window[`graficoCircular${tipo}`].destroy();
        }
        window[`graficoCircular${tipo}`] = new Chart(ctxCircular, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: votos,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    title: {
                        display: true,
                        text: `Distribución de Votos - ${tipo === 'personeros' ? 'Personeros' : 'Representantes'}`
                    }
                }
            }
        });

        // Gráfico de barras
        const ctxBarras = document.getElementById(`grafico-barras-${tipo}`).getContext('2d');
        if (window[`graficoBarras${tipo}`]) {
            window[`graficoBarras${tipo}`].destroy();
        }
        window[`graficoBarras${tipo}`] = new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Votos',
                    data: votos,
                    backgroundColor: tipo === 'personeros' ? '#36A2EB' : '#FF6384'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: `Votos por ${tipo === 'personeros' ? 'Personero' : 'Representante'}`
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    // Función para verificar si han cambiado los votos
    function hanCambiadoVotos(votosAnteriores, votosNuevos) {
        if (!votosAnteriores || !votosNuevos) return true;
        if (votosAnteriores.length !== votosNuevos.length) return true;
        
        return votosAnteriores.some((votoAnterior, index) => {
            const votoNuevo = votosNuevos[index];
            return votoAnterior.total_votos !== votoNuevo.total_votos;
        });
    }
    
    // Función para formatear números
    function formatearNumero(numero) {
        return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    
    // Función para formatear hora
    function formatearHora(fecha) {
        if (!fecha) return '';
        const date = new Date(fecha);
        return date.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Función para escapar HTML
    function escapeHTML(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}); 
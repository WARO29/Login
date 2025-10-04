$(document).ready(function() {
    // Variables para almacenar datos
    let datosEstudiantes = {};
    let datosDocentes = {};
    let pingInterval; // Para almacenar el intervalo de ping

    // Iniciar el intervalo de ping para mantener la sesión activa
    iniciarPing();
    
    // Cargar datos iniciales
    cargarDatosEstadisticas();
    
    // Configurar actualizaciones periódicas
    setInterval(cargarDatosEstadisticas, 30000); // Actualizar cada 30 segundos
    
    // Función para cargar datos de estadísticas
    function cargarDatosEstadisticas() {
        console.log("Cargando datos de estadísticas...");
        
        // Obtener datos de estudiantes
        $.ajax({
            url: '/Login/api/estadisticas.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log("Datos de estudiantes recibidos:", data);
                datosEstudiantes = data;
                actualizarEstadisticasGenerales(data);
                mostrarVotosRecientesEstudiantes(data.votosRecientes);
                
                // Si hay datos de votos por candidato, mostrarlos y crear gráficos
                if (data.votosCandidatos && data.votosCandidatos.length > 0) {
                    console.log("Datos de candidatos a mostrar:", data.votosCandidatos);
                    // Verificar que los datos tengan el formato correcto
                    const datosValidos = data.votosCandidatos.map(candidato => {
                        // Asegurar que total_votos es un número
                        candidato.total_votos = parseInt(candidato.total_votos || 0);
                        return candidato;
                    });
                    console.log("Datos procesados:", datosValidos);
                    
                    mostrarVotosPorCandidato(datosValidos);
                    crearGraficos(datosValidos);
                } else {
                    // Si no hay datos de votos por candidato, mostrar mensaje
                    console.error("No hay datos de votos por candidato disponibles:", data);
                    $('#votos-candidatos-container').html('<p class="text-muted text-center my-3">No hay datos disponibles</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos de estudiantes:", error);
                console.log("Respuesta:", xhr.responseText);
                mostrarMensajeError();
            }
        });
        
        // Obtener datos de docentes
        $.ajax({
            url: '/Login/api/estadisticas_docentes.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log("Datos de docentes recibidos:", data);
                datosDocentes = data;
                actualizarEstadisticasDocentes(data);
                mostrarVotosRecientesDocentes(data.votosRecientes);
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos de docentes:", error);
                console.log("Respuesta:", xhr.responseText);
                mostrarMensajeError();
            }
        });
    }
    
    // Función para mostrar mensaje de error
    function mostrarMensajeError() {
        $('#votos-candidatos-container').html('<p class="text-danger text-center my-3">Error al cargar datos</p>');
        $('#votos-recientes-estudiantes').html('<li class="list-group-item text-center"><p class="text-danger mb-0">Error al cargar datos</p></li>');
        $('#votos-recientes-docentes').html('<li class="list-group-item text-center"><p class="text-danger mb-0">Error al cargar datos</p></li>');
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
        
        // Actualizar votos en blanco si están disponibles
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
        
        // Actualizar estadísticas de docentes
        $("#total-docentes").text(formatearNumero(data.estadisticas.total_docentes || 0));
        $("#total-votos-docentes").text(formatearNumero(data.estadisticas.total_votos || 0));
        
        // Actualizar información específica de docentes
        if ($("#total-votos-blanco-docentes").length) {
            $("#total-votos-blanco-docentes").text(formatearNumero(data.estadisticas.votos_blancos || 0));
        }
        if ($("#porcentaje-participacion-docentes").length) {
            $("#porcentaje-participacion-docentes").text((data.estadisticas.participacion || 0).toFixed(1) + "%");
        }
    }
    
    // Función para mostrar votos por candidato
    function mostrarVotosPorCandidato(votosCandidatos) {
        console.log("Mostrando votos por candidato:", votosCandidatos);
        
        if (!votosCandidatos || votosCandidatos.length === 0) {
            $('#votos-candidatos-container').html('<p class="text-muted text-center my-3">No hay datos disponibles</p>');
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>Candidato</th><th class="text-end">Votos</th></tr></thead><tbody>';
        
        // Depurar cada candidato y su número de votos
        votosCandidatos.forEach(function(candidato) {
            console.log("Datos del candidato:", JSON.stringify(candidato));
            
            html += '<tr>';
            const nombreCandidato = candidato.nombre || candidato.nombre_candidato || 'Candidato';
            const apellidoCandidato = candidato.apellido || candidato.apellido_candidato || '';
            
            // Convertir total_votos a número si es string o null
            const totalVotos = parseInt(candidato.total_votos || 0);
            console.log(`Candidato: ${nombreCandidato} ${apellidoCandidato}, Votos: ${totalVotos}`);
            
            html += `<td>${escapeHTML(nombreCandidato)} ${escapeHTML(apellidoCandidato)}</td>`;
            html += `<td class="text-end fw-bold">${formatearNumero(totalVotos)}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        $('#votos-candidatos-container').html(html);
    }
    
    // Función para crear gráficos con Chart.js
    function crearGraficos(votosCandidatos) {
        if (!votosCandidatos || votosCandidatos.length === 0) {
            return;
        }
        
        // Preparar los datos para los gráficos
        const nombres = votosCandidatos.map(c => {
            const nombre = c.nombre || c.nombre_candidato || '';
            const apellido = c.apellido || c.apellido_candidato || '';
            return nombre + (apellido ? ' ' + apellido : '');
        });
        const votos = votosCandidatos.map(c => parseInt(c.total_votos || 0));
        const colores = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
        ];
        
        // Crear gráfico circular
        const ctxCircular = document.getElementById('grafico-circular');
        if (ctxCircular) {
            try {
                // Destruir el gráfico existente si existe
                if (window.chartCircular) {
                    window.chartCircular.destroy();
                }
                
                // Crear nuevo gráfico
                window.chartCircular = new Chart(ctxCircular, {
                    type: 'pie',
                    data: {
                        labels: nombres,
                        datasets: [{
                            data: votos,
                            backgroundColor: colores,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 15
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error("Error al crear gráfico circular:", error);
            }
        }
        
        // Crear gráfico de barras
        const ctxBarras = document.getElementById('grafico-barras');
        if (ctxBarras) {
            try {
                // Destruir el gráfico existente si existe
                if (window.chartBarras) {
                    window.chartBarras.destroy();
                }
                
                // Crear nuevo gráfico
                window.chartBarras = new Chart(ctxBarras, {
                    type: 'bar',
                    data: {
                        labels: nombres,
                        datasets: [{
                            label: 'Votos',
                            data: votos,
                            backgroundColor: colores,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error("Error al crear gráfico de barras:", error);
            }
        }
    }
    
    // Función para mostrar los votos recientes de estudiantes
    function mostrarVotosRecientesEstudiantes(votosRecientes) {
        if (!votosRecientes || !Array.isArray(votosRecientes) || votosRecientes.length === 0) {
            $('#votos-recientes-estudiantes').html('<li class="list-group-item text-center"><p class="text-muted mb-0">No hay actividad reciente</p></li>');
            return;
        }
        
        let html = '';
        votosRecientes.forEach(function(voto) {
            // Verificar que el voto tiene los datos mínimos necesarios
            if (!voto.nombre_estudiante && !voto.id_estudiante) {
                return;
            }
            
            // Formatear el nombre completo del estudiante
            const nombreEstudiante = voto.nombre_estudiante || '';
            const apellidoEstudiante = voto.apellido_estudiante || '';
            const nombreCompleto = nombreEstudiante + (apellidoEstudiante ? ' ' + apellidoEstudiante : '');
            
            // Determinar el tipo de voto
            const tipoVoto = voto.tipo_voto || 'PERSONERO';
            const badgeClass = tipoVoto === 'PERSONERO' ? 'primary' : 'success';
            const tipoTexto = tipoVoto === 'PERSONERO' ? 'Personero' : 'Representante';
            
            // Determinar candidato o voto en blanco
            let textoVoto = 'Votó';
            if (voto.voto_blanco || voto.id_candidato === null) {
                textoVoto += ' en blanco';
            } else if (voto.candidato_nombre) {
                textoVoto += ' por ' + escapeHTML(voto.candidato_nombre);
                if (voto.candidato_apellido) {
                    textoVoto += ' ' + escapeHTML(voto.candidato_apellido);
                }
            }
            
            // Formatear la hora
            const hora = formatearHora(voto.fecha_voto);
            
            // Construir el elemento de la lista
            html += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-${badgeClass} me-2">${tipoTexto}</span>
                        <strong>${escapeHTML(nombreCompleto)}</strong>
                    </div>
                    <small class="text-muted">${hora}</small>
                </div>
                <small class="text-muted">${textoVoto}</small>
            </li>`;
        });
        
        $('#votos-recientes-estudiantes').html(html);
    }
    
    // Función para mostrar los votos recientes de docentes
    function mostrarVotosRecientesDocentes(votosRecientes) {
        if (!votosRecientes || !Array.isArray(votosRecientes) || votosRecientes.length === 0) {
            $('#votos-recientes-docentes').html('<li class="list-group-item text-center"><p class="text-muted mb-0">No hay actividad reciente</p></li>');
            return;
        }
        
        let html = '';
        votosRecientes.forEach(function(voto) {
            // Verificar que el voto tiene los datos mínimos necesarios
            if (!voto.nombre_docente && !voto.id_docente) {
                return;
            }
            
            // Formatear el nombre del docente
            const nombreDocente = voto.nombre_docente || 'Docente';
            
            // Determinar candidato o voto en blanco
            let textoVoto = 'Votó';
            if (voto.voto_blanco || voto.codigo_representante === null) {
                textoVoto += ' en blanco';
            } else if (voto.nombre_representante) {
                textoVoto += ' por ' + escapeHTML(voto.nombre_representante);
            }
            
            // Formatear la hora
            const hora = formatearHora(voto.fecha_voto);
            
            // Construir el elemento de la lista
            html += `
            <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-info me-2">Docente</span>
                        <strong>${escapeHTML(nombreDocente)}</strong>
                    </div>
                    <small class="text-muted">${hora}</small>
                </div>
                <small class="text-muted">${textoVoto}</small>
            </li>`;
        });
        
        $('#votos-recientes-docentes').html(html);
    }
    
    // Función para formatear la hora
    function formatearHora(fechaStr) {
        if (!fechaStr) return 'Reciente';
        try {
            const fecha = new Date(fechaStr);
            return fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return 'Reciente';
        }
    }
    
    // Función para escapar HTML
    function escapeHTML(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Función para formatear números
    function formatearNumero(numero) {
        return new Intl.NumberFormat('es-CO').format(numero);
    }
    
    // Función para mantener la sesión activa
    function iniciarPing() {
        // Detener el intervalo anterior si existe
        if (pingInterval) {
            clearInterval(pingInterval);
        }
        
        // Configurar un ping cada 5 minutos para mantener la sesión activa
        pingInterval = setInterval(function() {
            $.ajax({
                url: '/Login/api/ping.php',
                type: 'GET',
                success: function(response) {
                    console.log("Ping exitoso:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Error en ping:", error);
                }
            });
        }, 300000); // 5 minutos = 300000 ms
    }
}); 
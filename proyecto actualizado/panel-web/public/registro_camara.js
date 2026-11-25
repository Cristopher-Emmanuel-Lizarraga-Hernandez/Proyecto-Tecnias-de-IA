/**
 * public/registro_camara.js
 * Enciende la cámara del navegador, toma varias fotos del tutor y las envía al servidor.
 * (JavaScript "de siempre": no necesita instalar nada.)
 */
(function () {
    'use strict';

    const NUM_FOTOS = 25;   // cuántas fotos se toman
    const PAUSA_MS = 250;   // pausa entre foto y foto (25 fotos ≈ 6 segundos)
    const ANCHO_MAX = 640;  // las fotos se reducen a este ancho para que pesen poco

    const form = document.getElementById('form-registro');
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const btnCamara = document.getElementById('btn-camara');
    const btnRegistrar = document.getElementById('btn-registrar');
    const estado = document.getElementById('estado-registro');
    const barra = document.getElementById('barra');

    let stream = null;   // la cámara encendida
    let ocupado = false; // evita registrar dos veces a la vez

    function mostrar(texto, tipo) {
        estado.textContent = texto;
        estado.className = 'estado-' + (tipo || 'info');
    }

    function esperar(ms) {
        return new Promise(function (resolver) { setTimeout(resolver, ms); });
    }

    async function encenderCamara() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            video.srcObject = stream;
            btnCamara.textContent = 'Apagar cámara';
            btnRegistrar.disabled = false;
            mostrar('Cámara lista. Llena los datos y pulsa «Registrar y escanear rostro».', 'info');
        } catch (e) {
            mostrar('No se pudo abrir la cámara. Revisa el permiso del navegador, que ninguna otra '
                + 'aplicación la esté usando y que entres por http://localhost.', 'error');
        }
    }

    function apagarCamara() {
        if (stream) {
            stream.getTracks().forEach(function (pista) { pista.stop(); });
            stream = null;
        }
        video.srcObject = null;
        btnCamara.textContent = 'Encender cámara';
        btnRegistrar.disabled = true;
    }

    /** Toma una foto del video y la devuelve en base64 (JPEG). */
    function tomarFoto() {
        const escala = Math.min(1, ANCHO_MAX / video.videoWidth);
        canvas.width = Math.round(video.videoWidth * escala);
        canvas.height = Math.round(video.videoHeight * escala);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', 0.9).split(',')[1];
    }

    async function registrar() {
        if (ocupado) { return; }
        if (!stream || !video.videoWidth) {
            mostrar('Primero enciende la cámara y espera a que se vea tu imagen.', 'error');
            return;
        }
        ocupado = true;
        btnRegistrar.disabled = true;
        btnCamara.disabled = true;

        try {
            // 1) Tomar las fotos
            const fotos = [];
            barra.max = NUM_FOTOS;
            barra.value = 0;
            barra.hidden = false;
            mostrar('Mira de frente a la cámara y mueve la cabeza despacio de lado a lado…', 'info');
            for (let i = 0; i < NUM_FOTOS; i++) {
                fotos.push(tomarFoto());
                barra.value = i + 1;
                await esperar(PAUSA_MS);
            }

            // 2) Enviarlas al servidor junto con el formulario
            mostrar('Enviando fotos y entrenando el modelo… puede tardar unos segundos.', 'info');
            const datos = new FormData(form);          // incluye los campos y el token CSRF
            datos.append('frames', JSON.stringify(fotos));
            const res = await fetch(form.action, { method: 'POST', body: datos, credentials: 'same-origin' });

            if (res.status === 403) {
                throw new Error('No tienes permiso para registrar tutores (se necesita rol administrador).');
            }
            if (res.status === 419) {
                throw new Error('La página está desactualizada. Recárgala e inténtalo de nuevo.');
            }
            const tipo = res.headers.get('content-type') || '';
            if (tipo.indexOf('application/json') === -1) {
                throw new Error('Tu sesión expiró o la página está desactualizada. Recarga e inicia sesión de nuevo.');
            }
            const respuesta = await res.json();

            if (respuesta.ok) {
                mostrar(respuesta.mensaje, 'ok');
                apagarCamara();
                setTimeout(function () { window.location.reload(); }, 3000);
            } else {
                mostrar(respuesta.mensaje, 'error');
            }
        } catch (e) {
            mostrar(e.message || 'Error de comunicación con el servidor.', 'error');
        } finally {
            ocupado = false;
            barra.hidden = true;
            btnCamara.disabled = false;
            if (stream) { btnRegistrar.disabled = false; }
        }
    }

    btnCamara.addEventListener('click', function () {
        if (stream) { apagarCamara(); mostrar('Cámara apagada.', 'info'); } else { encenderCamara(); }
    });

    form.addEventListener('submit', function (evento) {
        evento.preventDefault();   // no se envía de la forma normal: lo hace registrar()
        registrar();
    });

    window.addEventListener('beforeunload', apagarCamara);
})();
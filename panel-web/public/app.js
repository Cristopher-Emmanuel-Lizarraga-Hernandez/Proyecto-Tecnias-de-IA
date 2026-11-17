/* app.js — comportamiento compartido del panel. Sin dependencias. */
(function () {
    'use strict';

    /* Resalta en el menú la sección actual (según ?controller=...) */
    function marcarMenuActivo() {
        var actual = new URLSearchParams(window.location.search).get('controller') || 'dashboard';
        document.querySelectorAll('.nav a[href]').forEach(function (a) {
            var destino = new URL(a.getAttribute('href'), window.location.href);
            var ctrl = destino.searchParams.get('controller');
            if (ctrl === actual) a.setAttribute('aria-current', 'page');
        });
    }

    /* Iniciales dentro del círculo: <span class="avatar" data-iniciales="Ana Pérez"> */
    function pintarAvatares() {
        document.querySelectorAll('[data-iniciales]').forEach(function (el) {
            var partes = el.getAttribute('data-iniciales').trim().split(/\s+/).filter(Boolean);
            var texto = (partes[0] ? partes[0][0] : '?') + (partes.length > 1 ? partes[partes.length - 1][0] : '');
            el.textContent = texto.toUpperCase();
        });
    }

    function normalizar(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function csvCelda(valor) {
        return '"' + valor.replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
    }

    /* Tabla con búsqueda, filtro por estado, exportación e impresión.
       Contenedor:  <div data-tabla="nombre-del-archivo"> ... </div> */
    function iniciarTabla(contenedor) {
        var filas = Array.prototype.slice.call(contenedor.querySelectorAll('tbody tr[data-fila]'));
        var buscador = contenedor.querySelector('[data-buscar]');
        var botones = contenedor.querySelectorAll('[data-filtro]');
        var contador = contenedor.querySelector('[data-contador]');
        var sinResultados = contenedor.querySelector('[data-sin-resultados]');
        var filtro = 'todos';

        function aplicar() {
            var consulta = buscador ? normalizar(buscador.value) : '';
            var visibles = 0;
            filas.forEach(function (fila) {
                var coincideTexto = normalizar(fila.textContent).indexOf(consulta) !== -1;
                var coincideEstado = filtro === 'todos' || fila.getAttribute('data-estado') === filtro;
                var mostrar = coincideTexto && coincideEstado;
                fila.hidden = !mostrar;
                if (mostrar) visibles++;
            });
            if (contador) contador.textContent = 'Mostrando ' + visibles + ' de ' + filas.length;
            if (sinResultados) sinResultados.hidden = visibles !== 0 || filas.length === 0;
        }

        if (buscador) buscador.addEventListener('input', aplicar);
        botones.forEach(function (b) {
            b.addEventListener('click', function () {
                filtro = b.getAttribute('data-filtro');
                botones.forEach(function (o) { o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
                aplicar();
            });
        });

        var exportar = contenedor.querySelector('[data-exportar]');
        if (exportar) {
            exportar.addEventListener('click', function () {
                var encabezados = Array.prototype.map.call(
                    contenedor.querySelectorAll('thead th:not([data-sin-exportar])'),
                    function (th) { return csvCelda(th.textContent); });
                var lineas = [encabezados.join(',')];
                filas.filter(function (f) { return !f.hidden; }).forEach(function (fila) {
                    var celdas = Array.prototype.slice.call(fila.children).filter(function (td) {
                        return !td.hasAttribute('data-sin-exportar');
                    });
                    lineas.push(celdas.map(function (td) { return csvCelda(td.textContent); }).join(','));
                });
                var blob = new Blob(['\uFEFF' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
                var enlace = document.createElement('a');
                enlace.href = URL.createObjectURL(blob);
                enlace.download = (contenedor.getAttribute('data-tabla') || 'datos') + '-' +
                    new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(enlace);
                enlace.click();
                document.body.removeChild(enlace);
                URL.revokeObjectURL(enlace.href);
            });
        }

        var imprimir = contenedor.querySelector('[data-imprimir]');
        if (imprimir) imprimir.addEventListener('click', function () { window.print(); });

        aplicar();
    }

    document.addEventListener('DOMContentLoaded', function () {
        marcarMenuActivo();
        pintarAvatares();
        document.querySelectorAll('[data-tabla]').forEach(iniciarTabla);
    });
})();

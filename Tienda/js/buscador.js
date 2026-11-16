/*
  Autocompletado del buscador (cabecera y barra lateral de la tienda).
  Los productos llegan desde index.php en la variable PRODUCTOS_BUSCADOR:
  [{nombre, url, imagen, precio}, ...]

  - Mientras se escribe, completa el nombre del primer producto que empieza
    por lo escrito (la parte añadida queda seleccionada).
  - Debajo muestra hasta 6 productos que contienen el texto; al pulsar uno
    se va a su ficha.
  - Teclado: flechas arriba/abajo para moverse, Intro para elegir, Esc para cerrar.
*/
document.querySelectorAll('.buscador-productos').forEach(function (campo) {
    var formulario = campo.closest('form');
    var lista = document.createElement('div');
    lista.className = 'buscador-sugerencias list-group shadow';
    formulario.style.position = 'relative';
    formulario.appendChild(lista);

    var enlaces = [];   // sugerencias que se están mostrando
    var marcada = -1;   // la que está resaltada con las flechas

    // "Balón" y "balon" cuentan como iguales
    function normalizar(texto) {
        return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    function cerrar() {
        lista.style.display = 'none';
        marcada = -1;
    }

    // Pinta la lista con los productos que contienen el texto escrito
    function mostrarSugerencias(texto) {
        var buscado = normalizar(texto.trim());
        lista.innerHTML = '';
        enlaces = [];
        marcada = -1;
        if (buscado === '') {
            cerrar();
            return;
        }
        PRODUCTOS_BUSCADOR
            .filter(function (p) { return normalizar(p.nombre).indexOf(buscado) !== -1; })
            .slice(0, 6)
            .forEach(function (p) {
                var enlace = document.createElement('a');
                enlace.href = p.url;
                enlace.className = 'list-group-item list-group-item-action bg-light d-flex align-items-center py-2';

                var foto = document.createElement('img');
                foto.src = p.imagen;
                foto.alt = '';
                foto.className = 'mr-3';

                var nombre = document.createElement('span');
                nombre.className = 'flex-grow-1';
                nombre.textContent = p.nombre;   // textContent: nunca se interpreta como HTML

                var precio = document.createElement('small');
                precio.className = 'font-weight-bold ml-2 text-nowrap';
                precio.textContent = p.precio;

                enlace.append(foto, nombre, precio);
                lista.appendChild(enlace);
                enlaces.push(enlace);
            });
        lista.style.display = enlaces.length ? 'block' : 'none';
    }

    // Completa el campo con el primer producto que empieza por lo escrito
    // (sin importar mayúsculas ni tildes: "balon" completa "Balón de baloncesto")
    function completar(escrito) {
        var producto = PRODUCTOS_BUSCADOR.find(function (p) {
            return normalizar(p.nombre.slice(0, escrito.length)) === normalizar(escrito);
        });
        if (producto && producto.nombre.length > escrito.length) {
            campo.value = producto.nombre;   // con las mayúsculas y tildes del producto
            campo.setSelectionRange(escrito.length, campo.value.length);
        }
    }

    function resaltar(posicion) {
        enlaces.forEach(function (e) { e.classList.remove('activa'); });
        marcada = posicion;
        if (marcada >= 0) {
            enlaces[marcada].classList.add('activa');
            enlaces[marcada].scrollIntoView({ block: 'nearest' });
        }
    }

    campo.addEventListener('input', function (evento) {
        var escrito = campo.value;
        mostrarSugerencias(escrito);
        // Solo se completa al escribir (no al borrar, para poder corregir)
        if (evento.inputType === 'insertText' && escrito.trim() !== '') {
            completar(escrito);
        }
    });

    campo.addEventListener('keydown', function (evento) {
        if (lista.style.display !== 'block') {
            return;
        }
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            resaltar(Math.min(marcada + 1, enlaces.length - 1));
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            resaltar(Math.max(marcada - 1, -1));
        } else if (evento.key === 'Enter' && marcada >= 0) {
            evento.preventDefault();
            window.location.href = enlaces[marcada].href;
        } else if (evento.key === 'Escape') {
            cerrar();
        }
    });

    // Al salir del campo se cierra (con un pequeño retraso para que dé tiempo a pulsar una sugerencia)
    campo.addEventListener('blur', function () {
        setTimeout(cerrar, 200);
    });
    campo.addEventListener('focus', function () {
        if (campo.value.trim() !== '') {
            mostrarSugerencias(campo.value);
        }
    });
});

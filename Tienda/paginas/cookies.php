<?php
/*
  POLÍTICA DE COOKIES (index.php?p=cookies)
  Lista de las cookies que usa la tienda. Si se añade una cookie nueva, hay que apuntarla aquí.
*/
$lista_cookies = [
    // nombre => [para qué sirve, cuánto dura]
    'PHPSESSID'     => ['Mantiene tu sesión iniciada y tu carrito.', 'Hasta cerrar el navegador'],
    'tema'          => ['Recuerda si has elegido el modo oscuro.', '30 días'],
    'idioma'        => ['Recuerda el idioma elegido (español o inglés).', '30 días'],
    'googtrans'     => ['La usa el traductor de Google cuando eliges inglés.', '30 días'],
    'aviso_cookies' => ['Recuerda que ya has visto el aviso de cookies.', '1 año'],
];

mostrar_migas_de_pan([
    'Inicio' => 'index.php',
    'Política de cookies' => null,
]);
?>
    <div class="container-fluid">
        <h2 class="section-title position-relative text-uppercase mx-xl-5 mb-4"><span class="bg-secondary pr-3">Política de cookies</span></h2>
        <div class="row px-xl-5">
            <div class="col-lg-9 mb-5">
                <div class="bg-light p-30">
                    <p>Una cookie es un pequeño archivo que la web guarda en tu navegador para recordar cosas entre una página y otra.</p>
                    <p>Tio Pepe <b>solo usa cookies necesarias</b> para que la tienda funcione. No usamos cookies de publicidad,
                       de análisis ni de seguimiento, por eso no hace falta pedirte permiso, solo informarte.</p>

                    <table class="table table-bordered bg-white mb-4">
                        <thead class="thead-dark">
                            <tr><th>Cookie</th><th>Para qué sirve</th><th>Duración</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lista_cookies as $nombre => [$uso, $duracion]): ?>
                                <tr><td><code><?= $nombre ?></code></td><td><?= $uso ?></td><td><?= $duracion ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h5>Cómo borrarlas</h5>
                    <p class="mb-0">Puedes borrarlas o bloquearlas desde la configuración de tu navegador. Si lo haces,
                       se cerrará tu sesión y se vaciará el carrito.</p>
                </div>
            </div>
        </div>
    </div>

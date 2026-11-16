<?php
// TEMPORAL: guarda el HTML de una página para revisarla. Se borrará al terminar.
file_put_contents(__DIR__ . '/_vista.html', file_get_contents('php://input'));
echo 'ok';

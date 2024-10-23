<?php
// Ruta al archivo feed.xml dentro del plugin
$feed_path = dirname(dirname(dirname(__FILE__))) . '/feed.xml';

// Comprobar si el archivo existe
if (file_exists($feed_path)) {
    // Establecer la cabecera adecuada para RSS
    header('Content-Type: application/rss+xml; charset=UTF-8');

    // Mostrar el contenido del feed
    readfile($feed_path);
} else {
    // Mostrar mensaje de error si el feed no está disponible
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Feed no disponible.';
}

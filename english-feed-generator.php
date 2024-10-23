<?php
/**
 * Plugin Name: English Feed Generator
 * Description: Permite al administrador añadir publicaciones en inglés y genera un feed RSS en /en/feed
 * Version: 1.5
 * Author: A. Cambronero Blogpocket.com
 */

// Evitar el acceso directo al archivo
if (!defined('ABSPATH')) exit;

// Añadir el menú al panel de administración
add_action('admin_menu', 'efg_add_admin_menu');

function efg_add_admin_menu() {
    if (current_user_can('administrator')) {
        add_menu_page(
            'English Feed',       // Título de la página
            'English Feed',       // Título del menú
            'manage_options',     // Capacidad requerida
            'english-feed',       // Slug
            'efg_admin_page',     // Función que muestra el contenido
            'dashicons-rss',      // Icono del menú
            6                     // Posición en el menú
        );

        // Submenú para configuraciones
        add_submenu_page(
            'english-feed',
            'Configuración',
            'Configuración',
            'manage_options',
            'english-feed-settings',
            'efg_settings_page'
        );
    }
}

// Registrar ajustes
add_action('admin_init', 'efg_register_settings');

function efg_register_settings() {
    register_setting('efg_settings_group', 'efg_max_entries', array(
        'type' => 'integer',
        'default' => 10,
        'sanitize_callback' => 'absint',
    ));

    register_setting('efg_settings_group', 'efg_blog_title', array(
        'type' => 'string',
        'default' => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
    ));

    register_setting('efg_settings_group', 'efg_blog_description', array(
        'type' => 'string',
        'default' => get_bloginfo('description'),
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
}

// Función que muestra la página de administración principal
function efg_admin_page() {
    // Comprobar si se ha enviado el formulario
    if (isset($_POST['efg_submit'])) {
        // Verificar nonce para seguridad
        check_admin_referer('efg_nonce_action', 'efg_nonce_field');

        // Obtener y sanitizar los datos del formulario
        $title = sanitize_text_field($_POST['efg_title']);
        $description = sanitize_textarea_field($_POST['efg_description']);
        $link = esc_url_raw($_POST['efg_link']);

        // Añadir la publicación al archivo
        efg_add_post($title, $description, $link);

        echo '<div class="updated notice is-dismissible"><p>Publicación añadida exitosamente.</p></div>';
    }

    // Formulario de entrada
    ?>
    <div class="wrap">
        <h1>Añadir publicación al feed en inglés</h1>
        <form method="post" action="">
            <?php wp_nonce_field('efg_nonce_action', 'efg_nonce_field'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="efg_title">Título</label></th>
                    <td><input name="efg_title" type="text" id="efg_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="efg_description">Descripción</label></th>
                    <td><textarea name="efg_description" id="efg_description" class="large-text" rows="5" required></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="efg_link">Link</label></th>
                    <td><input name="efg_link" type="url" id="efg_link" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button('Añadir publicación', 'primary', 'efg_submit'); ?>
        </form>
    </div>
    <?php
}

// Función que muestra la página de configuración
function efg_settings_page() {
    // Comprobar si se ha guardado la configuración
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        echo '<div class="updated notice is-dismissible"><p>Configuración actualizada exitosamente.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Configuración del English Feed</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('efg_settings_group');
            do_settings_sections('efg_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="efg_max_entries">Número máximo de entradas</label></th>
                    <td><input name="efg_max_entries" type="number" id="efg_max_entries" value="<?php echo get_option('efg_max_entries', 10); ?>" class="small-text" min="1" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="efg_blog_title">Título del blog</label></th>
                    <td><input name="efg_blog_title" type="text" id="efg_blog_title" value="<?php echo esc_attr(get_option('efg_blog_title', get_bloginfo('name'))); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="efg_blog_description">Descripción del blog</label></th>
                    <td><textarea name="efg_blog_description" id="efg_blog_description" class="large-text" rows="3" required><?php echo esc_textarea(get_option('efg_blog_description', get_bloginfo('description'))); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button('Guardar cambios'); ?>
        </form>
    </div>
    <?php
}

// Función para añadir una publicación al archivo y actualizar el feed
function efg_add_post($title, $description, $link) {
    // Ruta del archivo english_posts.txt en la carpeta del plugin
    $file_path = plugin_dir_path(__FILE__) . 'english_posts.txt';

    // Crear el registro en formato JSON
    $post = array(
        'title' => $title,
        'description' => $description,
        'link' => $link,
        'pubDate' => date('r')
    );

    // Leer contenido existente
    $posts = array();
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $posts = json_decode($content, true);
    }

    // Añadir el nuevo post al inicio (orden cronológico inverso)
    array_unshift($posts, $post);

    // Obtener el número máximo de entradas desde la configuración
    $max_entries = get_option('efg_max_entries', 10);

    // Limitar el número de entradas a $max_entries
    $posts = array_slice($posts, 0, $max_entries);

    // Guardar en el archivo
    file_put_contents($file_path, json_encode($posts));

    // Actualizar el feed.xml
    efg_update_feed($posts);
}

// Función para generar el feed RSS y guardarlo en feed.xml
function efg_update_feed($posts) {
    // Obtener el título y descripción del blog desde la configuración
    $blog_title = get_option('efg_blog_title', get_bloginfo('name'));
    $blog_description = get_option('efg_blog_description', get_bloginfo('description'));

    // Obtener la URL del sitio y añadir el sufijo /en
    $blog_link = trailingslashit(get_bloginfo('url')) . 'en';

    // Construir el contenido del feed RSS
    $rss_feed = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss_feed .= '<rss version="2.0" xml:lang="en">' . "\n";
    $rss_feed .= '<channel>' . "\n";
    $rss_feed .= '<title>' . htmlspecialchars($blog_title) . '</title>' . "\n";
    $rss_feed .= '<link>' . esc_url($blog_link) . '</link>' . "\n";
    $rss_feed .= '<description>' . htmlspecialchars($blog_description) . '</description>' . "\n";
    $rss_feed .= '<language>en</language>' . "\n";
    $rss_feed .= '<lastBuildDate>' . date('r') . '</lastBuildDate>' . "\n";

    foreach ($posts as $post) {
        // Modificar el enlace de la publicación para incluir /en
        $original_link = $post['link'];
        $modified_link = efg_insert_en_in_link($original_link);

        $rss_feed .= '<item>' . "\n";
        $rss_feed .= '<title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
        $rss_feed .= '<link>' . esc_url($modified_link) . '</link>' . "\n";
        $rss_feed .= '<description><![CDATA[' . $post['description'] . ']]></description>' . "\n";
        $rss_feed .= '<pubDate>' . $post['pubDate'] . '</pubDate>' . "\n";
        $rss_feed .= '</item>' . "\n";
    }

    $rss_feed .= '</channel>' . "\n";
    $rss_feed .= '</rss>';

    // Ruta del archivo feed.xml en la carpeta del plugin
    $feed_path = plugin_dir_path(__FILE__) . 'feed.xml';

    // Guardar el feed
    file_put_contents($feed_path, $rss_feed);
}

// Función para insertar /en en los enlaces
function efg_insert_en_in_link($url) {
    $parsed_url = parse_url($url);

    if(isset($parsed_url['scheme']) && isset($parsed_url['host'])) {
        // Reconstruir la URL con /en agregado al path
        $scheme = $parsed_url['scheme'];
        $host = $parsed_url['host'];
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        // Añadir /en al inicio del path
        $path = '/en' . $path;

        // Reconstruir la URL
        $new_url = "$scheme://$user$pass$host$port$path$query$fragment";
        return $new_url;
    } else {
        // Si no se puede parsear la URL, devolverla tal cual
        return $url;
    }
}

// Añadir regla de reescritura personalizada
add_action('init', 'efg_add_rewrite_rules');

function efg_add_rewrite_rules() {
    add_rewrite_rule('^en/feed/?$', 'index.php?efg_feed=1', 'top');
}

// Añadir variable de consulta personalizada
add_filter('query_vars', 'efg_add_query_vars');

function efg_add_query_vars($query_vars) {
    $query_vars[] = 'efg_feed';
    return $query_vars;
}

// Interceptar la solicitud y mostrar el feed
add_action('template_redirect', 'efg_template_redirect');

function efg_template_redirect() {
    if (get_query_var('efg_feed') == 1) {
        // Ruta al archivo feed.xml dentro del plugin
        $feed_path = plugin_dir_path(__FILE__) . 'feed.xml';

        // Comprobar si el archivo existe
        if (file_exists($feed_path)) {
            // Establecer la cabecera adecuada para RSS
            header('Content-Type: application/rss+xml; charset=UTF-8');

            // Mostrar el contenido del feed
            readfile($feed_path);
            exit;
        } else {
            // Mostrar mensaje de error si el feed no está disponible
            header('HTTP/1.0 404 Not Found');
            echo 'Feed no disponible.';
            exit;
        }
    }
}

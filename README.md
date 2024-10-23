# English Generator RSS
- Permite al administrador añadir publicaciones en inglés y genera un feed RSS en /en/feed
- Esto es una solución artesanal para resolver la ausencia de un archivo RSS con el plugin GTranslate instalado.
- Requiere rellenar los campos Título, descripción y link permanente del post en la interfaz del administrador
## Instalación del plugin
### Crear la carpeta del plugin
- Navega a wp-content/plugins/ y crea una carpeta llamada english-feed-generator.
### Agregar los archivos del plugin
- Copia el archivo english-feed-generator.php en la carpeta english-feed-generator.
- Crea un archivo index.php vacío en la misma carpeta para seguridad.
### Activar el plugin
- Ve al panel de administración de WordPress.
- Navega a Plugins > Plugins instalados.
- Activa el plugin English Feed Generator.
## Configuración inicial
### Refrescar las reglas de reescritura
- Ve a Ajustes > Enlaces permanentes.
- Haz clic en Guardar cambios sin modificar nada. Esto refresca las reglas de reescritura y asegura que la URL en/feed funcione correctamente.
### Configurar los parámetros generales
- Ve a English Feed > Configuración.
- Establece el Número máximo de entradas, el Título del blog (en inglés) y la Descripción del blog (en inglés) según tus preferencias. 
- Haz clic en Guardar cambios.
## Añadir publicaciones al feed
### Agregar una nueva publicación:
- Ve a English Feed en el menú de administración.
- Completa los campos Título (en inglés), Descripción o resumen del post (en inglés) y Link. El link debe ser el de la publicación en español.
- Haz clic en Añadir publicación.
### Verificar el feed
- Accede a https://tusitio.com/en/feed.
- Deberías ver el contenido del feed RSS con las publicaciones añadidas y los enlaces modificados para incluir /en.
## Notas importantes
### Sobre la inserción de /en en los enlaces
- La función efg_insert_en_in_link() se encarga de insertar /en en el path de cada URL de las publicaciones.
Asegúrate de que las rutas resultantes existan o estén configuradas en tu sitio si deseas que los enlaces funcionen correctamente.
### Uso de las APIs de reescritura de WordPress
- Al utilizar las funciones add_rewrite_rule(), add_query_vars() y template_redirect, hemos integrado la redirección dentro de WordPress, evitando la necesidad de modificar el archivo .htaccess.
- Recuerda refrescar las reglas de reescritura cada vez que modifiques estas funciones.
### Seguridad y validación
- El plugin utiliza funciones de sanitización y validación para garantizar la seguridad de los datos ingresados.
- Se utilizan nonces para proteger los formularios contra ataques CSRF.
### Permisos de archivos
- Asegúrate de que el servidor web tiene permisos de lectura y escritura en la carpeta del plugin y en los archivos feed.xml y english_posts.txt.

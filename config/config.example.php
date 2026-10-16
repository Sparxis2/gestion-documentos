<?php
/**
 * Copia este archivo como config.php y pon ahí tus datos.
 * config.php NO se sube a Git (está en .gitignore).
 *
 * XAMPP local:  host 127.0.0.1, usuario root, clave vacía, base appweb.
 * Vercel:       este archivo NO se usa; los datos van en variables de entorno
 *               (DATABASE_URL). Ver README.md, sección "Publicar en Vercel".
 */
return [
    'db_host'    => '127.0.0.1',
    'db_puerto'  => 3306,
    'db_nombre'  => 'appweb',
    'db_usuario' => 'root',
    'db_clave'   => '',
];

<?php
/**
 * Configuración y conexión a MySQL.
 *
 * El mismo código corre en dos lugares:
 *  - XAMPP (local):  lee config/config.php y guarda los archivos en las carpetas pdf/ y perfiles/.
 *  - Vercel (nube):  lee variables de entorno y guarda los archivos dentro de MySQL,
 *                    porque el disco de Vercel se borra entre peticiones.
 *
 * Variables de entorno (Vercel → Settings → Environment Variables):
 *   DATABASE_URL  mysql://usuario:clave@host:puerto/base?ssl-mode=REQUIRED   (la "Service URI" de Aiven)
 *     o bien DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
 *   DB_SSL_CA     contenido del certificado CA del proveedor (opcional si subes el archivo config/ca.pem)
 */

/** true si el sistema corre en Vercel. */
function en_la_nube()
{
    return getenv('VERCEL') !== false && getenv('VERCEL') !== '';
}

/** Configuración efectiva (se calcula una vez). */
function config_app()
{
    static $c = null;
    if ($c !== null) {
        return $c;
    }

    $c = [
        'db_host' => '127.0.0.1', 'db_puerto' => 3306, 'db_nombre' => 'appweb',
        'db_usuario' => 'root', 'db_clave' => '', 'db_ssl' => false, 'db_ssl_ca' => '',
        'almacenamiento' => en_la_nube() ? 'bd' : 'carpeta',
    ];

    $desdeEntorno = true;
    $url = getenv('DATABASE_URL');
    if ($url) {
        $p = parse_url($url);
        if (!$p || empty($p['host'])) {
            throw new RuntimeException('La variable DATABASE_URL no tiene un formato válido (mysql://usuario:clave@host:puerto/base).');
        }
        parse_str(isset($p['query']) ? $p['query'] : '', $q);
        $modo = strtolower(isset($q['ssl-mode']) ? $q['ssl-mode'] : (isset($q['sslmode']) ? $q['sslmode'] : ''));
        $c = array_merge($c, [
            'db_host'    => $p['host'],
            'db_puerto'  => isset($p['port']) ? (int) $p['port'] : 3306,
            'db_nombre'  => isset($p['path']) ? ltrim(rawurldecode($p['path']), '/') : '',
            'db_usuario' => isset($p['user']) ? rawurldecode($p['user']) : '',
            'db_clave'   => isset($p['pass']) ? rawurldecode($p['pass']) : '',
            'db_ssl'     => $modo !== '' && $modo !== 'disabled',
        ]);
    } elseif (getenv('DB_HOST')) {
        $c = array_merge($c, [
            'db_host'    => getenv('DB_HOST'),
            'db_puerto'  => getenv('DB_PORT') ? (int) getenv('DB_PORT') : 3306,
            'db_nombre'  => (string) getenv('DB_NAME'),
            'db_usuario' => (string) getenv('DB_USER'),
            'db_clave'   => (string) getenv('DB_PASSWORD'),
            'db_ssl'     => in_array(strtolower((string) getenv('DB_SSL')), ['1', 'true', 'si', 'sí', 'required'], true),
        ]);
    } else {
        $desdeEntorno = false;
        $ruta = __DIR__ . '/../config/config.php';
        if (!is_file($ruta)) {
            throw new RuntimeException(en_la_nube()
                ? 'Faltan las variables de entorno de la base de datos (DATABASE_URL) en Vercel.'
                : 'Falta el archivo config/config.php. Copia config/config.example.php como config.php y pon tus datos de MySQL.');
        }
        $c = array_merge($c, require $ruta);
    }

    // Certificado CA: variable DB_SSL_CA (texto PEM) o archivo config/ca.pem
    $pem = getenv('DB_SSL_CA');
    if ($pem) {
        $pem  = str_replace('\n', "\n", trim($pem)); // por si se pegó en una sola línea
        $ruta = sys_get_temp_dir() . '/appweb-ca-' . md5($pem) . '.pem';
        if (!is_file($ruta)) {
            file_put_contents($ruta, $pem . "\n");
        }
        $c['db_ssl_ca'] = $ruta;
        $c['db_ssl']    = true;
    } elseif ($desdeEntorno && $c['db_ssl_ca'] === '' && is_file(__DIR__ . '/../config/ca.pem')) {
        $c['db_ssl_ca'] = realpath(__DIR__ . '/../config/ca.pem');
        $c['db_ssl']    = true;
    } elseif ($c['db_ssl_ca'] !== '') {
        $c['db_ssl'] = true;
    }

    if (getenv('ALMACENAMIENTO')) {
        $c['almacenamiento'] = getenv('ALMACENAMIENTO');
    }
    $c['almacenamiento'] = $c['almacenamiento'] === 'bd' ? 'bd' : 'carpeta';

    return $c;
}

/** Conexión PDO única por petición. */
function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $c = config_app();

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], (int) $c['db_puerto'], $c['db_nombre']);

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if ($c['db_ssl']) {
        if ($c['db_ssl_ca'] !== '') {
            // Conexión cifrada y verificada con el certificado del proveedor
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $c['db_ssl_ca'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        } else {
            // Sin certificado: se cifra igual, pero sin verificar al servidor
            $sistema = openssl_get_cert_locations();
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $sistema['default_cert_file'];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }

    $pdo = new PDO($dsn, $c['db_usuario'], $c['db_clave'], $opciones);
    // Hora de México (sin horario de verano desde 2022) para CURRENT_TIMESTAMP
    $pdo->exec("SET time_zone = '-06:00'");

    return $pdo;
}

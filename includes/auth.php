<?php
/**
 * Sesión y permisos.
 * Roles: admin (todo) y capturista (registra, edita y consulta documentos).
 */
function usuario_actual()
{
    static $usuario = false;
    if ($usuario !== false) {
        return $usuario;
    }
    $usuario = null;
    if (!empty($_SESSION['usuario_id'])) {
        $st = db()->prepare('SELECT id, nombre, usuario, rol, foto FROM usuarios WHERE id = ? AND activo = 1');
        $st->execute([(int) $_SESSION['usuario_id']]);
        $fila = $st->fetch();
        if ($fila) {
            $usuario = $fila;
        } else {
            unset($_SESSION['usuario_id']); // lo desactivaron o lo eliminaron
        }
    }
    return $usuario;
}

function es_admin()
{
    $u = usuario_actual();
    return $u && $u['rol'] === 'admin';
}

function requerir_login()
{
    if (!usuario_actual()) {
        redirigir('login.php');
    }
}

function requerir_admin()
{
    requerir_login();
    if (!es_admin()) {
        flash('error', 'Esa sección es solo para administradores.');
        redirigir('index.php');
    }
}

function iniciar_sesion($usuario, $clave)
{
    $st = db()->prepare('SELECT id, password_hash FROM usuarios WHERE usuario = ? AND activo = 1');
    $st->execute([$usuario]);
    $fila = $st->fetch();
    if ($fila && password_verify($clave, $fila['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $fila['id'];
        return true;
    }
    return false;
}

function cerrar_sesion()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

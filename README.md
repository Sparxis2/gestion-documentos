# Gestión de Documentos · Oficina Digital

Sistema web para registrar y consultar oficios. Los datos se guardan en MySQL y el
archivo (PDF o foto) en la carpeta `pdf/`; la base de datos solo guarda el nombre del archivo.

Hecho con PHP y MySQL (PDO) en el servidor, y HTML, CSS, **Bootstrap 5.3.3** y **jQuery 3.7.1** en el
navegador (incluidos en `assets/vendor/`, funcionan sin internet). El mismo código funciona en XAMPP (local)
y en Vercel con un MySQL en la nube.

- **Bootstrap:** rejilla responsiva, ventana modal de confirmación, avisos flotantes (toasts) y tooltips.
- **jQuery:** cambio de estatus por AJAX sin recargar, búsqueda en vivo, vista previa y reducción de fotos.

## Instalación local (XAMPP)

1. Copia la carpeta en `C:\xampp\htdocs\appweb`.
2. Inicia **Apache** y **MySQL** en el panel de XAMPP.
3. En `http://localhost/phpmyadmin` crea la base `appweb` (cotejamiento `utf8mb4_unicode_ci`).
4. Con la base seleccionada, pestaña **Importar** → elige `database.sql` → **Importar**.
5. Copia `config/config.example.php` como `config/config.php` (con XAMPP no hay que cambiar nada).
6. Abre `http://localhost/appweb/`.

Usuario inicial: **admin** · contraseña: **admin123** (cámbiala en Configuración).

## Usarlo desde el teléfono

Con el teléfono en la misma red WiFi abre `http://IP-DE-LA-LAPTOP/appweb/`. La sección **Conexión desde teléfono** muestra la dirección y un código QR.
Si no carga, permite Apache en el Firewall de Windows.

## Estructura

```
appweb/
├── index.php              Inicio (totales y últimos documentos)
├── login.php, logout.php  Sesión
├── documentos.php         Listado con filtros y paginación
├── documento_form.php     Registrar / editar
├── documento_accion.php   Cambiar estatus y eliminar
├── ver.php                Ver un documento (datos + foto o PDF) con botón Volver
├── archivo.php            Entrega el archivo (abrir o descargar)
├── buscar.php, reportes.php, usuarios.php, usuario_form.php, configuracion.php
├── conexion.php           Dirección y código QR para abrirlo desde el teléfono
├── foto.php               Entrega las fotos de perfil guardadas en MySQL (solo en la nube)
├── api/index.php          Punto de entrada en Vercel (dirige cada petición a su página)
├── api/php.ini            Límites de PHP en Vercel
├── vercel.json            Configuración de Vercel (runtime PHP y rutas)
├── config/                Conexión a la base de datos
├── includes/              Código compartido (conexión, sesión, encabezado, tabla)
│   └── almacen.php        Archivos en carpeta (XAMPP) o en MySQL (Vercel), sesiones e instalación
├── assets/                CSS, JS (jQuery), ícono y vendor/ (Bootstrap y jQuery)
├── pdf/                   Archivos de los documentos
├── perfiles/              Fotos de perfil de los usuarios
└── database.sql           Tablas y datos iniciales
```

## Roles

- **Administrador**: todo, incluido eliminar documentos, usuarios y configuración.
- **Capturista**: registra, edita, consulta y cambia el estatus de documentos.

## Publicar en Vercel

Vercel no ejecuta PHP de forma nativa; se usa el runtime comunitario
[vercel-php](https://github.com/vercel-community/php) (PHP 8.4). Diferencias con XAMPP:

| | XAMPP | Vercel |
|---|---|---|
| Datos de conexión | `config/config.php` | Variables de entorno |
| Archivos subidos | Carpetas `pdf/` y `perfiles/` | Tabla `archivos` de MySQL (el disco de Vercel se borra) |
| Sesiones | Archivos de PHP | Tabla `sesiones` de MySQL |
| Tamaño máximo por documento | 10 MB | 4 MB (Vercel no acepta peticiones de más de 4.5 MB) |
| Tablas | Importar `database.sql` | Se crean solas al abrir el sistema la primera vez |

Pasos:

1. Crear un MySQL gratuito en [Aiven](https://aiven.io) y copiar su **Service URI** y su **CA certificate**.
2. Subir este proyecto a un repositorio de GitHub.
3. En Vercel: **Add New → Project**, importar el repositorio, Framework Preset **Other**.
4. En **Environment Variables** agregar:
   - `DATABASE_URL` = la Service URI (`mysql://avnadmin:…@…aivencloud.com:PUERTO/defaultdb?ssl-mode=REQUIRED`)
   - `DB_SSL_CA` = el contenido completo del CA certificate (desde `-----BEGIN CERTIFICATE-----` hasta `-----END CERTIFICATE-----`)
5. **Deploy**. Al abrir la dirección se crean las tablas y el usuario `admin` / `admin123`.
6. Cambiar de inmediato la contraseña de `admin`: el sitio es público.

En lugar de `DB_SSL_CA` también se puede guardar el certificado como `config/ca.pem` en el repositorio
(es un certificado público, no una contraseña).

## Publicar en un hosting

1. Sube todos los archivos a la carpeta pública (`htdocs` o `public_html`).
2. Crea la base de datos en el panel del hosting e importa `database.sql`.
3. Edita `config/config.php` con el host, usuario, contraseña y nombre de base del hosting.
4. Verifica que la carpeta `pdf/` tenga permiso de escritura.

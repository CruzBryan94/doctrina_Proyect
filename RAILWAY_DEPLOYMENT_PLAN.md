# Plan de despliegue de ARTEMIS en Railway Hobby

Fecha de análisis: 26 de octubre de 2026, America/Guayaquil.

Actualización del 27/10/2026: el usuario creó la rama `produccion` y servicios Railway. Autorizó adaptar la validación de arranque, CI y documentación a **`produccion`** y generar el commit local `to-prod1120`, sin push ni despliegue desde Codex. `APP_ENV=production` se conserva como entorno Laravel. La corrección no cambia lógica, datos ni esquema; los estados de preparación del 26/10 siguientes son antecedentes y no certifican la configuración remota actual.

**Estado actualizado: infraestructura y CI preparados localmente; activación externa y despliegue pendientes.** La primera entrega fue exclusivamente de análisis. En la segunda fase autorizada se añadieron Docker, CI y documentación; se ejecutaron pruebas únicamente sobre SQLite aislado, validación de cachés en directorios aislados y compilación frontend en una copia temporal. No se modificaron lógica de negocio, dependencias/lockfiles, tablas ni datos existentes. No se crearon ramas, commits, servicios o despliegues. La suite completa presenta fallos heredados que bloquean CI; no se considera aprobada.

La guía operativa vigente es [DEVOPS_WORKFLOW.md](DEVOPS_WORKFLOW.md). Los fragmentos orientativos de este plan deben contrastarse con los archivos reales de `docker/`, `Dockerfile`, `railway.json` y `.github/workflows/ci.yml`. Su preparación no activa opciones en GitHub o Railway.

## 1. Conclusión y alcance

La aplicación puede desplegarse conservando Laravel 10, Blade y MySQL. La arquitectura inicial recomendada consta de un servicio web con Caddy y PHP-FPM, un servicio MySQL privado, una réplica de cada servicio y dos volúmenes separados. No hay evidencia en las fuentes activas de necesitar Redis, workers, scheduler permanente ni servidor WebSocket.

Para reducir RAM ociosa, usar PHP-FPM `ondemand` con dos hijos como máximo. MySQL seguirá siendo el principal consumo base. El modo Serverless del servicio web es opcional: reduce horas encendidas a cambio de arranque en frío; no se presupone que MySQL pueda dormir de manera fiable ni que el conjunto cueste siempre USD 5.

La revisión inicial fue estática y local; ahora incluye pruebas aisladas de CI. No se consultó la base MySQL ni una cuenta Railway: tamaño real de MySQL, versión de servidor, tráfico, métricas y configuración efectiva en nube siguen por confirmar. Las estimaciones no son mediciones del contenedor.

La raíz Laravel/Git es `doctrina_Proyect/`, dentro del directorio de trabajo superior `CEDE - DOCTRINA/`. El documento se ubica en la raíz Laravel junto a `artisan` y `composer.json`. Si GitHub publica ese repositorio, Railway debe construir desde `/`; si publica el directorio superior, seleccionar `/doctrina_Proyect` como Root Directory. Verificarlo antes de desplegar.

Se detectaron cambios locales previos en código, vistas, pruebas y documentación; se conservaron. Una imagen construida desde GitHub solo incluirá lo que efectivamente esté publicado allí; no se hicieron commits ni se incorporaron cambios pendientes.

## 2. Inventario y dependencias

| Elemento | Evidencia y consecuencia |
| --- | --- |
| Backend | `composer.json`: PHP `^8.1`, Laravel `^10.10`; `composer.lock`: Laravel **10.48.25**. Instalar desde el lockfile, sin `composer update`. |
| Interfaz | Blade, AdminLTE 3.2.0 / integración 3.14.2, Bootstrap, Alpine, Tailwind y Vite 5. Mantener vistas y recursos existentes. |
| Autenticación/autorización | Sanctum y Spatie Permission 6.10.1; permisos cacheados 24 horas en el store predeterminado. |
| PDF | Integración DomPDF 3.0.1 y motor 3.1.0; exportaciones síncronas en `DashboardController::report` y `HistoricalDashboardController::report`. |
| Otras dependencias | Guzzle, Socialite 5.16.0, Laravel UI y Tinker. La dependencia Socialite por sí sola no implica uso activo de Google. |
| Frontend | `package.json` solo tiene `dev` y `build`; `public/build` y `public/hot` están ignorados. Compilar Vite en build y copiar también `public/css`, `public/js`, `public/vendor`, `public/lib` e imágenes utilizadas. |
| Herramientas de desarrollo | Sail, Pint, PHPUnit e Ignition son dependencias de desarrollo; excluirlas del runtime mediante instalación `--no-dev`. No ejecutar Vite dev ni servidor Artisan en producción. |
| Infraestructura | Preparados `Dockerfile`, `.dockerignore`, `docker/Caddyfile`, configuración PHP/FPM, script de arranque supervisado con Tini y `railway.json`. CI en `.github/workflows/ci.yml`; falta su ejecución remota y validación de imagen. |

PHP seleccionado: **8.3 FPM**, imagen fijada por digest de manifiesto obtenido del registro oficial durante esta preparación. Composer, Caddy y Node de construcción también tienen digest; actualizar mediante PR revisado. El README documenta uso local de PHP 8.3; esto no certifica el entorno productivo. `composer check-platform-reqs` pasó localmente con PHP 8.3.29; la comprobación `--no-dev` está incorporada al Dockerfile, aún pendiente de ejecutar con un motor Docker disponible.

Incluir extensiones requeridas por Laravel y dependencias: PDO/MySQL, mbstring, DOM/XML, ctype, curl, fileinfo, filter, hash, OpenSSL, PCRE, session, tokenizer y OPcache; GD para imágenes de PDF. Validar iconv y zlib; añadir ZIP/intl únicamente si la instalación o las funciones verificadas lo requieren. Conservar fuentes DejaVu Sans y permisos de escritura para DomPDF. No instalar Redis, Xdebug o Imagick por defecto. Referencia: [requisitos Laravel 10](https://laravel.com/docs/10.x/deployment).

## 3. Procesos y servicios realmente necesarios

| Componente | Hallazgo | Decisión inicial |
| --- | --- | --- |
| Colas | `.env` local y `config/queue.php`: `QUEUE_CONNECTION=sync`. Sin jobs propios ni implementaciones activas de `ShouldQueue` encontradas. La tabla histórica `failed_jobs` no prueba necesidad de worker. | Mantener ejecución síncrona; no lanzar `queue:work`, Horizon ni Redis. |
| Cron/scheduler | `app/Console/Kernel.php` solo contiene el ejemplo comentado; `routes/console.php` define `inspire` sin programarlo. | No crear servicio cron ni `schedule:work`. |
| WebSockets | Echo/Pusher están comentados en `resources/js/bootstrap.js`; no se detectaron eventos broadcast activos ni dependencias de servidor WebSocket. | Mantener `BROADCAST_DRIVER=log`, sin Reverb/Soketi/Pusher adicional. |
| Procesamiento | CSV, PDF, cálculos de plazos, dashboard y cambios de estado se ejecutan por petición en las fuentes inspeccionadas. | No inventar tareas nocturnas para esas funciones. |
| Correo | Hay controlador heredado de recuperación y listener de verificación, pero `routes/auth.php` no es cargado por las rutas activas revisadas y `User` no implementa `MustVerifyEmail`. El reset vigente es administrativo según documentación local. | No contratar SMTP para el flujo actual. Si se habilitan rutas de correo en otro alcance, configurar y probar SMTP entonces. |
| Permanentes | Caddy, maestro PHP-FPM y MySQL. Puede existir un supervisor mínimo para manejar ambos procesos web. | Solo esos procesos; Node/Composer son herramientas de construcción. |

No se detectó polling periódico con `setInterval` en los JS propios revisados. La inspección del repositorio no permite descartar cron externos o integraciones configuradas fuera de él; confirmar su inexistencia antes del corte.

## 4. Configuración actual y persistencia

El entorno local usa `APP_ENV=local`, `APP_DEBUG=true`, `LOG_CHANNEL=stack`, `LOG_LEVEL=debug`, MySQL, caché/sesión en archivos y disco `local`. No copiar esos parámetros de depuración a producción ni publicar `.env`.

| Área | Análisis | Tratamiento recomendado |
| --- | --- | --- |
| Caché | `config/cache.php`: archivos en `storage/framework/cache/data`; Spatie usa este store. También hay limitación de intentos/autorización que debe continuar funcionando. | Conservar `CACHE_DRIVER=file` con una réplica. No usar `array`/`null` para ahorrar disco. Restablecer caché de permisos tras desplegar. |
| Sesión | `config/session.php`: archivos, 120 minutos, cookie HTTP-only/SameSite lax, limpieza por lotería 2/100. CSV guarda `member_csv_preview` con hasta 5.000 filas en sesión. | Conservar `SESSION_DRIVER=file` en volumen. No cambiar a cookie: la previsualización excedería el tamaño práctico de cookies. No crear tablas nuevas de sesiones/caché. |
| Logs | `stack` apunta a `single`, que escribe `storage/logs/laravel.log` sin rotación. | `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning`; conservar errores y auditoría existente en DB. Si se necesitan eventos informativos, usar `info` y medir volumen. Mantener escribible `storage/logs` para emergencia. |
| Archivos | Discos local y public disponibles, sin escrituras de adjuntos encontradas en los flujos activos. | Mantener las rutas actuales; no exponer `storage/app` completo. |
| CSV | Archivo temporal de PHP, límite de aplicación 2 MiB/5.000 filas; revisión en sesión y confirmación posterior. | `/tmp` efímero y escribible; persistir sesión entre revisión y confirmación. No guardar el CSV como adjunto nuevo. |
| PDF | Se descargan directamente; exportaciones usan `get()` para listados completos. DomPDF usa `storage/fonts` y temporales del sistema según configuración del paquete. | Fuentes/cache escribibles y regenerables; prever picos de memoria/CPU. No almacenar cada PDF generado. |
| MySQL | Una conexión `mysql`, utf8mb4, modo estricto, sin `PDO::ATTR_PERSISTENT` configurado. `DATABASE_URL` también puede determinar conexión. | Red privada y credenciales propias del despliegue; no activar conexiones persistentes ni pooler. Evitar `DATABASE_URL` heredada que contradiga `DB_*`. |

### Volúmenes

1. **MySQL:** volumen en la ruta de datos de la imagen elegida, normalmente `/var/lib/mysql`. Es indispensable para conservar esquema, registros, historial, usuarios y auditoría.
2. **Web:** volumen en `/app/storage`. Permite conservar sesiones y cualquier archivo legítimo existente sin cambiar las rutas PHP. Inicializar en el arranque `app/public`, `framework/sessions`, `framework/cache/data`, `framework/views`, `fonts` y `logs`, con propietario/permisos correctos. `bootstrap/cache` permanece en la imagen y debe ser escribible, sin volumen adicional.

El volumen web oculta el contenido de `storage` que se haya preparado en la imagen. Por ello, crear directorios y compilar vistas después de montarlo; no asumir que `view:cache` del build será visible. Conservar/migrar solo archivos requeridos, sin sobrescribir sesiones ni vaciar el volumen en cada inicio. Crear `public/storage` únicamente como enlace a `storage/app/public` cuando se necesite ese disco.

Se inventariaron aproximadamente **9 MiB en `storage/app`** entre capturas, PDF de QA, copias JSON y bases SQLite de pruebas. Su presencia no demuestra que sean datos funcionales. Los respaldos deben conservarse fuera de la imagen productiva; no copiar QA ni bases SQLite al runtime. No se borró ninguno. La capacidad de MySQL sigue desconocida: medir datos + índices + redo/binlogs + margen antes de dimensionar volúmenes; las migraciones no revelan el tamaño actual.

Railway monta volúmenes al iniciar el contenedor, no en build/pre-deploy. Un servicio con volumen tiene restricciones de réplica y puede sufrir interrupción al redeploy. Planificar ventana de mantenimiento. Fuentes: [uso de volúmenes](https://docs.railway.com/volumes), [limitaciones](https://docs.railway.com/volumes/reference).

## 5. Arquitectura de despliegue propuesta

```text
Usuario HTTPS
    -> Railway Edge (TLS)
        -> servicio web, una réplica
            Caddy HTTP 0.0.0.0:$PORT, raíz /app/public
                -> FastCGI 127.0.0.1:9000
                    PHP-FPM ondemand -> Laravel 10 / Blade
                        -> MySQL:3306 por red privada
            volumen web /app/storage
        -> servicio MySQL, una réplica, volumen de datos propio
```

Usar la misma región para web y DB. Publicar solo Caddy, no FPM ni MySQL; no habilitar TCP proxy público de DB salvo una necesidad puntual controlada. No añadir una segunda réplica con sesiones/caché locales.

**Método recomendado: Dockerfile explícito y build multietapa**, con Caddy 2 y PHP 8.3 FPM en un solo contenedor web. Mantener MySQL en servicio aparte. Esto permite controlar el pool FPM sin añadir otro servicio facturable para Caddy.

Hay una discrepancia relevante en las fuentes consultadas: la [guía Laravel de Railway](https://docs.railway.com/guides/laravel) menciona autodetección con PHP-FPM/Caddy; la [documentación actual de Railpack PHP](https://railpack.com/languages/php/) describe **FrankenPHP** y arranque automático con migraciones. No confiar en la autodetección para cumplir el requisito FPM. Si se evaluara Railpack posteriormente, inspeccionar su plan real y desactivar migraciones con `RAILPACK_SKIP_MIGRATIONS=true`; esa variable no configura un Dockerfile propio.

### Construcción y arranque futuros

1. Preparar contexto de build que excluya `.env*` privados, `.git`, QA, `tmp`, dumps, logs, sesiones, cachés locales, `vendor` y `node_modules` locales. Preservar fuentes, lockfiles y recursos públicos legítimos. No incluir secretos en argumentos o capas.
2. Instalar dependencias con `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`; conservar scripts necesarios de descubrimiento. No ejecutar actualización ni scripts de creación de proyecto/key generation.
3. Usar Node compatible con Vite 5 y `npm ci && npm run build` en etapa de construcción; instalar también devDependencies de npm allí porque contienen Vite. Copiar `public/build` a runtime, excluir `public/hot` y herramientas de build del contenedor final.
4. Arranque tras montar volumen: preparar directorios/permisos, regenerar configuración y vistas con las variables reales de Railway; caché de rutas solo después de validarla. Sin consultas MySQL en un bucle de espera permanente.
5. Iniciar `php-fpm -F` y `caddy run` mediante un init/supervisor mínimo: reenviar SIGTERM, recoger hijos y finalizar el contenedor si cualquiera de los dos procesos esenciales termina. No usar un simple `php-fpm & caddy` sin gestión de fallos.
6. Healthcheck inicial `/login`, esperando HTTP 200 en staging. No utilizar `/`, que redirige a autenticación. Ese check valida respuesta web, no toda la integridad MySQL. Si se necesita comprobación profunda, diseñar después una ruta mínima autorizada. Railway no sustituye con esto el monitoreo continuo.

**No ejecutar `migrate`, `migrate --force`, seeders, `fresh` ni `db:wipe` en build, pre-deploy o arranque.** Para la transferencia inicial será necesaria una autorización separada de copia de la base existente, preservando exactamente esquema y datos. No crear esquema nuevo desde migraciones históricas. Desplegar contra una DB vacía no conservaría la aplicación funcional.

## 6. PHP-FPM, PHP y Caddy recomendados

Fragmento de pool propuesto, sujeto a validar en imagen; reemplazar/ajustar el pool predeterminado, no crear dos pools accidentalmente:

```ini
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000
pm = ondemand
pm.max_children = 2
pm.process_idle_timeout = 10s
pm.max_requests = 300
clear_env = no
catch_workers_output = yes
decorate_workers_output = no
security.limit_extensions = .php
```

`ondemand` crea hijos al llegar peticiones y los retira al vencer su inactividad; el maestro sigue encendido. Dos hijos permiten dos solicitudes PHP simultáneas; el resto espera y puede agotar timeouts. `pm.max_requests` recicla procesos, pero no limita memoria por sí mismo. No añadir `start_servers` ni mínimos de spare servers, que corresponden a otros modos. Desactivar cualquier configuración adicional de imagen que fuerce un pool `dynamic`. Referencia: [manual PHP-FPM](https://www.php.net/manual/en/install.fpm.configuration.php).

Punto de partida para PHP, sin convertirlo en un límite funcional definitivo:

```ini
memory_limit = 256M
max_execution_time = 120
upload_max_filesize = 4M
post_max_size = 8M
display_errors = Off
log_errors = On
error_log = /proc/self/fd/2
expose_php = Off
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 64
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
```

Los límites de carga admiten el CSV de 2 MiB con margen para multipart. Validar que el límite HTTP también lo permita. El valor 256M es un techo de asignaciones PHP por proceso, no una reserva ni un límite completo de RSS. Las bibliotecas y buffers pueden añadir consumo. No bajar a 64M/128M antes de ensayar PDF e importaciones máximas.

`opcache.validate_timestamps=0` presupone imágenes inmutables y reinicio en cada despliegue. Monitorear memoria/hit ratio de OPcache; subir a 96M si se llena. Mantener JIT desactivado por defecto. Los 120 segundos son candidatos: medir CSV/PDF y alinear timeouts Caddy/plataforma con margen; no imponer terminación FPM más corta que las operaciones existentes. Reducirlos sin pruebas podría interrumpir importaciones válidas.

Caddyfile orientativo para `/app` y TLS terminado en Railway:

```caddyfile
{
    admin off
    auto_https off
}
:{$PORT} {
    root * /app/public
    @hidden path /.env /.env/* /.git /.git/*
    respond @hidden 404
    php_fastcgi 127.0.0.1:9000 {
        read_timeout 150s
    }
    file_server
}
```

Caddy sirve estáticos y envía PHP a FastCGI; no exponer `/app` como document root. No depender de `.htaccess`: Caddy no aplica reglas Apache. Validar configuración con `caddy validate` y `php-fpm -tt` en la imagen, y comprobar denegación de secretos, rutas inexistentes, CSS/JS/PDF y enlaces simbólicos. La caché HTTP duradera solo debe aplicarse a assets versionados, nunca a páginas autenticadas. Fuente: [Caddy php_fastcgi](https://caddyserver.com/docs/caddyfile/directives/php_fastcgi).

**HTTPS/proxy pendiente de validación:** `TrustProxies.php` tiene `$proxies` sin definir. `APP_URL=https://...` y cookie segura no garantizan que todas las URLs generadas reconozcan TLS detrás del proxy. Probar redirecciones, CSRF, IP del cliente y enlaces. Si falla, preparar un ajuste acotado de proxies confiables, previa autorización, respetando los encabezados recibidos por Railway; no confiar indiscriminadamente en headers del cliente ni resolverlo suponiendo una variable inexistente `TRUSTED_PROXIES`.

## 7. Cachés de Laravel y MySQL

Proponer `php artisan config:cache` y `php artisan view:cache` tras disponer de variables/directorios reales; `route:cache` solo cuando pase en staging. Existen closures en rutas: no afirmar incompatibilidad o éxito sin probar la versión concreta. No se encontró `env()` fuera de `config/` en las fuentes propias examinadas. Después de cambiar variables, reconstruir la caché de configuración y reiniciar procesos.

Evitar `optimize:clear` indiscriminado sobre el volumen: puede limpiar caché funcional/limitadores compartidos por el despliegue anterior. Invalidar solo lo necesario. Regenerar vistas por versión y restablecer caché de permisos con el comando de Spatie durante la ventana controlada, sin modificar roles ni datos. Referencia: [optimización Laravel 10](https://laravel.com/docs/10.x/deployment).

Para MySQL, primero registrar versión exacta del origen, charset, collation, SQL mode, tamaño, zona horaria y requisitos de backup. Elegir imagen compatible y fijarla; no usar `mysql:latest` ni asumir que una actualización mayor será transparente. Mantener modo estricto/utf8mb4 existentes y no modificar esquema ni índices.

Candidatos iniciales de ajuste de servidor, a ensayar en copia y según versión:

```ini
[mysqld]
innodb_buffer_pool_size=128M
max_connections=20
table_open_cache=256
thread_cache_size=4
```

El pool de InnoDB no es toda la RAM de MySQL; conexiones y buffers añaden uso. Validar valores efectivos con `SHOW VARIABLES`, conexiones máximas observadas y RSS. No asumir que variables Railway arbitrarias como `MYSQL_INNODB_BUFFER_POOL_SIZE` configuran la imagen: usar archivo/argumentos soportados y comprobarlos. Con dos hijos FPM, 20 conexiones dejan margen administrativo, pero deben revisarse si existen otros clientes. No desactivar fsync/durabilidad, binlog requerido por backups, ni Performance Schema a ciegas para ahorrar. Fuentes: [memoria MySQL](https://dev.mysql.com/doc/refman/8.0/en/memory-use.html), [variables de servidor](https://dev.mysql.com/doc/refman/8.0/en/server-system-variable-reference.html).

## 8. Variables de entorno propuestas, sin secretos

Usar Variables de Railway. Los textos `<...>` son marcadores, no valores literales para pegar.

| Variable | Valor/criterio |
| --- | --- |
| `APP_NAME` | `ARTEMIS` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `<clave existente custodiada>`; conservarla durante despliegues. Nunca regenerarla en cada arranque. |
| `APP_URL` | `https://<dominio-real>` |
| `LOG_CHANNEL` / `LOG_LEVEL` | `stderr` / `warning` inicialmente |
| `LOG_DEPRECATIONS_CHANNEL` | `null`, como configuración actual; revisión de compatibilidad en staging |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `<host privado del servicio MySQL>` mediante referencia Railway |
| `DB_PORT` | Puerto interno, normalmente `3306` |
| `DB_DATABASE` | `<nombre de base restaurada>` |
| `DB_USERNAME` / `DB_PASSWORD` | `<usuario de aplicación>` / `<secreto>` mediante referencias; evitar root para runtime |
| `DATABASE_URL` | Omitir si se usan `DB_*`; no dejar valor local contradictorio |
| `DB_SOCKET` | Sin socket local configurado |
| `MYSQL_ATTR_SSL_CA` | Solo si la conexión elegida exige CA; ruta real del certificado, no contenido secreto en este plan |
| `CACHE_DRIVER` | `file`; este Laravel lee `CACHE_DRIVER`, no asumir `CACHE_STORE` |
| `SESSION_DRIVER` / `SESSION_LIFETIME` | `file` / `120` |
| `SESSION_SECURE_COOKIE` | `true` bajo HTTPS |
| `SESSION_DOMAIN` | Omitir para cookie del host; fijarlo solo si hay una necesidad de dominio probada |
| `FILESYSTEM_DISK` | `local` |
| `QUEUE_CONNECTION` | `sync` |
| `BROADCAST_DRIVER` | `log` |
| `PORT` | Proporcionado/configurado en Railway y leído por Caddy; escuchar en todas las interfaces |
| `VIEW_COMPILED_PATH` | Normalmente omitir; directorio por defecto escribible creado antes del cacheado |

La zona horaria y locale actuales están definidos en `config/app.php`: conservarlos; añadir `APP_TIMEZONE` o `APP_LOCALE` no tendría efecto si el archivo no los lee. No establecer `ASSET_URL` salvo CDN real probado. No se requieren Redis, Memcached, AWS, Pusher, Google ni SMTP para los flujos activos detectados. Los campos vacíos de `.env.example` no justifican crear esos servicios. Si correo se vuelve necesario, configurar `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` y `MAIL_FROM_NAME` sin registrar sus secretos.

Las variables de inicialización de la imagen MySQL y sus claves deben configurarse también en el servicio DB según la imagen concreta; no confundirlas con `DB_*` de Laravel. Custodiar credenciales de administración y backups separadamente. Nunca imprimir `config:show`, `.env` o URLs con contraseña en logs/evidencias.

## 9. Estimación de recursos y costo

Supuesto de trabajo: pocos usuarios, 1–2 solicitudes PHP concurrentes, PDF/CSV ocasionales, una región y ninguna réplica adicional. Valores orientativos para **validar**, sin benchmark ejecutado:

| Componente | Reposo encendido | Actividad/pico previsto | Techo inicial a ensayar |
| --- | --- | --- | --- |
| Web: Caddy + maestro FPM + OPcache | 40–120 MiB, hijos retirados | 180–500 MiB habitual; 600–800 MiB con dos operaciones pesadas posibles | 768 MiB; subir a 1 GiB si dos PDF/CSV legítimos lo requieren |
| MySQL ajustado | 250–450 MiB | 350–650 MiB; importación/restauración puede superar esto | 768 MiB inicialmente, con margen extra para restauración |
| CPU | Objetivo web cercano a cero, DB con actividad interna baja | Ráfagas importantes en PDF, hash de login y CSV | Ensayar hasta 1 vCPU por servicio; limitar CPU puede aumentar latencia |

Un límite de RAM/CPU es protección, no ahorro equivalente al consumo real ni reserva facturable. No prometer que 256 MiB de web bastan para todas las funciones. Ajustar `pm.max_children` usando memoria medida de las operaciones más pesadas y dejando espacio para maestro, Caddy, memoria compartida y sistema; no sumar RSS de OPcache compartido repetidas veces.

Tarifa pública consultada: Hobby USD 5/mes con USD 5 de uso incluido; RAM USD 10/GB-mes, CPU USD 20/vCPU-mes, egreso USD 0,05/GB y volumen USD 0,15/GB-mes. Facturación orientativa: `max(5, uso mensual elegible)`; no sumar otra vez los USD 5 a todo el uso. Revisar impuestos, extras y tarifas vigentes antes de contratar. Fuente: [planes y precios Railway](https://docs.railway.com/pricing/plans).

Ejemplos propios de planificación, usando promedios mensuales en GB facturables y volúmenes facturados totales de 2 GB:

| Escenario | Cálculo de uso | Total orientativo |
| --- | --- | --- |
| Muy ligero | RAM 0,35 GB × 10 + CPU 0,005 × 20 + disco 2 × 0,15 + egreso 1 × 0,05 = USD 3,95 | USD 5 mínimo |
| Conservador | RAM 0,55 GB × 10 + CPU 0,01 × 20 + disco 2 × 0,15 + egreso 2 × 0,05 = USD 6,10 | USD 6,10 |
| Más memoria/tráfico | RAM 0,85 GB × 10 + CPU 0,03 × 20 + disco 2 × 0,15 + egreso 5 × 0,05 = USD 9,65 | USD 9,65 |

No presupuestar esos 2 GB sin medir la DB; verificar en Usage cómo se contabiliza almacenamiento/backups. Copias externas y cualquier otro proyecto de la cuenta pueden añadir gastos. Para comenzar, reservar aproximadamente **USD 6–12/mes**, con posibilidad de acercarse al mínimo solo si las métricas lo permiten. No se garantiza ese intervalo.

### Serverless opcional

Probar primero únicamente en web. Railway detecta inactividad de red, puede dormir tras aproximadamente 5–10 minutos y despierta por tráfico; hay arranque en frío. Activar el ajuste requiere redeploy para afectar al contenedor. Conexiones, sondeos o tráfico privado pueden impedir el sueño. Fuente: [Serverless](https://docs.railway.com/deployments/serverless).

Verificar en el ensayo el tiempo hasta `/login` y primera consulta real; mantener MySQL activo inicialmente. No contabilizar ahorro de DB hasta probar compatibilidad, despertar privado y latencia. El volumen sigue necesitando presupuesto. No programar pings de keep-alive ni monitores frecuentes que frustren el ahorro; elegir monitoreo y disponibilidad conscientemente.

## 10. Imprescindibles y opcionales

### Imprescindibles antes de producción, previa autorización

- Build reproducible desde lockfiles y runtime Caddy/FPM explícito, sin herramientas de desarrollo activas.
- `APP_ENV=production`, depuración apagada, secretos fuera de imagen, `APP_KEY` estable y HTTPS/cookies/proxy verificados.
- Persistencia de MySQL y sesiones; directorios/permisos de volumen inicializados después del montaje.
- Transferencia autorizada y respaldada de la base existente, sin cambiar esquema; sin migraciones automáticas.
- Una réplica, `sync`/`file` existentes; no añadir workers/scheduler/Redis que el código activo no usa.
- Logs controlados a stderr, OPcache y cachés Laravel verificadas; perfil FPM bajo demanda y presupuesto de memoria probado con PDF/CSV.
- Backup recuperable, prueba de restauración, validación funcional y registro de costos reales.

### Opcionales, solo después de una línea base

- Serverless web si se acepta arranque en frío.
- Ajustar pool MySQL, cachés de tablas y conexiones según métricas; no son condición para iniciar staging con valores conservadores.
- Bajar techo de RAM, hijos FPM o tiempos de espera únicamente si pasan las pruebas de carga funcionales.
- Compresión HTTP y caché de estáticos versionados si el ahorro de egreso compensa CPU y no afecta recursos autenticados.
- Almacenamiento de objetos/CDN o Redis solo ante necesidad futura demostrada; supondrían costos y posiblemente cambios que no están autorizados.
- Telemetría externa/slow log temporal con retención acotada para investigar problemas concretos; no añadir agentes residentes inicialmente.

## 11. Riesgos y mitigaciones

| Riesgo concreto | Impacto y tratamiento |
| --- | --- |
| Disco efímero o montaje incompleto | Pérdida de sesiones, revisión CSV o datos DB. Volúmenes y prueba de redeploy/reinicio. Nunca presentar volumen como sustituto de backup. |
| Copias solo en el mismo servicio | Una pérdida/borrado afecta datos y copias. Exportación cifrada fuera del servicio y restauración ensayada; no copiar datadir MySQL en caliente como backup válido. |
| Dos exportaciones pesadas | RAM alta, OOM, espera de login o 502/504. Medir percentiles y pico, dimensionar web y cola de peticiones; no recortar reportes existentes como optimización implícita. |
| CSV de 5.000 filas | Más RAM, sesión grande y transacciones largas. Ensayar preview/commit, doble envío y recuperación de sesión; mantener límites funcionales existentes. |
| Pool demasiado pequeño | Ahorro con mayor espera. Dos hijos son propuesta para poca concurrencia, no garantía; revisar saturación antes de reducir. |
| Reinicios/redeploy con volúmenes | Interrupciones y peticiones en curso. Ventana de mantenimiento, señales/gracia y rollback; sin promesa de alta disponibilidad con una réplica. |
| Logs `single` y artefactos QA | Crecimiento de disco, exposición de información y archivos innecesarios. stderr, contexto de build acotado y exclusión de QA; no publicar backups. |
| TLS/proxy mal reconocido | URLs HTTP, cookies o throttling incorrectos. Validar headers e IP de cliente con Railway antes de abrir acceso real. |
| Publicación fuera de red controlada | La documentación local define recuperación administrativa con provisional compartida para un grupo/red controlados. Exponerla en Internet cambia el contexto de riesgo. Acordar acceso restringido o una revisión específica autorizada; no modificar autenticación en este plan. |
| Versión antigua de framework | Laravel 10 está fuera de soporte de seguridad a esta fecha. Mantener la versión mayor exigida, registrar riesgo y revisar dependencias/exposición en un alcance posterior; no actualizar aquí. [Calendario Laravel](https://laravel.com/docs/10.x/releases). |
| Windows a Linux | Mayúsculas en rutas, enlaces y permisos pueden romper assets/PDF. Ensayar en imagen Linux exacta, conservar nombres y fuentes. |
| MySQL incompatible o distinto SQL mode | Fallos de importación/consultas o diferencias de fechas. Confirmar versión y restaurar copia íntegra compatible; sin cambios de esquema para sortear errores. |
| Límite duro de gasto | Puede dejar servicios fuera de línea. Configurar alerta temprana y elegir límite conscientemente; una alerta no bloquea consumo. |

Backup inicial propuesto: exportación lógica consistente de la DB, archivos funcionales necesarios y copia custodiada de APP_KEY/configuración secreta; retención diaria de siete copias y semanal de cuatro, ajustable al volumen. Definir RPO inicial de 24 horas; medir RTO en restauración antes de prometer una cifra. No se configuró ningún backup en esta fase.

## 12. Plan de pruebas y criterios de aceptación posteriores

Ejecutar tras autorización en staging temporal y con copia aislada; no usar credenciales/datos habituales para pruebas destructivas ni arrancar `RefreshDatabase` sin verificar SQLite de pruebas. El staging adicional también consume recursos.

1. **Imagen:** instalar lockfiles, validar plataforma PHP, extensiones, Caddy y FPM; construir Vite y confirmar manifest/assets. Verificar que no contiene `.env`, SQL, QA o secretos. Sin migraciones ni generación automática de claves.
2. **Conectividad:** dominio HTTPS, `/login` 200, estáticos 200, FPM/DB inaccesibles públicamente; headers TLS/IP y cookies correctos; sin contenido mixto ni errores JS.
3. **Funciones:** login/logout/remember, inactivos, roles/combinaciones, usuario sin módulos, URL directa, reset administrativo, CRUD, matriz, transición/publicación, notas, historia, referencias y proyección. Confirmar que no cambian reglas ni datos históricos.
4. **CSV:** carga/revisión/confirmación, archivo de 2 MiB y hasta 5.000 filas, válidos/errores/duplicados/ceros iniciales; sesión sobrevive al redeploy entre preview y commit, y se respeta expiración actual. No crear cuentas o roles por importar integrantes.
5. **PDF:** reportes actual e histórico, mayor listado representativo, fuentes/acentos/logos/tablas/filtros correctos; descargar dos simultáneos y navegar durante ellos. Registrar memoria y duración, sin cambiar contenido para hacer pasar la prueba.
6. **Persistencia/recuperación:** reinicio y redeploy conservan DB, auditoría, sesiones y archivos requeridos; cachés se regeneran. Restaurar backup en destino aislado y comparar esquema, conteos e integridad referencial, sin imprimir datos personales.
7. **Carga:** reposo de 30–60 minutos, una petición, dos concurrentes y ráfaga de cinco, incluyendo PDF/CSV. Registrar RAM máxima/media, CPU, p50/p95, 5xx, reinicios/OOM, cola FPM y conexiones DB. Usar instrumentación temporal no pública para FPM.
8. **FPM:** tras más de 10 segundos sin solicitudes, verificar que el pool retira hijos y quedan maestro/Caddy; después comprobar que una petición nueva funciona. Sin workers, cron o Node en runtime.
9. **Serverless opcional:** esperar ciclo real de inactividad sin pings, comprobar que duerme, despertar por URL y verificar login/DB/sesión. Registrar impacto y decidir si activarlo.
10. **Costo:** observar 24–72 horas y revisar proyección; después una semana representativa. No certificar costo mensual solo por métricas de cinco minutos.

Criterio mínimo: sin regresiones funcionales, pérdida de datos, OOM/reinicios ni errores 5xx en el ensayo de 1–2 solicitudes simultáneas. Como objetivo inicial a acordar, p95 de páginas ordinarias menor de 2 segundos en caliente; PDF/CSV deben completar dentro del timeout validado con margen y sin bloquear sistemáticamente el acceso. Si fallan, ajustar recursos antes de reducir funcionalidades.

## 13. Monitoreo de costos y operación

- Antes de producción, registrar inicio de ciclo, servicios activos, región, volúmenes, plan y presupuesto. En Railway Usage, separar RAM, CPU, almacenamiento, egreso y backups por servicio; revisar entornos temporales olvidados.
- Configurar alerta de consumo computacional, por ejemplo USD 7, y evaluar límite duro, por ejemplo USD 12, solo después de aceptar que puede detener los servicios. Los límites se aplican al ámbito configurado y pueden incluir otros proyectos; no confundir Compute Usage con consumo del Railway Agent. [Controles de costo](https://docs.railway.com/pricing/cost-control).
- Primeras 72 horas: revisar diariamente RAM media/pico, CPU ociosa, errores/reinicios y proyección de factura. Si web queda alto en reposo, inspeccionar hijos FPM, sondeos y procesos extra; si domina DB, comparar variables efectivas con propuesta y tamaño real.
- Proyectar `uso acumulado / días observados × días del ciclo`, corrigiendo días de pruebas y cambios de carga; contrastar con proyección Railway. Mantener margen para exportaciones, restauraciones y picos.
- Semanalmente: revisar crecimiento de volúmenes, conexiones DB, copias y egreso de PDF. Revisar consumo al cambiar imagen, tamaño de datos o concurrencia. Guardar métricas sin secretos ni datos personales.
- Si se usa Serverless, confirmar sueño real y ahorro del web; el gráfico público de red no refleja todo el tráfico privado. No instalar pings para mantenerlo despierto.
- Los límites por servicio deben permitir la carga probada. No bajar RAM hasta provocar reinicios para conseguir un objetivo de factura. Al aproximarse al presupuesto, investigar y decidir entre más presupuesto, ajuste probado o pausa expresamente acordada.

## 14. Secuencia de activación y despliegue pendiente

1. Aprobar arquitectura, presupuesto, exposición de red y aceptación o no de Serverless.
2. Revisar los archivos de infraestructura ya preparados y autorizar por separado cualquier ajuste adicional de proxy que resulte necesario; sin tocar lógica ni esquema.
3. Ejecutar CI e imagen en runner efímero o entorno local y medir recursos sobre copia aislada; no crear staging permanente en Railway ni publicar producción por el solo hecho de aprobar el plan.
4. Autorizar transferencia íntegra de base/archivos, credenciales y ventana de mantenimiento; hacer respaldo y ensayar restauración.
5. Ejecutar corte, pruebas de humo y observación de costos; conservar origen disponible para recuperación según plan acordado.

Rollback: conservar imagen anterior, configuración segura y backups verificados; revertir la imagen sin migraciones. El volumen no revierte al cambiar de imagen. Ante problemas de datos, detener escrituras y definir restauración/reconciliación autorizada; restaurar una copia antigua después de nuevas escrituras perdería cambios. Mantener APP_KEY y no ejecutar seeders de recuperación.

**Pendientes para la siguiente fase:** resolver expectativas heredadas de pruebas antes de aprobar CI; verificar imagen Linux; definir/crear manualmente ramas aprobadas y sus protecciones; versión/tamaño y respaldo real de MySQL; medición de PDF/CSV máximo; presupuesto final; acceso público o restringido; dominio y configuración efectiva de Railway. No se autorizó desplegar.

## 15. Decisiones de CI/CD y estado de preparación

Flujo objetivo: `dev → PR revisado → produccion → CI de push correcto → Railway`. En la inspección inicial del 26/10 solo existían `devDoctrina` y `main`; el 27/10 se confirmó además `produccion` local y `origin/produccion`. No se creó, eliminó ni renombró ninguna rama desde Codex. `dev` sigue pendiente de creación autorizada; conservar `produccion` existente. El ajuste de nombre no elimina los requisitos de revisión y CI.

| Opción | Ventajas | Límites para ARTEMIS | Decisión |
| --- | --- | --- | --- |
| Railpack / FrankenPHP | Menos mantenimiento de imagen y configuración automática. | La documentación describe migraciones automáticas por defecto; no es PHP-FPM y no aplica `pm=ondemand`. Su consumo depende del modo/configuración y exige medición. | No seleccionado para este alcance. No se afirma que siempre consuma más RAM. |
| Docker / Caddy + FPM ondemand | Control explícito del ciclo de procesos, pool de dos hijos, entorno y arranque sin migraciones. | Requiere mantener imagen/extensiones y verificar build, señales y volumen; Caddy/maestro permanecen residentes mientras no duerma web. | Seleccionado para conservar arquitectura y controlar memoria ociosa. Ahorro aún no medido. |

Referencia de comparación: [Railpack PHP](https://railpack.com/languages/php/). Node 22 solo compila assets; el runtime continúa siendo Laravel/Blade/PHP, sin proceso Node.

### Controles preparados

- CI se ejecuta en PR a `produccion` y push a `dev`, `produccion` y temporalmente `devDoctrina`. No usa secretos de producción ni tokens Railway ni publica imágenes. Actions de terceros están fijadas por SHA.
- Jobs de aplicación y contenedor desembocan en **`ARTEMIS CI required`**. Fallo/cancelación/omisión de cualquiera bloquea el gate. Para PR, el gate también exige origen `dev` del mismo repositorio. No hay `continue-on-error`, exclusiones de tests ni auto-merge.
- `phpunit.ci.xml` incluye Unit y Feature completos; bootstrap previo a `RefreshDatabase` fuerza SQLite `:memory:` y redirige storage y todas las cachés a un directorio nuevo ignorado en `storage/framework/testing/ci-*`. Comprueba configuración efectiva antes de ejecutar tests. Las migraciones/fixtures internas de PHPUnit solo se aplican a esa SQLite efímera, nunca a MySQL existente.
- Docker instala Composer con `--no-dev --no-scripts`. El arranque ejecuta únicamente descubrimiento de paquetes y cachés de configuración/rutas/vistas/permisos, luego FPM y Caddy. No genera APP_KEY ni ejecuta migraciones, seeders o reconstrucción histórica.
- Como defensa adicional contra la rama equivocada, el arranque en contexto Railway exige `RAILWAY_GIT_BRANCH=produccion` provista por su trigger GitHub; rechaza ramas de desarrollo o metadata ausente. No definir esa variable manualmente para saltar la validación ni usar CLI como ruta alternativa de publicación.
- `railway.json` fija Docker, una réplica, volumen obligatorio `/app/storage`, pre-deploy vacío, healthcheck `/login`, drenaje de 150 s y tres reintentos por fallo. La rama vinculada, Autodeploy, Wait for CI, credenciales, volumen DB y desactivación de previews se configuran manualmente; no están garantizados por ese JSON.
- Caddy envía `HTTPS=on`/`SERVER_PORT=443` a PHP porque esta imagen está destinada a Railway Edge con HTTPS externo. Esto permite URLs HTTPS sin modificar `TrustProxies.php`; todavía hay que comprobar IP del cliente/throttling y uso exclusivo del Edge. No es una configuración genérica para publicar HTTP directamente.
- Persistencia web conserva sesión, caché de archivos, fuentes y archivos; solo se invalida caché de permisos al desplegar. Errores se envían a stderr y tienen la retención de Railway; auditorías de negocio siguen en MySQL. Para retención de logs más larga, definir exportación/retención separadamente.

### Validaciones de esta fase

- Sintaxis PHP de scripts CI, sintaxis Bash del arranque, YAML del workflow, JSON Railway contra esquema oficial y XML contra el esquema PHPUnit 10: correctos. Actionlint 1.7.12 pasó sin incidencias, con integración ShellCheck deshabilitada porque esa herramienta no estaba disponible.
- Plataforma del `vendor` local: correcta con PHP 8.3.29. Se habilitó pdo_sqlite solo en el proceso de pruebas usando la DLL ya disponible.
- `package:discover`, `config:cache`, `route:cache`, `view:cache` y `permission:cache-reset`: correctos en el entorno aislado, sin acceder a MySQL. No se tocaron cachés operativas locales.
- `npm ci` y `npm run build`: correctos en copia temporal de frontend, con Node local 24.12.0 y Vite 5.4.11. CI/Docker seleccionan Node 22; su ejecución exacta queda pendiente. Hay advertencias preexistentes de Sass/Browserslist; no se actualizaron dependencias.
- Suite completa: **162 pruebas, 1.326 aserciones, 16 fallos, 2 errores, 1 omitida**, salida 1. Las 18 incidencias corresponden a scaffold de rutas desconectadas documentadas en `docs/INTEGRACION_VERIFICACION.md`; la omisión es el benchmark opt-in de 30.000 personas. El gate permanecerá rojo mientras no se apruebe y ejecute su mantenimiento; no se eliminó ni suavizó ninguna prueba.
- Docker Desktop estaba apagado; se inició temporalmente y se intentó build con motor 29.8.2. Falló antes de descargar imágenes porque el motor no resolvió `registry-1.docker.io`. No se modificó la red del equipo ni se alcanzaron Caddy/FPM reales o smoke test. El job Docker los prepara para GitHub: construcción sin publicar, validación, login sin servicio DB y persistencia tras reinicio con volumen desechable. No equivale a haberlos superado.

No se probaron GitHub Actions remotos, protecciones de rama, Wait for CI, red Railway, importación de datos ni consumo real. La preparación queda **parcialmente verificada y bloqueada para liberación**, no lista para desplegar sin atender esos pendientes. Ver [DEVOPS_WORKFLOW.md](DEVOPS_WORKFLOW.md) para activación, operación y recuperación.

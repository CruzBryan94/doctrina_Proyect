# Unificación visual: verificación

## Cambios

- `resources/views/layouts/app.blade.php` y `public/css/artemis.css`: acceso, registro y cambio obligatorio con identidad ARTEMIS, logos originales, panel de formulario, foco y cabecera móvil compacta.
- `resources/views/welcome.blade.php`: portada institucional y logo real en el layout activo.
- `public/css/artemis.css` y `public/css/alerts.css`: paleta, contraste de texto secundario, componentes Bootstrap 5 y piel AdminLTE 3; Bootstrap 4 permanece aislado en legacy.
- `config/adminlte.php`, vistas activas de `committees/members` y `committees/military_units`: logo ancho en sidebar abierto y compacto en colapsado, navegación alineada con ARTEMIS y GateFilter/`viewAny`, etiquetas activas acero, botones outline/paginación sin verde y eliminación de COMMAND NEXUS visible. DataTables conserva la versión 1.10.19 con sus assets locales existentes; la tabla de unidades mantiene búsqueda, paginación y textos en español sin CDN.
- `resources/views/report/dashboardPDF.blade.php` y `historyPDF.blade.php`: tipografía y paleta PDF propias conservadas, sin cambios a datos/cálculos.

## Evidencia técnica

- PHPUnit dirigido: `AuthenticationTest|RegistrationTest|AccountAccessTest|DashboardQueryTest`: **28 pruebas, 165 assertions, OK** (SQLite en memoria con `pdo_sqlite`).
- Permisos/roles `DoctrineWorkflowTest` (dos casos seleccionados): **2 pruebas, 8 assertions, OK**.
- `php artisan view:cache` y `php artisan view:clear`: OK.
- `php -l config/adminlte.php`: OK tras el ajuste del menú/skin.
- `git diff --check`: OK; Git informa avisos de normalización CRLF/LF en varios archivos existentes.
- `imagenes/Logo de portada.png` y `public/imagenes/Logo de portada.png`: SHA-256 idéntico `EC4058908529A8791067147B7F1EBFA548625B06D6E5BF238C579372488FC6AE`.

## Fuente y límites

No hay archivo Nunito local en `public` ni `resources`; se usa pila local `Segoe UI, Arial, sans-serif`, sin fuente externa ni dependencia nueva. PDFs conservan `DejaVu Sans, Arial, sans-serif`.

QA de integración informó que el login móvil presenta los campos y el envío cerca del inicio, y que DataTables de unidades funciona con búsqueda local. La validación visual final de los reportes queda en el flujo integrador. No se verificó en esta tarea toda vista heredada desconectada. Select2, Chart.js, SweetAlert2 y Pace conservan sus configuraciones inactivas existentes.

## Cierre de integración visual

- QA navegador: login y registro390×844, login/escritorio1440×900, pantalla obligatoria de contraseña, bienvenida none, dashboard, catálogo de integrantes, unidades y matriz. Sin errores JavaScript en esas comprobaciones. Cabecera móvil68px y botón de acceso dentro de la vista inicial; sin overflow horizontal de página. Matriz conserva únicamente MANUAL fija horizontalmente.
- Se corrigió el float del logo AdminLTE que desplazaba la navegación: nav ahora comienza en x8px, ancho234px dentro del sidebar250px; clic real Unidades→Matriz y regreso correcto. Menú activo acero, logo auténtico ancho y toggle manual preservado.
- Contraste: blanco/rojo6,21; blanco/acero10,12; navy/cian5,36 y navy/oro7,06. Para texto pequeño se usa acero porque el gris de paleta sobre blanco no llega a4,5. Foco de teclado principal en acero.
- Recursos DataTables1.10.19 ahora publicados en public/lib/datatables/1.10.19 desde las mismas URL oficiales previamente configuradas, sin actualizar versión. Se conservan las cabeceras de licencia. Búsqueda/paginación unidades funciona y el DOM no carga scripts ni hojas de estilo externos activos.
- Root retiró la fuente remota de resources/sass/app.scss y fijó pila local Segoe UI/Arial y fondo#F3F6F9 en _variables.scss. npm run build correcto con dependencias existentes; avisos de obsolescencia Sass/Browserslist del toolchain, sin cambios de versiones ni instalaciones.
- PDFs actual e histórico con paleta unificada:1página A4 horizontal cada uno, renderizados e inspeccionados sin cortes/solapes; ver PDF_VERIFICACION_FINAL.md.
- HTTP sobre MySQL: dashboard, histórico, cronograma, plazos, ficha, matriz, cuentas y notas200. Verificación de identidad/hash/estado/roles del admin habitual sin cambios. No hubo migraciones ni escrituras de datos de aplicación por el rediseño; las cuentas QA se crearon únicamente en SQLite separada.
- El servidor SQLite QA usa CACHE_DRIVER=array para aislar IDs de permisos de la base habitual. Se limpió la caché de permisos tras la separación. No se modificaron roles ni permisos persistidos.
- Vista previa habitual disponible en http://127.0.0.1:8766/login; el puerto8765 corresponde a fixtures QA. No hay commits de esta entrega. No se certifican vistas antiguas sin rutas activas.

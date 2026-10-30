# ARTEMIS — pendientes para próximas sesiones

Actualizado: 26 de octubre de 2026. Se implementó y verificó la sección de gráficos del dashboard; los recorridos manuales CSV/CGFT y la verificación integrada37 están completados. El benchmark PDF de alto volumen sigue siendo opt-in.

## 1. Dashboard: gráficos pendientes

La base funcional incluye agregaciones, filtros, listado paginado, enlaces, alertas y PDF. Los gráficos analíticos se completaron en esta entrega sin dependencias externas.

| ID | Prioridad | Tarea | Aceptación |
| --- | --- | --- | --- |
| G01 | Alta | **Verificado** — Dona de situación actual. | En proceso/Publicado excluyentes. Live dashboard: 22/27 en proceso y 5/27 publicados; enlaces, denominador y publicaciones futuras incluidas una vez comprobados por datos agregados y prueba futura. |
| G02 | Alta | **Verificado** — Dona de clasificación. | Desconocidos explícitos y enlazados. Live dashboard: Generado1, Actualización0, Sin información26. |
| G03 | Alta | **Verificado** — Barras por cinco fases, Publicados y mapa. | Visual inspeccionado a 1280×900; conteos y porcentajes corresponden al total27, incluidos Publicados como categoría independiente. |
| G04 | Media | **Verificado** — Serie mensual en Productividad histórica. | Consulta por fecha declarada de publicación y fecha efectiva de CGFT. Clic en abril2026 abrió su período y el listado de1 publicación. Puntos vacíos se muestran como cero sin interpolación; cobertura histórica permanece visible. Fechas futuras se identifican en barras/tabla y se verifican con fixture automatizada; la base habitual no contenía publicaciones futuras para mostrar en vivo. |
| G05 | Alta | **Verificado** — Segmentos, leyendas y barras enlazan con listados, conservan otros filtros y reinician paginación. | En el navegador se probó activación del segmento SVG por teclado y leyenda por clic. Combinación clasificación+fase conservó `classification=unknown`, cambió a `phase=process` y quitó `page=2`. |
| G06 | Media | **Verificado** — Navegación común de Situación actual, Productividad, Proyección y Consistencia. | Las cuatro secciones cargan su página/servicio al entrar; Proyección y Consistencia quedaron con la pestaña activa correcta. |
| G07 | Alta | **Verificado** — Interfaz accesible, responsive y sin CDN. | Vista inspeccionada a 1280×900 y390×844. Leyendas y tabla mensual acompañan los gráficos; cifras y etiquetas evitan depender solo del color; SVG ofrece enlaces con activación de teclado. Cero resultados mantiene etiquetas, ceros y tabla. Sin errores de consola. |
| G08 | Alta | **Verificado** — Exportación y casos límite. | Pruebas dirigidas cubren cero, desconocidos, publicación futura, filtros y endpoints PDF. La exportación PDF conserva alcance/filtros existentes; los gráficos no se añaden al PDF, por lo que no requiere réplica estática. Interacciones y cifras live inspeccionadas con27 manuales activos. |

### Evidencia de implementación — 26/10/2026

- Archivos: `app/Services/DashboardQuery.php`, `app/Services/ManualProductivity.php`, `app/Http/Controllers/DashboardController.php`, `app/Http/Controllers/HistoricalDashboardController.php`, vistas `dashboard.blade.php`, `dashboard-history.blade.php`, `projection/index.blade.php`, `references/index.blade.php`, partial `dashboard-tabs.blade.php`, `partials/filters.blade.php`, `public/js/dashboard-charts.js`, `public/css/artemis.css` y pruebas de dashboard/productividad.
- Verificación dirigida actualizada en esta sesión: 19 pruebas y113 aserciones correctas; `php -l`, `node --check`, build Vite y `git diff --check` correctos.
- Servidor habitual `127.0.0.1:8766` abrió con la base encendida. Dashboard, filtros combinados, productividad mensual, Proyección y Consistencia se recorrieron en navegador; escritorio1280×900 y móvil390×844. No se modificaron registros.
- No hubo publicación futura en la base habitual para mostrar en pantalla; la marca de futuro se comprobó mediante prueba aislada de PHPUnit. Los gráficos siguen fuera del PDF y su exportación mantiene los filtros existentes.

La tarea28 queda implementada para el alcance G01–G08. La verificación integrada37 conserva su estado parcial hasta cerrar los demás módulos/recorridos documentados.

## 2. Recorridos manuales y cierre de integración

- CSV: **completado en navegador (26/10/2026)**. En una copia SQLite desechable se revisaron 5 filas: 1 nueva, 1 existente, 1 repetida dentro del archivo y 2 errores; la confirmación incorporó solo la nueva y mostró el resumen correcto (1 incorporado, 2 omitidos, 2 con errores). Lectura posterior confirmó las dos filas de fixture y la nueva en la copia. La copia habitual y MySQL no se modificaron; la cuenta temporal y la base desechable se retiraron tras verificar. La copia de QA original no tenía las cuentas de demostración del README, por lo que no servían para el acceso de prueba.
- CGFT: **completado en navegador (26/10/2026)**. En una copia SQLite desechable se publicaron QA Manual aislado 04 (fecha declarada 25/10/2026) y QA Manual aislado 09 (26/10/2027, futura) con el botón explícito y confirmación. Se verificaron ficha y estado publicado, dashboard, históricos de 2026/2027 y ambos PDF. El histórico conserva el marcador de fecha futura; el consolidado muestra ambas fechas y los tiempos detenidos al publicar. PDF de una página A4 horizontal cada uno, renderizados sin cortes, solapes ni texto ilegible. La copia de QA se retiró; MySQL y la copia original no se modificaron.
- Tarea37: **completada (26/10/2026)**. Ver matriz representativa de roles/URL/concurrencia, suite Feature amplia y dirigida, preservación MySQL y límites de cobertura en [INTEGRACION_VERIFICACION.md](INTEGRACION_VERIFICACION.md). La suite amplia no se etiqueta como globalmente correcta: identifica por separado las 18 incidencias de rutas scaffold desconectadas.
- Evaluar rendimiento del PDF con volúmenes mayores únicamente cuando haga falta. El benchmark de30.000 personas permanece desactivado salvo solicitud explícita; para desarrollo usar fixtures pequeñas.

## 3. Estado que debe conservarse

- Notas por fase, transferidos, cronograma, plazos, referencias, cuentas y reset local ya tienen implementación; no volver a registrarlos como funcionalidades nuevas pendientes.
- La unificación visual24/25 está cerrada. Los gráficos G01–G08 quedaron implementados y verificados como ampliación analítica; no reabrir la tarea de paleta.
- Migraciones anteriores aplicadas a MySQL; preservar usuario, hash de contraseña, estado y roles del admin habitual. No ejecutar seeders generales ni recargar datos para desarrollar gráficos.
- Mantener la estrategia económica: una tarea acotada para Luna y revisión/integración del agente principal; pocas fixtures y pruebas pertinentes, sin duplicar exploraciones ni suites.
- Preview de la base habitual: http://127.0.0.1:8766/login. El puerto8765 es QA aislada y debe usar caché separada/en memoria para no compartir IDs de permisos con MySQL.

Fuentes de evidencia: PLAN_IMPLEMENTACION.md y los documentos de verificación por módulo. Este archivo es el índice vigente de trabajo futuro.

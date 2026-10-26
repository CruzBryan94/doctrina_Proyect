# Dashboard y consultas actuales — evidencia de implementación

Fecha: 24 de octubre de 2026. Alcance parcial de tareas 27/28/30; no constituye cierre de los reportes históricos ni de sus dependencias.

## Contrato implementado

- Una fuente `DashboardQuery` comparte criterios entre pantalla y PDF: activos, nombre, mapa, tipo, clasificación y fase/publicación.
- `phase=cgft` selecciona CGFT no publicado; `phase=published` selecciona publicados incluso con fecha futura. Sin fase incluye ambas poblaciones una sola vez.
- `classification=generated|update|unknown`; `unknown` selecciona NULL, sin asignar clasificación a datos anteriores. Desglose con el mismo alcance y denominador filtrado visible.
- Conteos por fase, publicación, clasificación y mapa agregados en base de datos. Las alertas recorren solo fechas escalares mediante cursor para conservar meses/años calendario exactos, incluidos fines de mes; no cargan seguimientos ni participantes.
- Pantalla pagina 20 manuales y carga seguimiento solo para esa página. PDF exporta toda la selección con los mismos filtros y conteos. El PDF completo todavía requiere memoria proporcional al tamaño exportado por DomPDF.
- Indicadores por fase/publicación y distribución por mapa permiten consultar el listado conservando los demás filtros. Cambio de mapa limpia tipo para evitar combinación cruzada.
- Notas/observaciones excluidas. Inicio desconocido explícito; publicaciones futuras rotuladas y tiempos hasta hoy conforme al cálculo del modelo.

## Verificación técnica

`DashboardQueryTest`: conteos sin duplicar CGFT/publicados futuros; inactivos excluidos; ausencia de carga de seguimiento en agregados; filtros nombre/mapa/tipo/clasificación; paginación frente a exportación completa; límites de calendario de alertas; filtros inválidos; pantalla/PDF sin observaciones; antecedentes de clasificación desconocidos sin default; presentación de inicio desconocido y publicación futura.

Resultado: **10 pruebas y 54 aserciones correctas**. Incluye publicado directo sin tareas: «Sin seguimiento previo» en lugar de 0 % interpretado como incumplimiento. Sintaxis PHP de controlador/servicio/pruebas correcta; `git diff --check` de archivos asignados sin errores. No representa éxito de la suite global.

Comando aislado (SQLite en memoria, sin MySQL):

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit --filter DashboardQueryTest
```

Pendiente de integración por el agente principal: comprobación visual de navegación, responsive, enlaces y filtros en navegador. No se certifica aquí. El render PDF se comprueba por petición HTTP y plantilla, no por inspección visual de páginas.

## Límites pendientes

No se inventan permanencias históricas, productividad anual, referencia institucional ni proyecciones. Las tareas 29/31/32/33–36 permanecen fuera de este bloque. La clasificación requiere la migración aditiva preparada por el agente principal; no se ejecuta sobre datos existentes desde esta tarea. No se afirma rendimiento de PDF con exportaciones masivas ni verificación visual final mientras no exista evidencia correspondiente.

## Aclaración vigente — 25/10/2026

Las migraciones, comprobaciones visuales y otros módulos descritos como pendientes en la evidencia inicial se completaron posteriormente; consultar PLAN_IMPLEMENTACION.md. No obstante, el dashboard analítico NO está cerrado: hay tarjetas y barras simples por mapa, pero faltan pasteles/donas, barras comparativas completas y series históricas gráficas. La tarea28 queda parcial en este aspecto. El trabajo futuro está registrado en TAREAS_PENDIENTES.md, G01–G08. Esta actualización es documental, sin cambios de código ni ejecución de pruebas.

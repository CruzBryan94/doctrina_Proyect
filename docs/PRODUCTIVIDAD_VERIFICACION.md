# Productividad histórica — contrato y evidencia

Fecha de cierre técnico: 25 de octubre de 2026. Tareas 29/30 implementadas sobre evidencia de permanencias disponible; cierre funcional sujeto a las verificaciones de navegador/PDF e integración documentadas por el agente principal.

## Consulta

- Rutas `dashboard.history` y `reports.history`, permisos `dashboard.view`/`reports.view`; la comprobación de cuenta activa se conserva antes de autorización de permisos.
- Año calendario actual por defecto. Rango opcional Desde/Hasta reemplaza el año y se compara con el mismo rango del año anterior, usando años calendario y 28 de febrero cuando corresponde.
- Filtros compartidos por nombre/mapa/tipo/clasificación; clasificación desconocida conserva NULL. Se consulta la condición activa y clasificación vigentes, no una fotografía de estados pasados.
- Publicaciones: manuales actualmente publicados, por `published_on` declarada, incluidos años futuros. No se usa `created_at` como fecha efectiva. Publicados sin fecha se informan como cobertura faltante.
- Envíos a CGFT: permanencia con `entry_basis=transition` y evento de entrada `advance`, usando `entered_on`. Se excluyen alta directa y retrocesos. Dos envíos del mismo manual dentro de un período cuentan una vez.
- Permanencia: entrada fechada anterior o igual al final y salida posterior o igual al comienzo; incluye entradas anteriores al período. Una vez por manual/fase/período. No sumar fases como manuales diferentes.
- Toda permanencia se limita hasta hoy: una visita abierta no acredita años futuros. Si existe evento de salida sin fecha, se acredita únicamente el día de entrada conocido, sin extender una permanencia cuya duración se ignora. Entrada desconocida no se asigna a un período.
- Se muestra cobertura global de la selección: manuales con alguna entrada fechada, sin visitas, entradas/salidas desconocidas y publicaciones sin fecha. Tener una visita fechada no acredita un recorrido completo.
- Indicadores abren el listado correspondiente, paginado a 25 manuales, con nombres enlazados a la ficha. El listado agrupa reingresos e identifica su cantidad. PDF A4 horizontal usa el mismo servicio y exporta toda la selección, sin notas ni dependencia de JavaScript.

## Verificación técnica

`ManualProductivityTest`: **8 pruebas y 43 aserciones correctas**, incluyendo intersecciones y límites inclusivos, reingresos, fecha efectiva frente a digitación, altas directas/retrocesos CGFT excluidos, cobertura desconocida, futuro de publicaciones frente a permanencia, filtros coherentes, comparación calendario/bisiesto, paginación/PDF completo, notas excluidas y acceso supervisor/none/inactivo.

Conjunto dirigido final `ManualProductivityTest|DashboardQueryTest`: **18 pruebas y 97 aserciones correctas**, SQLite en memoria. No representa éxito de toda la suite.

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit --filter 'ManualProductivityTest|DashboardQueryTest'
```

## Límites

La cobertura depende de visitas nuevas y reconstrucción demostrable de tarea 16; no se completan intervalos a partir de la fase actual ni fechas de registro. Publicaciones anuladas cuya fecha vigente se eliminó no se reconstruyen sin evidencia adecuada. El PDF completo depende de memoria DomPDF proporcional al listado exportado. Pruebas HTTP verifican generación y contenido de plantilla; no certifican presentación visual del PDF. Navegación responsive, interacción real y render visual final quedan a cargo de la comprobación del agente principal.

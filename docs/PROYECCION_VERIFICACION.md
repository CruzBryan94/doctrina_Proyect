# Verificación de plazos y cronograma

## Alcance implementado

- Duración global en meses por fase; configuración inicial de tres meses y rango administrativo de 1–120. Cambios solo por admin activo; auditoría separada conserva actor y valores anterior/nuevo. `old_months` más `lockForUpdate` evita guardar una configuración desactualizada (HTTP 409).
- Alertas de fase calculadas con meses calendario `addMonthsNoOverflow`; igualdad con el límite no alerta, un día después sí. Investigación conserva el aviso anual `addYearNoOverflow`. Planificación y publicados no reciben alerta de plazo; una publicación futura tampoco entra al cronograma.
- El cálculo de duración es compartido por Manual, resumen del dashboard, matriz y PDF. Cache request-scoped evita repetir consultas de configuración por fila; las fechas de eventos, estados y actividades no se modifican.
- El cronograma muestra la duración transcurrida como real y el resto como proyectado. Sin fecha de inicio mantiene la fila visible y sin barras. Si el plazo corriente quedó atrás, las fases siguientes comienzan en la fecha de consulta y se rotulan como ejemplo. Planificación no alerta; si su periodo quedó atrás, las siguientes fases igualmente parten de la fecha de consulta como ejemplo.
- Filtros existentes de texto, mapa, tipo, fase y clasificación se conservan; mes y año inicial y horizonte de 6, 12, 18 o 24 meses ajustan el calendario informativo.

## Archivos del bloque

`database/migrations/2026_10_25_000009_create_phase_durations.php`, modelos `PhaseDuration`/`PhaseDurationAudit`, `PhaseDurationService`, `ManualProjection`, controladores `PhaseDurationController`/`ProjectionController`, vistas `phase-durations/index` y `projection/index`, ajustes a `Manual`, `DashboardQuery`, `AppServiceProvider`, matriz, dashboard y PDF, CSS central ARTEMIS y `tests/Feature/PhaseDurationProjectionTest.php`.

Las rutas (`phase-durations.index/update`, `dashboard.schedule`) y enlaces de navegación se integraron en `routes/web.php` y `layouts/artemis` por el agente raíz. La migración fue aplicada en MySQL por el agente raíz; esta tarea no la ejecutó.

## Verificación

- `PhaseDurationProjectionTest.php`: 7 pruebas, incluye borde de fin de mes, igualdad/exceso, actualización global auditada sin alterar fechas ni seguimiento, inicio desconocido, publicación futura, vencimiento de fase, Planificación vencida sin alerta, auditoría de conflictos HTTP y carga de fases sin N+1.
- `DashboardQueryTest.php`: 10 pruebas / 54 aserciones, correctas con duraciones predeterminadas y criterios de publicación.
- `git diff --check`: correcto; Git avisó conversiones CRLF/LF en archivos preexistentes modificados.
- QA visual del agente raíz: cronograma sin inicio visible sin barras; Investigación iniciada 25/06 vencida proyectó siguientes fases desde 25/10 como ejemplo; al cambiar duración de Investigación de 3 a 4 meses, la igualdad con el día consultado quitó alerta y desplazó las fases futuras de otro manual sin alterar su inicio. Colores y leyenda por fase diferenciables; consola sin errores.
- La migración 000009 fue aplicada por el agente raíz en MySQL conservando su cuenta/hash admin. No se ejecutó build de frontend.

## Comando reproducible

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor\phpunit\phpunit\phpunit tests\Feature\PhaseDurationProjectionTest.php
```

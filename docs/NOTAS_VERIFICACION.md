# Verificación de notas de fase

- Nota nullable por manual/fase, límite 1000 caracteres y auditoría en tablas separadas con actor y valores anterior/nuevo; la limpieza queda auditada. Sin nuevos eventos generales ni relaciones en el snapshot de `ManualWorkflow`.
- Admin y rol de fase editan incluso tras transferir; roles funcionales activos consultan contexto. `none`, cuentas inactivas y roles de otras fases no escriben. El lock del manual y `lock_version` producen 409 si la versión quedó obsoleta.
- Prueba dirigida: `ManualPhaseNotesTest.php`, 8 pruebas y 68 aserciones correctas. Incluye HTTP, validación, conflicto atómico, limpieza y ausencia del sentinel en ficha, historial general, dashboard y PDF.
- `TransferredPhaseVisits` consulta cada permanencia cerrada independientemente, precarga datos para evitar N+1, separa paginación `transferred_page` y conserva `q`, mapa, tipo, clasificación y otras páginas del query string. Retrocesos se incluyen rotulados como tales.
- Integración reportada por root: migración aplicada a MySQL con cuenta/hash de admin conservados; rutas, enlace de matriz, sección transferidos y flujo de guardado/auditoría revisados en navegador.

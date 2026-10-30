# Referencias institucionales — verificación

Cada mapa conserva una referencia esperada nullable e independiente, con versión de bloqueo optimista. La conciliación cuenta mediante agregación SQL todos los manuales activos del mapa: las cinco fases no publicadas y Publicado, incluidos futuros, Generados y Actualizaciones. Los inactivos quedan fuera. No se ajustan registros ni referencias para forzar igualdad.

Solo cuentas activas con rol admin pueden guardar o limpiar el total (0–1.000.000); cada cambio registra actor y valores anterior/nuevo. La consulta está disponible a cuentas activas funcionales y supervisor. Sin referencia, la diferencia se presenta como no disponible.

Rutas para integrar: `GET /references/institutional` (`references.index`) y `PUT /admin/references/institutional/{doctrinalMap}` (`references.update`). La escritura requiere comprobar rol admin en middleware y servicio; el permiso users.manage por sí solo no basta. No ejecutar migraciones reales en este cambio.

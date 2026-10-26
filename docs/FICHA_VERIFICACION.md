# Ficha individual de manual — verificación

Fecha: 25 de octubre de 2026, America/Guayaquil. Tarea 26 implementada; validación visual a cargo de integración.

## Alcance

- Ruta de consulta `manuals.show` (`GET /manuals/{id}`), controller `ManualDossierController`, servicio `ManualDossier` y vista `manuals/show.blade.php`.
- Datos generales: mapa/tipo, clasificación o antecedente desconocido, código, estado activo/publicado, fase/subestado, actividad actual, fechas conocidas y observación general. Publicados con fecha futura se presentan como publicados con fecha declarada futura.
- Proceso: actividades y cumplimiento conservados, datos/responsable de Edición (incluida cuenta inactiva histórica), fechas y porcentaje propio de fase. Las fases anteriores sin inicio ni cumplimiento demostrado se presentan como antecedentes no acreditados; no se muestran como incumplidas. Las futuras se identifican como previstas sin atribuirles inicio real.
- Edición cerrada de seis actividades conserva su denominador histórico. Actividades archivadas se consultan aparte y no pesan en el avance vigente.
- Participantes y unidades se agrupan por comité. Se indica expresamente que son los vínculos actualmente conservados y no acreditan composiciones históricas.
- Permanencias paginadas a 15: entrada/salida efectiva, fecha de registro y origen; reingresos como filas separadas. Fecha efectiva desconocida no se reemplaza por digitación. `exit_event_id` con salida efectiva nula se presenta como salida acreditada de fecha desconocida. Si la tabla nueva aún no está instalada, se avisa y permanece disponible el resto de la ficha.
- Eventos generales paginados a 20, con actor y fecha de registro. Lista explícita de acciones/campos permitidos; no se exponen JSON bruto, motivos arbitrarios, eventos de notas ni campos desconocidos del payload. Los datos se escapan con Blade.
- Enlaces reutilizan formulario y matriz existentes. `view` controla consulta; `updateMetadata` controla acceso al formulario y `update` al seguimiento. La ficha no incorpora escrituras ni duplica formularios.

## Evidencia

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit tests/Feature/ManualDossierTest.php
```

Resultado: **10 pruebas, 111 aserciones, todas correctas** en SQLite en memoria, aproximadamente 2,7 segundos. Cubren roles funcionales/supervisor, permisos de operación por fase, admin, none, inactivo, invitado, URL inexistente, alta avanzada desconocida, publicado futuro, integrantes y responsable histórico inactivo, paginación de eventos/permanencias, campos secretos y notas excluidas, HTML escapado, archivo de seguimiento, seis actividades históricas y denominador 100 %, reingresos/fechas desconocidas y ausencia de cambios de versión/filas al consultar.

Además, sintaxis PHP de controller/servicio comprobada. La primera ejecución antes de existir la nueva tabla también comprobó el renderizado de la ficha con aviso de instalación pendiente; la última ejecución incluyó la tabla y las pruebas específicas de permanencias.

## Dependencias y límites

La ficha consulta la historia disponible sin reconstruirla. Reconstrucción y captura de permanencias pertenecen a tareas 15–16. No acredita datos que no existan, no inventa responsables/fechas y no modifica el esquema o datos al abrirse.

No hay notas de fase persistidas en esta entrega; la ficha ya excluye eventos y campos ajenos a su contrato. El historial general previo es otra pantalla y debe mantener esa separación si se incorporan notas posteriormente.

Pendiente de integración: revisión visual del diseño, navegación de secciones, enlaces a formulario/matriz, detalles plegables y paginación en navegador. La ficha no añade JavaScript ni dependencias de Internet.

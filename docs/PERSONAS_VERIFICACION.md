# Catálogo de integrantes e importación CSV — verificación

Fecha de trabajo: 24 de octubre de 2026 (America/Guayaquil). Tareas del plan: 21–23. Implementación sin migraciones ni modificaciones de datos de la base existente.

## Contrato de búsqueda y listado

- Administración: consulta GET `members.index?q=...`, 25 filas por página, total y navegación conservando la búsqueda; no se descarga el catálogo completo al navegador ni se usa traducción DataTables por CDN.
- `MemberDirectory::search` permite reutilizar el mismo filtro en el selector de comités. Cada palabra debe aparecer en nombre o cédula, en cualquier orden. Un término numérico busca prefijo de cédula; los demás buscan coincidencia parcial. Orden estable por grado, nombre e id en el listado.
- Grado relacionado por `committee_members.grades_id = grades.id`, incluida la selección en formularios. El código de grado se usa para presentar/ordenar y resolver el CSV; no se presume igual al id.
- Participación cuenta manuales distintos; una persona en varios comités del mismo manual cuenta una vez.
- Alta/edición individual valida grado existente, nombre, cédula de 10 dígitos única y celular opcional de hasta 20 caracteres. Las cédulas conservan ceros iniciales.

## Contrato CSV

1. Archivo UTF-8, con BOM opcional; máximo 2 MB y 5.000 filas no vacías. Cabecera exacta `cedula,nombre,grado_codigo,celular`. Admite coma o punto y coma, comillas CSV y nombre con separadores dentro de comillas. Celular puede estar vacío, pero debe existir su columna.
2. Revisión previa sin escrituras. Distingue nuevos, existentes, repetidos dentro del archivo y errores de fila. Un código de grado desconocido, nombre vacío, cédula que no tenga 10 dígitos o celular inválido impide incorporar esa fila. Archivo vacío, cabecera incorrecta, codificación inválida o exceso de tamaño/filas rechaza el archivo.
3. Revisión paginada a 50 filas. Los candidatos completos quedan en la sesión del usuario; el cliente solo envía token aleatorio, nunca los datos a insertar. Una nueva carga elimina la revisión anterior aun si el archivo nuevo falla. Revisión válida durante una hora.
4. Confirmación POST con CSRF y `catalogs.manage`, igual que la revisión y pantalla. Token de otra sesión, alterado o ya consumido no se acepta. Inactivos siguen bloqueados por el Gate existente.
5. Una transacción para todo el lote, tres intentos ante errores de concurrencia reconocidos por Laravel. Se omiten duplicados/existentes, se agregan solo nuevos y se conserva sin cambios cada integrante existente. Si desaparece un grado o sucede otro error inesperado, se revierte el lote entero. No se crean cuentas ni roles.
6. Cédula protegida por el índice único existente. La confirmación vuelve a consultar cada candidato con bloqueo; `createOrFirst` cubre la colisión de inserción. Una segunda confirmación o importación del mismo lote no duplica personas. Resumen final informa incorporados reales, omitidos y filas con errores.

La sesión configurada por defecto es de archivos y admite la revisión de este tamaño; no configurar sesiones en cookies para transportar lotes completos. El límite de 5.000 filas acota memoria y tamaño de sesión; la lectura CSV es incremental. No se incorporan dependencias ni índices sin evidencia adicional.

## Evidencia técnica

Comando ejecutado con SQLite en memoria, separado de MySQL:

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit tests/Feature/MemberDirectoryImportTest.php tests/Feature/CommitteeMemberCellphoneTest.php
```

Resultado final: **17 pruebas, 130 aserciones, todas correctas**, aproximadamente 4,1 segundos. Casos: listado/paginación/filtros, grado con código distinto de id, participación distinta, validación individual, ceros, revisión sin escrituras, duplicados, UTF-8/BOM/separadores, error de cabecera/archivo vacío, límite de tamaño/filas, token/expiración/carga nueva, permisos HTTP, relectura tras otra incorporación, resumen/idempotencia, conservación de existentes/cuentas y rollback por cambio de catálogo. Las pruebas de celular anteriores continúan correctas.

Escenario sintético de **30.000 personas**, SQLite en memoria, una ejecución comparativa en el mismo proceso:

| Consulta | Tiempo medido | Consultas | Registros hidratados |
| --- | ---: | ---: | ---: |
| Listado anterior completo (join corregido a id para comparación equivalente) | 247,80 ms | 1 | 30.000 |
| Listado nuevo paginado | 7,42 ms | 2 | 25 |
| Selector con prefijo de cédula | 8,15 ms | 2, incluida carga de grado | 10 |

El nuevo listado evita la hidratación completa. Las cifras dependen del motor/equipo y no son un compromiso de latencia ni certificación de MySQL. La prueba comparativa usa el algoritmo previo de carga completa con join a id, porque el join previo por código no devolvería estas personas con código distinto del id.

## Límites pendientes de verificación

- Validación visual en navegador a cargo de integración; las vistas ya fueron renderizadas mediante peticiones HTTP en pruebas.
- Prueba de inserciones simultáneas en dos conexiones MySQL y medición en el volumen real pendientes. Se verificaron el índice único existente, la transacción/rollback y el caso de cambio de base entre revisión y confirmación; no se presenta ese caso como simultaneidad física.
- No se ejecutaron migraciones en la base existente ni se importaron integrantes reales durante esta implementación.

## Actualización de integración — 26/10/2026

La concurrencia física CSV que esta nota dejaba pendiente se probó después en dos procesos MySQL sobre el esquema QA dedicado: una incorporación insertó1 fila y la otra omitió el duplicado, sin duplicar la persona. El ensayo manual de previsualización/importación se completó en una copia SQLite desechable; la cuenta/copia temporal fue retirada. El benchmark de volumen se conserva opt-in y no se repitió en la tarea37. Ver `INTEGRACION_VERIFICACION.md`.

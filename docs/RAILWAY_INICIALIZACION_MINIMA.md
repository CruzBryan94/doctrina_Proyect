# Inicialización mínima de ARTEMIS en Railway

Fecha: 27 de octubre de 2026, America/Guayaquil.

## Autorización y resultado

El usuario autorizó preparar el sistema mientras se ausenta, limitando la carga a estructura y relaciones, catálogos y un usuario administrador. Esta autorización sustituye para esta carga inicial el alcance anterior de copiar todos los datos. No autoriza migraciones automáticas futuras ni restablecer credenciales.

**Carga ejecutada y verificada en la base `railway` del proyecto Railway existente.** La aplicación quedó fuera de mantenimiento y el formulario público de login responde HTTP 200. No se creó otro servicio ni se publicó otra imagen; no se hizo commit ni push.

## Origen, destino y paquete

- Origen: MySQL local 8.4.3, consultado mediante una transacción de solo lectura. No se modificó el esquema ni los datos locales.
- Destino: MySQL Railway 8.4.13, usando la conexión privada del servicio Laravel. Se verificó que `SHOW FULL TABLES` no devolvía objetos tanto en preflight como inmediatamente antes de crear tablas.
- Paquete privado utilizado: `storage/app/backups/railway-minimal-20261027-172016/package.private.json`.
- SHA-256 del paquete: `6ed93f95dc0a5fa191b5f56a6c4c8e92ea133c5443006cc4d51567fcd51f69ac`.
- El paquete y los scripts privados de transporte están ignorados por Git. Contienen el hash de contraseña del administrador y deben conservarse como información privada; no adjuntarlos a PR ni compartirlos.
- Se guardó el inventario previo vacío en `target-before.json`, junto al manifiesto local. Al estar vacío el destino, no existían filas anteriores que respaldar o reemplazar.

## Contenido cargado

Se crearon **33 tablas** con motores InnoDB, índices y claves foráneas copiados de la estructura vigente mediante `SHOW CREATE TABLE`. Se verificaron **39 referencias de columna de clave foránea**, sin huérfanos. Se omitieron los contadores AUTO_INCREMENT históricos de las opciones de tabla para no arrastrar la numeración de operaciones locales vacías en destino.

| Tabla | Filas |
| --- | ---: |
| grades | 17 |
| committee_type | 4 |
| military_units | 4 |
| doctrinal_maps | 2 |
| manual_types | 10 |
| manual_phases | 5 |
| catalog_subphases | 44 |
| phase_durations | 5 |
| roles | 9 |
| permissions | 10 |
| role_has_permissions | 30 |
| users | 1 |
| model_has_roles | 1 |
| migrations | 31 |

`catalog_subphases` conserva el catálogo existente, incluidas plantillas históricas inactivas; esto no carga seguimiento de manuales. `phase_durations` conserva configuración de plazos. No se copiaron valores estadísticos de `institutional_references`.

Todas las demás tablas tienen **cero filas**, incluyendo manuales, integrantes, asignaciones, estados/actividades por manual, notas, visitas, eventos, auditorías, referencias institucionales, permisos directos de usuarios, tokens y trabajos fallidos.

La única cuenta es **`admin@admin.com`**, activa, con el rol `admin` y sin cambio de contraseña pendiente. Se conservó exactamente su hash local existente; no se generó ni restableció su contraseña. Se dejaron vacíos remember token y google_id para no transportar tokens ni vínculos de autenticación retirados. No se cargaron otras cuentas ni personas.

## Procedimiento ejecutado

1. Verificación del acceso autenticado, del proyecto/servicio autorizados, versión MySQL y destino vacío.
2. Exportación selectiva de estructura y filas permitidas, con validación local de referencias y checksum; sin arrancar Laravel local ni ejecutar seeders.
3. Preflight remoto del paquete, contexto productivo y base vacía; bloqueo exclusivo mediante `GET_LOCK`.
4. Modo mantenimiento temporal del servicio web, seguido de creación de tablas en orden de dependencias. Sin `DROP`, `TRUNCATE`, borrado o desactivación de claves foráneas.
5. Inserción de catálogos/administrador en transacción. Comprobación de todas las filas contra el paquete mediante hashes normalizados, conteos y ausencia de referencias huérfanas, antes del commit de datos.
6. Limpieza acotada de caché de permisos y verificación del administrador con Laravel: cuenta única/activa, rol admin y permisos de dashboard, usuarios y catálogos.
7. Consulta del dashboard vacío ejecutada correctamente con MySQL real.
8. `migrate:status` de solo lectura mostró las 31 entradas registradas; no se ejecutó `migrate`, `fresh`, rollback ni `db:seed` en Railway. La tabla de migraciones se transfirió como metadato coherente con el esquema copiado.
9. Retiro de mantenimiento y GET público `/login`: HTTP 200, formulario presente.

La DDL de MySQL tiene commits implícitos; por eso el importador se niega a correr sobre una base no vacía y no borra objetos para reintentar. No volver a ejecutarlo sobre esta base ya inicializada.

## Scripts y límites

- `scripts/deployment/prepare-minimal-database.php`: exportación de solo lectura; exige un único admin activo y verifica relaciones.
- `scripts/deployment/import-minimal-database.php`: validación/creación/inserción acotada al contexto Railway y base vacía autorizados. No se conecta desde el workflow ni desde el arranque.
- `scripts/deployment/Invoke-RailwayPhp.ps1`: envía PHP por entrada estándar de SSH, sin contraseñas ni hashes en argumentos del comando.

Estos scripts no están incorporados al CI, Docker ENTRYPOINT ni pre-deploy. No automatizan actualizaciones de esquema posteriores. La aplicación/código de negocio y las variables remotas no se cambiaron durante esta inicialización.

No se efectuó un login HTTP con contraseña, porque no se solicitó ni se obtuvo su texto claro. Se verificaron el hash transferido, el estado/rol/permisos por Laravel, las consultas del dashboard y la disponibilidad del formulario. El usuario debe ingresar con su contraseña local habitual. No se crearon registros de prueba para verificar escrituras en producción.

Quedan fuera de esta carga: alta de personas/cuentas adicionales, importación de manuales/historia, cambios de autenticación, publicación de código, configuración de backups periódicos y certificación de costo mensual.

## Versión propuesta

Origen: `dev` o `devDoctrina`. Destino: `produccion`.

Problema y comportamiento resultante:

## Verificación

- [ ] Revisé el diff completo y los cambios de infraestructura/CI.
- [ ] `ARTEMIS CI required` pasó en el último commit, sin omitir fallos.
- [ ] Probé los flujos afectados y revisé PDF/CSV si corresponde.
- [ ] No incluí `.env`, credenciales, respaldos ni datos personales.
- [ ] No se ejecutan migraciones ni seeders durante build o arranque.
- [ ] Confirmé compatibilidad con el esquema actual. Si hay cambios de esquema, existe un procedimiento separado aprobado con respaldo/restauración; este PR no autoriza aplicarlos.
- [ ] Identifiqué el SHA/versión anterior para rollback y el impacto en sesiones/archivos.

## Aprobación y operación

Revisor humano:

Pruebas pendientes/riesgos conocidos:

Al fusionar manualmente, Railway podrá desplegar este commit si está conectado a `produccion`, con Autodeploy y Wait for CI habilitados. No fusionar mientras existan verificaciones fallidas o pendientes.

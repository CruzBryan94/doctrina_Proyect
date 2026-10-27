# Verificación integrada ARTEMIS — tarea 37

Fecha: 26 de octubre de 2026. Alcance: revisión de criterios R01–R18 con evidencia dirigida, suite Feature en SQLite, rutas activas en MySQL y recorridos de navegador ya cerrados. No incluye el benchmark opcional de 30.000 personas ni certificación de rutas heredadas desconectadas.

## Resultado ejecutivo

La matriz siguiente refleja el estado actual respaldado por pruebas, verificación manual o ambas. **E** = implementado y verificado en las rutas/casos activos indicados; **P** = funcionalidad implementada con límite explícito de cobertura; **N** = aprobado pero pendiente de implementación. Los estados E/P/N de la matriz de brechas en REQUERIMIENTOS.md conservan la fotografía del diagnóstico original del 24/10/2026.

| Requisito | Estado actual | Evidencia y límite relevante |
| --- | --- | --- |
| R01 Mapas, tipos y clasificación | E | `AdvancedManualEntryTest`, `DashboardQueryTest`; altas y filtros de clasificación/mapa. |
| R02 Catálogos y porcentajes | E | `VariableActivityCatalogTest`, `DoctrineWorkflowTest`; catálogo vigente, cuarta reunión, denominadores y evidencia histórica cerrada. |
| R03 Incorporación avanzada | E | `AdvancedManualEntryTest`; antecedentes desconocidos y autorización por rol/fase sin inventar inicio o historia. |
| R04 Comités, integrantes y unidades | E | `CommitteeMemberCellphoneTest`, `MemberDirectoryImportTest`, `DoctrineWorkflowTest`; opciones remotas, relaciones y permisos. |
| R05 Matriz, guardado y transición | E | `DoctrineWorkflowTest`, `ManualPhaseHistoryTest`; una actividad por petición, validación de fase, eventos y control de versión. |
| R06 Edición compatible | E | `EditionWorkflowTest`, `VariableActivityCatalogTest`; tres tareas nuevas y seis históricas cerradas conservadas. |
| R07 Roles, consulta y publicados | E | `DoctrineWorkflowTest`, `AdvancedManualEntryTest`, `ManualDossierTest`; lectura transversal separada de escritura y administración. |
| R08 Notas de fase | E | `ManualPhaseNotesTest`; auditoría separada, transferencia y ausencia de notas en ficha, historial general, dashboard y PDF. |
| R09 Historia y permanencias | P | `ManualPhaseHistoryTest`, `ManualProductivityTest`; fechas efectivas y reingresos probados. La historia anterior a la captura estructurada o sin evento fechado permanece desconocida y se excluye, no se reconstruye por inferencia. |
| R10 Dashboard, productividad y PDF | E | `DashboardQueryTest`, `ManualProductivityTest`; gráficos G01–G08, filtros y coherencia web/PDF. Recorridos manuales reciente CSV/CGFT actualizados en TAREAS_PENDIENTES.md. |
| R11 Referencia institucional | E | `InstitutionalReferenceTest`; totales independientes por mapa, cero distinto de sin configurar, escritura admin auditada y conflicto de versión. |
| R12 Plazos, proyección y alertas | E | `PhaseDurationProjectionTest`; meses calendario, igualdad/exceso, ausencia de inicio y exclusión de publicados. UI corroborada en QA. |
| R13 Personas, cuentas y CSV | E | `MemberDirectoryImportTest`, `AccountAccessTest`; revisión sin escritura, solo nuevos, idempotencia, rollback y permisos. Concurrencia CSV en dos procesos MySQL QA: una inserción y una omisión, sin duplicado. El benchmark de volumen no se repitió en esta tarea. |
| R14 Acceso y restablecimiento local | E | `AccountAccessTest`, `RegistrationTest`; rol none, pantalla obligatoria, cambio de clave, acceso directo y hash no reutilizable. Recuperación por correo no forma parte del alcance acordado. |
| R15 Identidad y UX ARTEMIS | E | `UNIFICACION_VISUAL_VERIFICACION.md`; escritorio/móvil, navegación, foco, modales y recursos locales verificados en rutas activas. |
| R16 Ficha y navegación | E | `ManualDossierTest`; consulta transversal, permisos de edición separados y eventos/campos expuestos por lista permitida. Navegación HTTP de ficha verificada. |
| R17 Integridad y operación | P | Transacciones, auditoría, `lock_version`/409 e índices probados por módulo. Carrera del último admin reproducida y corregida en MySQL QA: resultado 302/422, permanece un administrador. No se afirma que todas las rutas heredadas ni todas las intercalaciones de cada escritura se hayan probado físicamente. |
| R18 Entrega y documentación | E | Plan, tareas y verificaciones actualizados con comandos, resultados y límites; `git diff --check` limpio salvo avisos de normalización CRLF ya existentes. |

No quedan requisitos aprobados con estado N en las rutas funcionales acordadas. P en R09/R17 documenta límites reales de evidencia, no tareas funcionales abiertas.

## Matriz representativa de roles y URL

| Actor | Consulta / ruta directa | Escritura directa | Evidencia |
| --- | --- | --- | --- |
| Invitado | Módulos protegidos redirigen a `login`; `GET /login` disponible. | No accede a operaciones protegidas. | `DoctrineWorkflowTest`, `ManualPhaseNotesTest`, `route:list`. |
| `none` activo | `dashboard`, `reports.manuals`, `manuals.index`, ficha y referencias bloqueadas (403); solo acceso sin módulos. | Alta/seguimiento/notas/cuentas/referencias bloqueados. | `DoctrineWorkflowTest`, `AdvancedManualEntryTest`, `ManualDossierTest`, `InstitutionalReferenceTest`, `ManualPhaseNotesTest`. |
| Supervisor y roles funcionales | Consulta transversal dashboard, reportes, ficha e historial dentro del contrato activo. | No obtiene escritura administrativa por la sola consulta; permisos de fase se evalúan en servidor. | `DoctrineWorkflowTest`, `AdvancedManualEntryTest`, `ManualDossierTest`; revisión de rutas activas. |
| Responsable funcional | Consulta y opera solamente con permiso de su fase; notas permitidas de una fase transferida según regla acordada. | Ruta directa ajena a su fase devuelve 403; la vista no sustituye la autorización del servidor. | `DoctrineWorkflowTest`, `ManualPhaseNotesTest`. |
| Administrador activo | Consulta y administración de cuentas, referencias, duración y metadatos autorizados. | Escrituras validadas y auditadas; se conserva siempre al menos un admin. | `AccountAccessTest`, `InstitutionalReferenceTest`, `PhaseDurationProjectionTest`, concurrencia MySQL QA. |
| Cuenta inactiva / permiso directo sin rol | No se toma como rol funcional activo. | `users.manage` directo sin rol admin y admin inactivo reciben 403; URLs directas cubiertas. | `AccountAccessTest`, `InstitutionalReferenceTest`, `AdvancedManualEntryTest`. |
| Cambio obligatorio de contraseña | Tras login, incluso URL directa a dashboard redirige a `password.change.show`. | Solo permite completar el cambio válido; la provisional no queda habilitada para repetir login tras guardarse la nueva. | `AccountAccessTest`; comprobación de pantalla y redirección en QA navegador. |

La matriz es representativa de los límites de acceso acordados y los módulos con mayor impacto; no es una repetición exhaustiva de cada combinación rol × URL heredada.

## Comprobaciones integradas ejecutadas

- Suite Feature completa, PHP 8.3.29 / PHPUnit 10.5.38 / SQLite en memoria: **161 pruebas, 1.325 aserciones, 142 correctas, 16 fallidas, 2 errores y 1 omitida**. Las 18 incidencias corresponden a pruebas plantilla sin ruta activa: `EmailVerificationTest` espera `verification.verify`; `PasswordConfirmationTest`, `PasswordResetTest`, `PasswordUpdateTest` y `ProfileTest` esperan rutas de verificación, confirmación, recuperación/perfil no conectadas; `ExampleTest` espera que `/` responda 200, pero el inicio redirige al flujo actual. `route:list` confirma que las rutas activas incluyen login/registro y cambio administrativo local, sin aquellos endpoints scaffold. No se presenta el resultado amplio como éxito global ni como regresión de rutas activas.
- Suite de módulos ARTEMIS seleccionados: **137 pruebas, 1.283 aserciones, 136 correctas y 1 omitida**. Todas correctas. La omisión es el benchmark opt-in en `MemberDirectoryImportTest`; no se definió `RUN_MEMBER_VOLUME_BENCHMARK`.
- Migraciones: `migrate:status` de MySQL informó las **31 migraciones aplicadas**. Las migraciones de catálogos, permanencias e historia tienen además pruebas SQLite en memoria/archivo/reapertura. No se ejecutó una migración durante la tarea37. La aplicación previa en MySQL local quedó auditada y separada de los datos reales; el hash, correo, nombre, estado y roles del administrador activo coinciden con la firma conservada.
- HTTP de solo lectura sobre MySQL: nueve rutas (referencias, cronograma, plazos, alta de cuenta, dashboard actual/histórico, matriz Investigación, ficha y nota contextual) respondieron **200**.
- Comprobación de preservación de solo lectura: 1 administrador activo y firma de identidad/credencial/estado/roles igual a la línea base guardada; 13 integrantes, 12 cuentas y 28 manuales observados. El conteo 28 sustituye la fotografía de 26 manuales tomada el 25/10; esta verificación no atribuye el incremento a la ejecución actual ni modifica registros.
- Concurrencia física en esquemas MySQL QA separados, evidencia del 25/10: importaciones CSV concurrentes insertaron una sola fila; la otra se omitió por duplicada. Dos demociones de admin produjeron una respuesta 302 y una 422; quedó exactamente un admin activo. Los conflictos optimistas/versionados se cubren además con pruebas dirigidas por módulo.
- Datos: la suite de PHPUnit fuerza SQLite `:memory:`. Los dos recorridos de navegador CSV/CGFT usaron copia SQLite desechable y ya fueron retirados; MySQL no recibió escrituras de esta verificación integrada. Ningún seed general ni borrado/reconstrucción de base ejecutado.

## Límites que permanecen

- R09 no permite declarar completa una historia que no está fechada o no tiene evento fuente. En consultas se reporta cobertura disponible; desconocido no equivale a incumplimiento.
- La suite completa tiene las 18 incidencias heredadas enumeradas arriba. Se mantienen visibles y separadas de los **137** casos activos/pertinentes correctos; no se habilitaron rutas de verificación por correo, reset tradicional, perfil ni borrado de cuenta para hacer pasar las plantillas.
- No se volvió a medir latencia ni volumen; el benchmark de 30.000 personas sigue opt-in y no era requisito de esta tarea.
- Se verificaron endpoints principales, combinaciones de rol representativas y escenarios de concurrencia críticos conocidos. No se certifica toda permutación de URL heredadas ni cada carrera posible del sistema.

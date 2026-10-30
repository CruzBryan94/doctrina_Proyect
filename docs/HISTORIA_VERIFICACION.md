# Permanencias y catálogos: contrato y verificación

Fecha: 25 de octubre de 2026. Alcance: tareas 06–08 y 15–16. Implementación autorizada por la instrucción de ejecución autónoma; no se han aplicado migraciones ni reconstrucción sobre la base existente.

## Catálogos

- Catálogo vigente: 35 actividades activas; Investigación 11+N (N=2–6), Experimentación 6+M (M=2–5). La cuarta reunión usa `research.validation-meeting.4`; se conservan las claves previas y `experimentation.12` del informe final.
- Estados Research/Experimentación nuevos: `catalog_version=3`. Las fases históricas cerradas anteriores conservan versión 2 y sus cantidades/porcentajes acreditados. Edición conserva su versionado 1/2.
- La migración preparada `2026_10_24_000002_upgrade_variable_activity_catalogs.php` añade cuarta reunión pendiente en fases abiertas, archiva mediante `is_legacy` los avances 6–8 retirados, conserva sus IDs, fechas, cumplimientos y eventos, y reduce M a 5 únicamente en fases no superadas. Guarda un evento con el estado anterior y el catálogo. No completa automáticamente actividades históricas ni cambia el esquema.
- Fases cerradas con M>5 admiten conservar ese valor al editar metadatos; altas nuevas y cambios actuales rechazan cantidades fuera de 2–5. La reapertura usa la versión vigente y conserva evidencia retirada como histórica.
- Para aplicar en la base existente se necesita respaldo verificable y prueba en copia. La reversión requiere restauración revisada del respaldo; no incluye borrados automáticos.

## Permanencias

`manual_phase_visits` / `App\Models\ManualPhaseVisit` conserva una fila por permanencia, incluso al reingresar:

| Campo | Significado |
| --- | --- |
| `manual_id`, `manual_phase_id` | Manual y fase acreditada. |
| `entered_on`, `exited_on` | Fechas efectivas, nullable si no se conocen. |
| `entered_recorded_at`, `exited_recorded_at` | Momento de registro del evento. |
| `entry_event_id`, `exit_event_id` | Evidencia de entrada/salida. |
| `entry_basis` | `phase_start`, `transition` o `unknown`. |
| `source` | `live` o `reconstructed`. |

Relaciones: `phase()`, `entryEvent()`, `exitEvent()`. Una visita sigue abierta cuando `exit_event_id` es null. Si hay evento de salida y `exited_on` es null, la salida está acreditada pero su fecha es desconocida.

Las nuevas altas incorporan únicamente su fase conocida. Una publicación directa no genera permanencia ficticia en CGFT. La actividad de seguimiento de publicaciones directas sin estados/actividades se rechaza con 422, sin afectar la edición autorizada de metadatos.

El evento, el cambio del manual y la permanencia se guardan en una transacción bajo bloqueo del manual. Un error de historia revierte también transición, versión y evento. El avance usa la fecha máxima de cumplimiento de la fase origen como fecha efectiva; un reingreso no puede cerrarse antes de su entrada acreditada. Retrocesos/despublicación usan la fecha actual y quedan auditados. La publicación declarada puede ser futura y se conserva separada de la salida efectiva de CGFT.

Registrar/corregir el inicio completa una entrada `unknown`/`phase_start`, conservando el antes/después en los eventos. No altera una entrada `transition`: la transferencia y el inicio de trabajo son hechos distintos.

`overlappingPeriod(desde, hasta)` incluye solo solapamientos acreditados y limita visitas abiertas al día actual. Una entrada conocida anterior al período cuenta si su salida posterior o continuidad abierta lo acredita. Una salida de fecha desconocida solo acredita el día de entrada conocido dentro del período. La ausencia de fechas no se transforma en permanencia supuesta; deben mostrarse avisos de cobertura. Para estadísticas, contar manuales distintos por fase/período, no filas de visita. Envíos CGFT: entrada con `entry_basis=transition` y `entryEvent.action=advance`, diferenciando altas directas y retrocesos.

## Reconstrucción preparada

La migración aditiva `2026_10_24_000003_create_manual_phase_visits.php` crea la tabla sin backfill automático. El comando siguiente revisa sin persistir por defecto:

```powershell
php artisan manuals:reconstruct-history
php artisan manuals:reconstruct-history --manual=123
```

Después del respaldo y la revisión en copia, `--apply` solicita persistencia explícita. No se ejecutó sobre datos existentes durante esta entrega. Solo utiliza eventos originales; no deduce transferencias ni cumplimientos desde estados únicos o actividades actuales. Una transferencia antigua sin `effective_on` conserva fecha desconocida. No inventa participantes antiguos. Omite manuales con visitas ya registradas, evitando duplicados o sobrescribir evidencia viva; cada manual se procesa en una transacción. Si se necesita ampliar una reconstrucción ya aplicada, debe diseñarse una revisión específica, no borrar/recrear sus visitas.

## Verificaciones realizadas

Configuración comprobada: PHPUnit fuerza `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` y `DATABASE_URL` vacío. No se utilizó MySQL para `RefreshDatabase`.

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit --filter 'DoctrineWorkflowTest|EditionWorkflowTest|VariableActivityCatalogTest|ManualPhaseHistoryTest'
```

Resultado de integración: **55 pruebas, 641 aserciones correctas**. Incluye límites y denominadores; cuarta reunión obligatoria; IDs/fechas/versiones históricas; migración en memoria, archivo y reapertura; fecha efectiva CGFT del 10 registrada el 20; reingresos sin doble conteo por manual; inicio desconocido/corrección; publicación futura; publicación directa sin seguimiento ficticio; reversión atómica ante error de historia; reconstrucción de solo eventos, revisión sin escritura e idempotencia. `git diff --check` correcto para los archivos asignados.

No acredita una aplicación de migraciones en MySQL, restauración de respaldo ni cobertura completa de los eventos existentes. La verificación visual y de las pantallas integradas corresponde a la integración principal. Tareas 06–08 y 15–16 quedan implementadas y verificadas técnicamente en aislamiento; despliegue y validación de datos existentes pendientes.

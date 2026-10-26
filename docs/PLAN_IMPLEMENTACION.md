# ARTEMIS — plan modular de implementación

Versión: 24 de octubre de 2026. Basado en [REQUERIMIENTOS](REQUERIMIENTOS.md) y [AGENTS.MD](../AGENTS.MD). **Implementación autónoma autorizada por el usuario el 24/10/2026. La restricción documental anterior queda sustituida para esta ejecución; se mantienen las restricciones de datos y migraciones. Estado actualizado al final del documento.**

## 1. Convenciones y condiciones de trabajo

- **P1:** base funcional, datos, permisos y operación; **P2:** consulta/presentación/productividad; **P3:** proyección. Prioridad no equivale a permiso de ejecución.
- Dependencias son IDs de tareas que deben estar aceptadas antes de comenzar. Se permite dividir una tarea si no cabe en una entrega revisable; no fusionar módulos por ahorrar pasos.
- Estados iniciales: **01 documentación entregada**; **02–37 pendientes/no iniciadas**. Actualizar estados con evidencia, no por presencia de este plan.
- Antes de cada módulo: explicar cambio/resultado, inspeccionar solo fuentes necesarias y registrar alcance de archivos. Cada entrega mantiene funciones existentes salvo sustituciones de reglas expresamente confirmadas.
- Antes de datos: respaldo verificable, correspondencia, prueba en copia, validación antes/después y estrategia de reversión. Sin fresh, seeders generales, cambios de .gitignore ni instalaciones implícitas.
- No generar registros/fases/fechas ficticios para salvar validación. Datos actuales sintéticos pueden adaptarse de manera acotada; no extrapolar ese permiso a datos reales.
- Cada tarea incluye comprobaciones pertinentes; las pruebas de aceptación no se posponen hasta la tarea 37. No repetir comprobaciones amplias si no hay cambios/fallos que lo justifiquen.

## 2. Niveles recomendados y delegación posterior

| Código | Nivel/modelo orientativo | Uso y revisión |
| --- | --- | --- |
| M | Menor capacidad: GPT-6 Luna, High | Plantillas, etiquetas, estilos o documentación con contrato cerrado. No diseñar permisos/migraciones. Revisión funcional del responsable. |
| I | Intermedio: GPT-6.1 Sol, Medium | Módulo acotado con reglas definidas y reutilización de componentes. Elevar si surge ambigüedad de datos/seguridad. |
| A | Avanzado: GPT-6.1 Sol, High | Autorización, historia, fechas, concurrencia y migraciones. Revisión avanzada antes de aplicar datos. |

Para un problema especialmente complejo puede usarse GPT-6 Astra con razonamiento Medium/High como revisión puntual; no requisito habitual. No usar Max/Ultra por defecto ni cambiar automáticamente el modelo de un usuario. Disponibilidad depende del cliente/cuenta. Referencia consultada: [OpenAI Docs — modelos y esfuerzo](https://learn.chatgpt.com/docs/models); la asignación por tarea es una recomendación para ARTEMIS.

Delegar después de autorizar implementación, con entradas/salidas, archivos asignados y aceptación explícita; evitar escritura simultánea en mismos archivos. Un agente M recibe componentes ya definidos, nunca libertad para reinterpretar negocio. Integración y revisión de tareas A permanecen bajo responsabilidad avanzada. No se inicia ningún agente por registrar esta clasificación.

## 3. Tareas pequeñas por módulos

### Módulo 0 — documentación y preparación

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 01 | Consolidar los tres documentos autorizados. | R01–R18 | P1 | — | I | Reglas E/P/N, entrevista, identidad, dependencias y modelos consistentes; sin otros archivos modificados. |
| 02 | Preparar línea base y contratos de regresión en entorno aislado. | R17/R18 | P1 | 01 | A | Identificar esquema/rutas/pruebas reales, copia y cobertura histórica; confirmar aislamiento SQLite; documentar fallos heredados sin ocultarlos ni usar MySQL real para RefreshDatabase. |

### Módulo 1 — autorización

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 03 | Incorporar rol publicados y capacidades de alta/edición. | R07 | P1 | 02 | A | Admin completo; publicados incorpora/edita metadatos; cgft incorpora sin adquirir edición general; roles se acumulan; none/inactivos bloqueados. |
| 04 | Separar consulta transversal de operación por fase. | R07/R16 | P1 | 03 | A | Funcionales consultan cualquier manual; escrituras directas fuera de fase rechazadas; supervisor solo consulta; notas mantienen contexto R08. |

### Módulo 2 — datos generales y catálogos

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 05 | Incorporar clasificación y validación de altas. | R01 | P1 | 02 | A | Generado/Actualización obligatorio en cualquier alta; publicado anterior intacto al crear actualización; adaptación sintética explícita, sin default silencioso en datos desconocidos. |
| 06 | Agregar cuarta reunión obligatoria de Investigación. | R02 | P1 | 02 | A | Clave nueva estable entre tercera reunión y entrega final; conservar claves/fechas/evidencia anteriores y respaldar adaptación de prueba. |
| 07 | Limitar avances de Experimentación a 2–5. | R02 | P1 | 02 | A | Seeder, validación y configuración aceptan 2/5 y rechazan 1/6; correspondencia de antiguos 6–8 preserva evidencia y no afecta otras fases. |
| 08 | Unificar denominadores, resumen y columnas aplicables. | R02/R05/R06 | P1 | 06,07 | A | Investigación 11+N, Experimentación 6+M; catálogo nuevo máximo 35; No aplica sin peso; coherencia PHP/SQL/matriz/PDF; Edición histórica sin recálculo indiscriminado. |

### Módulo 3 — incorporación y continuación

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 09 | Implementar alta en fase autorizada con antecedentes desconocidos. | R03/R04 | P1 | 04,05,08 | A | Se crea en fase elegida, sin eventos/cumplimientos previos ficticios; servidor valida datos propios y permiso; Planificación sin comités. |
| 10 | Adaptar formulario a la fase de incorporación. | R03/R04 | P1 | 09 | I | Solo grupos pertinentes; búsqueda/selecciones conservadas; validaciones visibles y contexto de retorno intacto; no duplicar formulario. |
| 11 | Cambiar transición para validar solo fase actual completa. | R03/R05/R06 | P1 | 09 | A | Avanza con actuales completas aunque previas desconocidas; rechaza actuales incompletas, fechas inválidas o comités faltantes; conserva confirmación, versión y bloqueo. |
| 12 | Implementar alta/edición autorizada de publicados. | R01/R07 | P1 | 09,10,11 | A | Fecha obligatoria/hoy inicial/editable futura; estado inmediato; clasificación/mapa/tipo válidos; sin recorrido ficticio; permisos servidor probados. |

### Módulo 4 — notas de fase

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 13 | Persistir nota por manual/fase y sus cambios. | R08/R17 | P1 | 04 | A | Nullable/independiente; admin/responsables editan tras transferencia; actor/antes/después; no abre edición de actividades previas ni pierde cambios concurrentes. |
| 14 | Mostrar/editar nota en contexto e historial propio. | R08 | P1 | 13 | I | Solo matriz/historial de fase; no ficha/dashboard/PDF ni JSON de historial general; guardado y errores claros. Un M puede preparar el parcial bajo contrato. |

### Módulo 5 — historial efectivo

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 15 | Registrar permanencias, transferencias y fechas efectivas. | R09/R03 | P1 | 11,12 | A | Distinguir entrada/salida/reingreso, fecha efectiva y digitación; ejemplo CGFT 10/20 de marzo reporta 10; cambio atómico y auditado. |
| 16 | Reconstruir únicamente historia demostrable existente. | R09/R17 | P1 | 15 | A | Mantener eventos originales y Edición versionada; cobertura/faltantes identificados; no rellenar antecedentes de altas avanzadas ni duplicar historia al reintentar proceso. |
| 17 | Añadir sección histórica de transferidos por fase. | R09/R08 | P2 | 14,16 | I | Paginada/plegable, distintas permanencias visibles, fechas/cambios consultables; no altera operación de fase destino; excepción de notas autorizada. |

### Módulo 6 — cuentas y acceso

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 18 | Implementar activación y finalización del reset local. | R14/R07 | P1 | 03 | A | Solo admin habilita; provisional exacta 132456 sin vencimiento; anterior inválida; solo cambio de contraseña antes de módulos; luego provisional inválida; sin correo, logs de claves o cierre de otras sesiones. |
| 19 | Añadir botón administrativo y pantalla obligatoria de nueva contraseña. | R14 | P1 | 18 | I | Petición personal fuera del sistema; login correo/Contraseña, no Cédula; confirmación/validación; no acceso alternativo a módulos ni cambios de roles. M puede maquetar, revisión A del flujo. |
| 20 | Completar alta/edición administrativa individual de cuentas. | R13/R07 | P1 | 03,18 | A | Cuentas separadas del catálogo; no autoconceder roles, validación de datos y último admin activo conservada; registro público none intacto. |

### Módulo 7 — catálogo e importación

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 21 | Medir y optimizar búsqueda/listado de integrantes. | R13/R04 | P1 | 02 | I | Escenario de 30.000 personas, resultados limitados/paginados, selecciones intactas; registrar tiempos/consultas antes-después; revisar join de grado y conteos. No inventar umbral de latencia aún no medido. |
| 22 | Preparar lectura, validación y revisión previa CSV. | R13 | P1 | 21 | I | Cédula texto/ceros; distinguir repetidos internos/existentes, nuevos y errores; previsualizar sin crear cuentas/roles ni actualizar existentes. |
| 23 | Persistir importación y resumen con concurrencia controlada. | R13/R17 | P1 | 22 | A | Agregar solo inexistentes; unicidad incluso con importaciones simultáneas; errores/omitidos/incorporados concordantes; transacción/lotes explícitos y sin corrupción parcial. |

### Módulo 8 — identidad y ficha

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 24 | Identificar imágenes y definir componentes/estilos de referencia. | R15 | P2 | 01 | M | Usar recursos de imagenes, sin sustitución/variantes; paleta exacta, Nunito/Font Awesome; contraste y foco; nombre visible ARTEMIS. |
| 25 | Adaptar una pantalla piloto y navegación compatible. | R15 | P2 | 24 | I | AdminLTE base conservada, sin Bootstrap 4/5 simultáneos, modales/funcionalidad intactos; responsive y compacto. M puede ejecutar ajustes mecánicos tras fijar integración. |
| 26 | Crear ficha individual con enlaces de edición existentes. | R16/R07/R09 | P2 | 04,16,25 | I | General/proceso/participantes/historia consultables transversalmente; sin notas; botón autorizado conduce a formulario/matriz, no crea proceso duplicado. |

### Módulo 9 — dashboard y estadísticas

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 27 | Preparar agregaciones, filtros y listado paginado actual. | R10/R01 | P2 | 08,12 | A | Activos por mapa/fase/clasificación; publicados no duplican CGFT; futuros incluidos; denominadores/filtros concordantes, sin cargar todo el seguimiento. |
| 28 | Construir Situación actual e interacciones de gráficos. | R10/R15 | P2 | 25,27 | I | Franja compacta/tabla/barras útiles; indicador abre listado correcto con filtros; sin notas ni tarjetas repetidas. M puede maquetar componentes con contrato de datos. |
| 29 | Implementar consultas por permanencia/período y productividad. | R09/R10 | P2 | 16,27 | A | Inicio previo incluido si hubo permanencia en período; reingresos cuentan una vez; año en curso por defecto; CGFT por fecha efectiva y publicaciones por año incluso futuro. |
| 30 | Adaptar PDF y listados históricos a criterios compartidos. | R10/R15 | P2 | 29 | I | Mismos filtros/conteos que pantalla, A4 horizontal, notas excluidas, fechas legibles sin JS/iconos; validar render. M puede ajustar presentación, no cálculos. |
| 31 | Registrar referencia institucional por mapa. | R11/R07 | P2 | 03,27 | A | Referencia independiente controlada por admin; alcance activos/publicados/actualizaciones y cambios auditados; sin ajuste automático para forzar igualdad. |
| 32 | Presentar conciliación y diferencias por mapa. | R11/R10 | P2 | 28,31 | I | Comparar mismo alcance, no filtros parciales contra total completo; diferencia explicable sin bloquear operación; acceso a registros de origen. |

### Módulo 10 — duración y cronograma

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 33 | Registrar duraciones globales por fase. | R12/R07 | P3 | 03,09 | A | Meses, tres inicial; solo admin modifica; por fase, no manual; cambios recalculan todas las proyecciones sin alterar fechas reales/operación. |
| 34 | Aplicar plazos configurados a alertas compartidas. | R12/R10 | P3 | 33,27 | A | Superar plazo alerta, igualdad no; Planificación excluida; conservar anual, meses calendario y publicados sin alertas; pantalla/PDF coherentes. |
| 35 | Calcular proyección desde fechas conocidas. | R12 | P3 | 33,34 | A | Sin inicio no dibuja; actuales vencidas alertan y siguientes desde consulta como ejemplo; desplaza futuras y mantiene evidencia del retraso; excluye publicados futuros. |
| 36 | Mostrar cronograma mensual y filtros. | R12/R15 | P3 | 25,35 | I | Real/proyectado diferenciados, filas sin inicio visibles sin barras, etiquetas/leyenda y meses/años; no arrastre/edición o nueva dependencia implícitos. M puede maquetar calendario con datos ya definidos. |

### Módulo 11 — integración

| ID | Tarea | Reglas | Pr. | Dep. | Nivel | Criterio de aceptación |
| --- | --- | --- | --- | --- | --- | --- |
| 37 | Validar integración y actualizar evidencia documental. | R01–R18 | P1 | 10,14,17,19,20,23,26,28,30,32,36 | A | Matriz de roles/URL/concurrencia, conteos, historia, reset, proyección y preservación de datos verificada; sin notas filtradas; migraciones probadas en copia; pruebas fallidas heredadas diferenciadas; estados E/P/N actualizados solo con evidencia. |

## 4. Casos transversales de aceptación

1. **Fase avanzada:** alta en Experimentación con anteriores desconocidas; completar sus actividades/comités permite avanzar, pero actuales incompletas o fechas inválidas no.
2. **Catálogo:** Investigación con N=2 y N=6 usa 13/17 tareas; Experimentación M=2/M=5 usa 8/11; cuarta reunión pesa y limita listo; No aplica no suma. Edición nueva 3, histórica cerrada 6.
3. **Publicación futura:** alta hoy con fecha del próximo año queda publicada hoy, entra en total activo y año declarado, no proyección ni doble conteo CGFT. Corrección de metadatos autorizada y auditada.
4. **Historia temporal:** manual iniciado antes del período y presente dentro de él cuenta; dos reingresos siguen contando una vez por fase/período. Envío CGFT del 10 registrado 20 se atribuye al 10.
5. **Notas:** responsable edita nota de su fase tras transferencia sin modificar tareas de destino; cambio preservado; no aparece en ficha/dashboard/PDF ni JSON del historial general.
6. **Permisos:** none/inactivo no operan; supervisor consulta sin escribir; roles combinados acumulan; cgft alta publicados sin edición general si no tiene publicados; admin mantiene administración completa y último activo protegido.
7. **CSV:** ceros de cédula intactos, duplicado en archivo/base/importación concurrente no crea otra persona; no cuenta/rol nuevo; resumen coincide con filas realmente incorporadas.
8. **Reset:** 132456 no abre cuenta sin activación admin; habilitada solo conduce a cambio, anterior deja de funcionar; tras guardar nueva deja de funcionar provisional; otras sesiones no se cierran y no se envían correos.
9. **Proyección:** cambiar meses afecta todos, no fechas reales; sin inicio hay fila sin calendario; vencida proyecta desde hoy con aviso de ejemplo; Planificación sin semáforo y anual preservada.
10. **Consistencia:** sumar fases no publicadas + publicados activos del mapa coincide con total calculado; contraste contra referencia independiente revela diferencias; filtros parciales no crean falsas alarmas.

## 5. Orden, límites y evidencia

Orden recomendado: 01 → 02 → permisos/catálogos → altas/transición → notas/historia/acceso/personas → identidad/ficha/dashboard → productividad/consistencia → proyección → integración. Se pueden trabajar ramas independientes después de autorizar, pero nunca saltar dependencias o compartir escrituras sin coordinación.

Por entrega registrar: IDs, archivos, regla cumplida, migración/copia/respaldo si aplica, pruebas/resultado, limitaciones reales y estado final. No adjudicar «implementado» a todo un módulo por entregar solo su UI. No prometer todos los reportes históricos completos si la fuente tiene lagunas.

Tareas M delegables posteriormente: 24 y subcomponentes cerrados de 14/19/25/28/30/36. Tareas I pueden delegarse con contrato y revisión; tareas A y decisiones de seguridad/datos requieren razonamiento avanzado. Si una tarea M descubre cambio de schema, permisos, fechas o negocio, detener esa ampliación y elevarla al responsable, no resolverla por conveniencia visual.

Esta entrega no modifica .gitignore ni fuerza AGENTS.MD al índice. No ejecuta los pasos del plan ni autoriza automáticamente una siguiente etapa.

## 6. Ejecución autónoma 24–25/10/2026 — pausa por reserva de cuota

Autorización: texto ARTEMIS adjunto y mensaje del usuario; varias tareas consecutivas y subagentes permitidos. No se aplicaron migraciones a MySQL ni se modificaron datos existentes. Sin commits, cambios de dependencias o .env. Se conserva aproximadamente el 10 % de reserva; no reanudar automáticamente hasta que el usuario vuelva.

### Estado por tarea

- 02: línea base técnica verificada, 59 pruebas/508 aserciones SQLite memory; esquema definido por migraciones. Pendiente copia/restauración y cobertura física de MySQL. No se certifica despliegue real.
- 03–05: rol publicados y capacidades separadas, consulta transversal, bloqueo HTTP de sesiones inactivas, clasificación obligatoria en altas y desconocida nullable en existentes. Implementado y probado aisladamente; migraciones pendientes de aplicación autorizada.
- 06–08: cuarta reunión, Investigación 11+N y Experimentación 2–5, denominadores/matriz y evidencia histórica versionada. Preparado y probado; migración conserva IDs, fechas y archivo, sin aplicación real.
- 09–12: altas por fase autorizada, antecedentes desconocidos, comités remotos en alta, avance solo fase actual y publicados directos/futuros. Probados; navegador confirmó alta Investigación y ficha. Fecha declarada CGFT añadida al botón; este último control tiene pruebas servidor pero falta recorrido visual completo.
- 15–16: permanencias efectivas, reingresos y reconstrucción conservadora con comando en modo revisión por defecto. Preparado/probado, reconstrucción real NO ejecutada.
- 21–23: listado personas paginado, FK grado por id, búsqueda prefijo, CSV revisión e inserción solo nuevos. Pruebas correctas y pantallas inspeccionadas. Pendiente carga CSV completa en navegador y concurrencia física MySQL entre dos conexiones.
- 26: ficha individual con lectura transversal, enlaces autorizados, participantes y fechas/historia sin notas. Probada e inspeccionada en navegador. Dependencias cosméticas 24/25 no implementadas; reutiliza layout vigente.
- 27–30: dashboard agregado/paginado, filtros clasificación/mapa/tipo/fase, publicados sin doble CGFT, productividad por permanencia/fecha efectiva, comparación y PDF. Pruebas e inspección navegador correctas. Historia real depende de migración/reconstrucción autorizadas; no se certifican antecedentes ausentes. Ver documentos de verificación por bloque.
- 13–14, 17–20, 24–25, 31–37: pendientes. No presentar notas, sección de transferidos en matriz, reset local, alta administrativa de cuentas, referencia institucional ni cronograma como implementados.

### Verificación y reanudación

Conjunto de integración observado: 107 pruebas/1021 aserciones correctas; no incluye las pruebas heredadas desconectadas ni certifica suite global. Historia además: 7 pruebas/65 aserciones, conjunto catálogo/workflow/edición/historia 55/641 correcto. Ficha final 10/111 y dashboard/histórico 18/97 correctos. `route:list` muestra39 rutas y `git diff --check` correcto. Sin build por no cambiar fuentes Vite.

Navegador aislado 127.0.0.1:8765 con SQLite propia en storage/app/qa; registros claramente QA. Revisados dashboard móvil/escritorio, filtros/publicado futuro, alta Investigación/selector/guardado, ficha, historial productivo, catálogo/CSV inicial y supervisor Planificación/Edición. Consola sin errores en esas comprobaciones. No equivale a ensayo visual completo de todos los estados. Evidencia específica: DASHBOARD_VERIFICACION.md, PRODUCTIVIDAD_VERIFICACION.md, FICHA_VERIFICACION.md, HISTORIA_VERIFICACION.md, PERSONAS_VERIFICACION.md.

Primero al retomar: revisar diffs y este estado; preparar respaldo verificable y copia MySQL antes de solicitar aplicación de las cuatro nuevas migraciones. No ejecutar `migrate` sobre la base existente sin autorización. Probar adaptación y reconstrucción en copia; después completar verificaciones pendientes y avanzar con notas/matriz o proyecciones según dependencias. Datos desconocidos permanecen desconocidos. Listado exacto de archivos: `git status --short` (cambios sin commit).
PDF QA: actual2 páginas e histórico1, A4 horizontal renderizados e inspeccionados sin cortes/solapes. Artefactos aislados en storage/app/qa. Se corrigió filtro inclusivo published_on mediante whereDate para timestamps SQLite. Fecha histórica puede mostrar hora00:00:00 (presentación pendiente). Servidor QA detenido al pausar.

## 7. Continuación autorizada 25/10/2026 — ejecución económica

El usuario autorizó aplicar migraciones sin respaldo por tratarse de datos de prueba, con preservación obligatoria de usuario y contraseña admin. Se usa un trabajador GPT-6 Luna Medium por bloque y revisión/integración del agente principal. No se crearon agentes con herencia del contexto completo. No se cargan30.000 registros: benchmark anterior ahora es opt-in `RUN_MEMBER_VOLUME_BENCHMARK=1`.

- Las cuatro migraciones de catálogo/permanencias/clasificación/rol publicados ya se aplicaron en MySQL. Se verificaron antes/después identidad, hash de contraseña, estado y roles admin sin imprimir credenciales. Reconstrucción conservadora aplicada a26 manuales desde eventos, sin inventar fechas desconocidas. La cuenta admin se conserva intacta.
- 13/14/17 implementadas: notas nullable hasta1000 caracteres por manual/fase, auditoría separada, versión compartida/409, edición por responsable aun transferido; historial contextual y sección plegable/paginada de salidas/reingresos. Migración000008 aplicada MySQL. 8 pruebas/68aserciones; QA navegador guardar/limpiar y auditoría, transferencia por workflow y consulta de fase anterior correctos. Las notas no se cargan en ficha/dashboard/PDF/eventos generales.
- 33–36 implementadas: duración global1–120meses por fase, inicialmente3, admin activo, auditoría/409, meses calendario, alertas dinámicas coherentes en matriz/dashboard/PDF, cronograma mes/año/horizonte con real/proyectado y ejemplos ante vencimiento; fila sin inicio sinbarras y publicados excluidos. Migración000009 aplicada MySQL. 7pruebas/41aserciones del bloque y DashboardQuery10/54. QA navegador cambió Investigación3→4 en baseQA, recalculó dos manuales sin cambiar inicios y respetó igualdad de plazo. Ver PROYECCION_VERIFICACION.md.
- Catálogo personas actual pequeño:12 iniciales, se agregó una persona claramente QA para concurrencia CSV en dos procesos MySQL independientes; uno insertó1 y otro omitió1, sin duplicado. Usuarios12 y manuales26 conservados. Para UI se creó SQLite QA separada con10 integrantes y2manuales por fase más1publicado; no es la base habitual.
- Comprobaciones HTTP sobre MySQL: dashboard, productividad, matriz Investigación, ficha y nota contextual200. ServidorQA propio en127.0.0.1:8765 durante revisión. Restantes bloques funcionales:31/32 en curso;18–20 pendientes;24/25 no se amplían por ser principalmente visuales;37 pendiente de verificación final acotada. No commits ni cambios de dependencias/.env.

### Cierre funcional de la continuación — 25/10/2026

- 18–20: alta/edición administrativa individual, roles acumulables/none, reset local con provisional132456, pantalla obligatoria antes de módulos, nueva clave confirmada, auditoría sin credenciales, preservación de remember_token y sin cierre masivo de sesiones. Migración000011 aplicada MySQL sin cambiar claves existentes. AccountAccessTest final7/61 y caso heredado de roles1/2 correctos. UI creación y pantalla obligatoria inspeccionadas; acceso provisional y URL directa redirigieron correctamente; el guardado de nueva clave se verificó por pruebas automáticas, no mediante navegador.
- Se detectó una carrera física MySQL al retirar simultáneamente dos roles admin en una base QA separada: ambos cambios inicialmente pasaban. Se corrigió serializando sobre la fila estable del rol admin antes de leer el conjunto. Repetición con dos procesos: uno302 y otro422, exactamente1admin activo conservado. La base habitual nunca participó en esa prueba y su admin pasó verificación de identidad/hash/estado/roles antes y después.
- 31–32: referencias independientes nullable por mapa, cero distinto de sin configurar, auditoría/versión409 y comparación del alcance completo activo (publicados futuros y actualizaciones incluidos). Migración000010 aplicada MySQL. Pruebas4/20 correctas. QA navegador pasó de sin configurar a referencia13 frente a11 registros de prueba y mostró diferencia−2, sin modificar manuales. La referencia de MySQL habitual permanece sin configurar; no se inventó una cifra institucional.
- Integración dirigida:142 pruebas,1284 aserciones,1 omitida deliberadamente (benchmark30.000). Las141 restantes correctas. Después del conjunto se verificaron los ajustes de cuentas y mutex con pruebas dirigidas y concurrencia física, sin repetir globales innecesarios.
- PDF finales: actual e histórico renderizados,1página A4 horizontal cada uno, sin cortes ni solapes. Sentinel de nota excluido de ambos. Ver PDF_VERIFICACION_FINAL.md.
- Los ocho cambios de esquema de esta entrega están aplicados a MySQL: cuatro anteriores y000008–000011. Se reconstruyó solo historia acreditada por eventos. Datos habituales:13 integrantes (incluye1QA de concurrenciaCSV),12 cuentas y26manuales; no se borraron ni recargaron datos. Fixtures de navegador:10 integrantes y2manuales por fase al crear la base aislada, más1publicado; pruebas de transferencia mueven alguno posteriormente.
- Pendiente:24/25 adaptación visual integral (fuera de prioridad funcional); completar ensayo manual CSV de carga/revisión/importación y recorrido visual completo del botón de publicación CGFT con fecha declarada; ambos tienen cobertura automática de servidor. La verificación37 está parcialmente cerrada con evidencia dirigida, no certifica rutas heredadas desconectadas ni todos los escenarios manuales. No quedan bloques funcionales principales13–23/26–36 sin implementación según alcance, sujeto a esas limitaciones.

## 8. Unificación visual autorizada — 25/10/2026

Tareas24/25 completadas para las rutas activas: login/registro/cambio obligatorio, bienvenida, navegación, dashboard, matrices y formularios, notas/historial, cronograma, referencias, cuentas, catálogos AdminLTE y PDF. Paleta institucional centralizada, imágenes auténticas verificadas por hash, fuente local coherente, contraste/foco y vista móvil compacta. Bootstrap5 y AdminLTE/Bootstrap4 permanecen separados. Sin generación/edición de imágenes ni cambios de lógica, autenticación, datos o versiones.

Validación:28 pruebas165aserciones dirigidas auth/registro/cuentas/dashboard y2casos8aserciones de roles correctos; compilación Blade/lint/diffcheck correctos; buildVite correcto sin instalar dependencias. QA navegador escritorio/móvil e interacciones de navegación/tablas, y PDF renderizados sin cortes. Corrección de float en logo AdminLTE confirmada por navegación real. DataTables1.10.19 y fuentes resueltos localmente, sin nuevos servicios/CDN. Detalle y límites: UNIFICACION_VISUAL_VERIFICACION.md y PDF_VERIFICACION_FINAL.md.

Los pendientes visuales24/25 anteriores quedan sustituidos por este cierre; permanecen los recorridos manuales generales CSV completo y publicaciónCGFT detallados en sección7, fuera de esta tarea de identidad. La prueba de30.000 personas continúa opt-in. Preview de base habitual en127.0.0.1:8766/login; no cambia credenciales admin.

## 9. Pendientes registrados a solicitud del usuario

Ver TAREAS_PENDIENTES.md como índice de próximas sesiones. Aclaración de estado: la base funcional del dashboard está implementada y la identidad visual24/25 cerrada, pero la tarea28 permanece PARCIAL respecto de gráficos analíticos. Se registraron G01–G08: donas/pasteles de situación y clasificación, barras por fase/mapa, series históricas, interacción con filtros/listado, secciones del dashboard, accesibilidad/offline y validación/exportación. No están implementados y no se inicia código por documentarlos. Se conservan las comprobaciones manuales CSV y publicaciónCGFT y el cierre integrado37.

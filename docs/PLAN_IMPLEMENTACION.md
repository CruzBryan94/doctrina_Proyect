# ARTEMIS — plan modular de implementación

Versión: 24 de octubre de 2026. Basado en [REQUERIMIENTOS](REQUERIMIENTOS.md) y [AGENTS.MD](../AGENTS.MD). **Solo la tarea 01 está autorizada en esta entrega. Las demás quedan pendientes y bloqueadas hasta autorización expresa de implementación.**

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

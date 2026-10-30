# ARTEMIS — requerimientos confirmados

Versión base: 24 de octubre de 2026. Fuente: diagnóstico selectivo, entrevista y propuesta documental aprobada. La autorización inicial de solo documentación fue sustituida por la autorización de implementación del24/10/2026; el estado integrado vigente se registra en [INTEGRACION_VERIFICACION.md](INTEGRACION_VERIFICACION.md).

## 1. Estados y fuentes

**E: existente**, verificado en código/diagnóstico; **P: parcial**, base reutilizable que requiere cambios; **N: nuevo aprobado pendiente**. La matriz de brechas siguiente es la fotografía de línea base del24/10/2026 y no representa el cierre actual. No certificaba ejecución de pruebas, cobertura de datos ni esquema físico de MySQL; consultar el estado y sus límites en [INTEGRACION_VERIFICACION.md](INTEGRACION_VERIFICACION.md).

[AGENTS.MD](../AGENTS.MD) contiene instrucciones permanentes y [PLAN_IMPLEMENTACION](PLAN_IMPLEMENTACION.md) las tareas. Los IDs R01–R18 enlazan reglas y aceptación. El relato de la entrevista prevalece sobre reglas antiguas; no presentar el objetivo como funcionalidad implementada.

### Arquitectura vigente

Laravel 10.48.25/PHP ^8.1, Eloquent/MySQL, Blade, Vite, Spatie Permission 6.10.1, AdminLTE 3.14.2 y DomPDF 3.0.1. Guard web/sesiones; locale es y America/Guayaquil. Layout ARTEMIS con Bootstrap 5 y módulos históricos de integrantes/unidades con AdminLTE/Bootstrap 4. Alpine/Axios importados; matriz usa JavaScript/fetch propio. Sin SPA. Sanctum/Socialite instalados no implican API doctrinaria ni Google activos.

Fuentes: routes/web.php y RouteServiceProvider; ManualController/ManualRequest; ManualWorkflow; EditionActivity; modelos Manual, ManualPhaseState y ManualPhaseSuphase; ManualPolicy/AuthServiceProvider; DashboardController::data; migraciones y seeders. Las vistas/controladores de perfil, recuperación y verificación heredados no implican rutas conectadas.

### Matriz de brechas

| ID | Área | Estado | Diferencia pendiente |
| --- | --- | --- | --- |
| R01 | Mapas y clasificación | P | Mapas/tipos existentes; falta Generado/Actualización obligatorio y tratamiento de nuevo proceso de actualización. |
| R02 | Catálogos/porcentajes | P | Investigación actual 10+N, N=2–6; Experimentación 6+M, M=2–8. Falta cuarta reunión y M máximo 5. |
| R03 | Incorporación avanzada | N | Hoy alta solo admin/planificador en Planificación y transición exige fases previas completas. |
| R04 | Comités/unidades | E | Cuatro grupos independientes, unidades opcionales, selector remoto; conservar. |
| R05 | Seguimiento/subestados | E/P | Guardado puntual y transición explícita existentes; adaptar a nuevas cantidades/altas. |
| R06 | Edición | E | Tres tareas y seis históricas cerradas; conservar compatibilidad. |
| R07 | Roles/consulta/publicados | P | Roles múltiples existentes; falta consulta transversal de ficha/historial y rol publicados. |
| R08 | Notas de fase | N | Observación general existente no sustituye una nota por manual/fase. |
| R09 | Historia/permanencias | P | Eventos antes/después y estado único por fase; faltan permanencias estructuradas y sección de transferidos. |
| R10 | Dashboard/productividad | P | Totales actuales, filtros y PDF; faltan clasificación, gráficos y consultas temporales completas. |
| R11 | Referencia institucional | N | Falta total esperado independiente por mapa y conciliación. |
| R12 | Proyección/duraciones | P | Alertas fijas tres meses/anual; falta configuración global y cronograma. |
| R13 | Personas/cuentas/CSV | P | CRUD/selector existentes; falta CSV, alta administrativa individual de cuentas y optimización con volumen. |
| R14 | Restablecimiento | P | Login/registro activos; reset local administrativo pendiente; email descartado. |
| R15 | Identidad/UX | P | Componentes/modales disponibles; Command Nexus pendiente. |
| R16 | Ficha/navegación | N | Enlaces actuales llevan a matriz; falta ficha sin duplicar edición. |
| R17 | Integridad/verificación | E/P | Transacciones/versiones/eventos principales; conservar y extender sin asumir uniformidad histórica. |
| R18 | Entrega/documentación | E/N | Documentos aprobados en esta entrega; implementación aún no autorizada. |

## 2. R01 — mapas, tipos y clasificación

Jerarquía mapa → tipo → manual. Guardar tipo y derivar mapa; validar compatibilidad en servidor. Todos comparten fases.

| Mapa | Código | Etiqueta |
| --- | --- | --- |
| MAPA DOCTRINARIO DEL EJÉRCITO | MFE | MANUALES FUNDAMENTALES DEL EJÉRCITO |
| MAPA DOCTRINARIO DEL EJÉRCITO | MFRE | MANUALES FUNDAMENTALES DE REFERENCIA DEL EJÉRCITO |
| MAPA DOCTRINARIO DEL EJÉRCITO | MCE | MANUALES DE CAMPAÑA DEL EJÉRCITO |
| MAPA DOCTRINARIO DEL EJÉRCITO | MTE | MANUALES DE TÉCNICAS DEL EJÉRCITO |
| MAPA DE PUBLICACIONES MILITARES | MEM | EDUCACIÓN MILITAR |
| MAPA DE PUBLICACIONES MILITARES | MME | MANTENIMIENTO |
| MAPA DE PUBLICACIONES MILITARES | MSI | SEGURIDAD INTEGRADA |
| MAPA DE PUBLICACIONES MILITARES | MAF | ADMINISTRATIVO FUNCIONAL |
| MAPA DE PUBLICACIONES MILITARES | RAE | REGLAMENTOS ADMINISTRATIVOS |
| MAPA DE PUBLICACIONES MILITARES | CTE | CATÁLOGOS |

Generado/Actualización es obligatorio en toda incorporación, también histórica/Publicados. No asignar Generado automáticamente a antecedentes desconocidos. Cada actualización será otro proceso/registro: conservar el publicado anterior, no sobrescribirlo/reabrirlo. No equivale a versión de archivo ni se introduce gestión de contenido.

## 3. R02 — fases, actividades y porcentajes

Recorrido: Planificación → Investigación → Experimentación → Edición → CGFT → Publicado. Publicado es estado, no sexta fase; actualmente conserva la fase CGFT. Nota de Aula no pertenece al recorrido. Orden mediante slug, activity_key y position; conservar claves, no IDs aritméticos ni etiquetas como identidad.

| Fase | Existente | Objetivo |
| --- | --- | --- |
| Planificación | ANÁLISIS; FACTIBILIDAD. | Conservar 2 actividades. |
| Investigación | 10 fijas + N, N=2–6; tres reuniones. | 11 fijas + N, N=2–6; cuarta reunión obligatoria para todos. |
| Experimentación | 6 fijas + M, M=2–8. | 6 fijas + M, M=2–5 configurable, no cinco obligatorias. |
| Edición | 3 actividades; 6 solo versión histórica cerrada. | Conservar R06. |
| CGFT | PENDIENTE; LEGALIZADO. | Conservar; alta directa de publicados no simula su cumplimiento. |

**Investigación objetivo, en orden:** CONFORMAR LOS COMITES DE INVESTIGACIÓN; INDUCCIÓN Y LINEAMIENTOS; ENTREGA DEL PERFIL Y CRONOGRAMA; TRES MES DE INVESTIGACIÓN; 1. a N. AVANCE DE LA INVESTIGACIÓN; ENTREGA DE LA CARPETA DE INVESTIGACIÓN; VALIDACIÓN DE LA INVESTIGACIÓN; 1., 2., 3. y 4. REUNIÓN DE VALIDACIÓN; ENTREGA DEL PROYECTO DOCTRINARIO CORREGIDO Y FINALIZADO.

«TRES MES» es actividad con casilla/fecha, no vencimiento automático. Mantener carpeta research.12, research.three-months y reuniones existentes research.validation-meeting.1 a .3; la cuarta requiere clave nueva estable, no renumerar las anteriores. No corregir etiquetas institucionales inadvertidamente.

**Experimentación objetivo, en orden:** DISPONER LA EXPERIMENTACIÓN; INDUCCIÓN Y LINEAMIENTOS AL COMITÉ DE EXPERIMENTACIÓN; ENTREGA DEL CRONOGRAMA DE ACTIVIDADES; PRIMER a QUINTO AVANCE DE LA EXPERIMENTACIÓN según cantidad elegida; INFORME FINAL DE LA EXPERIMENTACIÓN; VALIDACIÓN DE LA EXPERIMENTACIÓN DEL PROYECTO DOCTRINARIO; ENTREGA DEL PROYECTO DOCTRINARIO CON TODAS LAS OBSERVACIONES. El informe conserva experimentation.12 aunque cambie posición al retirar avances.

Cada actividad aplicable pesa igual. Denominador previsto por fase, nunca solo filas existentes/cumplidas; excluir «No aplica» y archivadas. Redondear al final. Actual: catálogo máximo de 37 plantillas, 23+N+M por manual nuevo (27–37). Objetivo: **35 plantillas activas, 24+N+M por manual nuevo (28–35)**; esto no implica completar antecedentes de altas avanzadas. Edición histórica conserva denominador 6.

El usuario permite adaptar todos los manuales actuales por ser de prueba, incluida cuarta reunión y reducción de avances. Preparar correspondencias y conservar trazabilidad; no autoriza fresh, destrucción general ni autocompletar datos reales. Actualizar juntos seeder, seguimiento, PHP, resumen SQL, matriz, transición, PDF y pruebas.

## 4. R03 — incorporación y continuación

- Cada usuario operativo incorpora en sus fases autorizadas; admin en cualquiera. Supervisor no recibe operación por consultar. Publicados según R07.
- Solicitar clasificación y datos propios de fase: comités/unidades en Investigación/Experimentación, datos de Edición o metadatos/publicación. Sin comités en Planificación.
- Antecedentes anteriores pueden quedar sin información; distinguir ausencia de tarea incumplida, sin transferencias ficticias, fechas inventadas ni obligar a recorrer Planificación.
- Para avanzar basta 100 % y requisitos de fase actual, con permiso/versión/confirmación. Quitar bloqueo global por fases anteriores incompletas. Conservar coherencia cronológica donde existan datos reales.
- Alta sin inicio conocido puede completarse después: no permite cumplir actividades sin inicio válido ni genera proyección. No sustituirlo por fecha de alta.
- Nuevos en Planificación mantienen tareas pendientes y 0 %; no cumplir primera automáticamente. Editar nombre/tipo no regenera seguimiento ni reactiva manuales.

## 5. R04 — comités, personas y unidades

| Tipo | Comité | Unidades |
| --- | --- | --- |
| 1 | Investigación | Independientes y opcionales. |
| 2 | Validación en Investigación | Independientes y opcionales. |
| 3 | Experimentación | Independientes y opcionales. |
| 4 | Validación en Experimentación | Independientes y opcionales. |

Cada fase exige al finalizar al menos un integrante en ambos comités; unidades no obligatorias ni asociaciones con FK nula. Persona/unidad sin duplicados dentro del grupo, pero permitida en distintos grupos. Edición de composición en su fase actual; posteriores conservan antecedentes. Notas son excepción de R08.

Sincronización diferencial: omitido conserva, vacío explícito retira. members/units y present_members/present_units distinguen ambos casos; units[2] admite Validación de Investigación. No borrar/reinsertar iguales. Validar existencia y duplicados.

Integrante: grado, nombre, cédula única de texto y celular opcional hasta 20 caracteres, conservando ceros/prefijo. No es cuenta de acceso. Selector existente por cédula/nombre/apellido y unidades por código/nombre, palabras en cualquier orden, bloques de 25/Mostrar más; buscar/paginar no desasigna. Solo guardar persiste.

## 6. R05 — matriz y subestados

- Una fila por manual: nombre, mapa/tipo, actividades, avance, inicio/tiempos y acciones. Solo MANUAL fija horizontalmente; encabezado fijo, filas compactas, scroll y texto legible.
- Tarjetas compactas: total; Desarrollo/Validación en Investigación/Experimentación; listos con nombre del destino. Sin contador de actividades. Resumen sobre todos los resultados filtrados, no solo página. Listos puede solaparse con subestado y no significa transferencia efectuada.
- Casilla cumplida requiere fecha válida no futura ni anterior al inicio aplicable. Marcar sin fecha no guarda ni aumenta porcentaje. «No aplica» sin controles/peso.
- Una actividad por petición; cola por manual, versión y progreso del servidor. No enviar toda la matriz ni interpretar omisiones como desmarcadas. Error reintentable; 409 exige recarga, sin sobrescritura.
- Desmarcar limpia fecha vigente, recalcula y audita, sin motivo/confirmación obligatorios. Proteger navegación interna con cambios pendientes mediante modal; no beforeunload nativo.
- Botón final visible/deshabilitado hasta 100 %, requisitos y guardados completos. Clic confirmado avanza una fase; nunca al completar última casilla. Edición: Enviar a CGFT, sin publicar. CGFT: Publicar manual. Validación equivalente en servidor.
- Retroceso explícito confirmado solo con fase actual toda pendiente, nunca antes de Planificación. Conservar historia/otras fases. Autorizar origen dentro de transacción; no conceder destino automáticamente.
- Conservar despublicación auditada existente al desmarcar seguimiento CGFT publicado; editar metadatos de publicados no debe simular tareas/recorridos.
- Subestados persistidos: Desarrollo desde inicio; Validación al cumplir carpeta research.12 o informe experimentation.12. Sin inicio no hay etiqueta. Fecha del hito = comienzo de Validación; desmarcar vuelve a Desarrollo y limpia esa fecha; cambiar fecha la actualiza. No exigir otras casillas para este cambio ni alterar porcentaje por etiqueta.
- Conservar columnas/etiquetas diferenciadas Desarrollo/Validación y estados cumplido/error/no aplica; grupos derivados del hito estable. No reiniciar reloj anual por cambio de fase.

## 7. R06 — Edición existente que debe conservarse

| Actividad / clave | Datos al cumplir |
| --- | --- |
| INICIAR CORRECCIÓN IDIOMÁTICA / edition.idiomatic-start | Seguimiento propio no vacío y fecha de cumplimiento. |
| CORRECCIÓN IDIOMÁTICA REVISIÓN FINAL / edition.idiomatic-final-review | Seguimiento propio, fecha máxima y cumplimiento. |
| CORRECCIÓN EDITORIAL Y GRÁFICA / MAQUETACIÓN / edition.editorial-layout | Inicio, fecha máxima, responsable usuario del sistema y cumplimiento. |

Textareas dos líneas/máximo 500 caracteres; diez palabras es orientación. Borradores nullable; no invalidar datos requeridos manteniendo casilla cumplida. Responsable buscable por nombre/correo: activo para nueva asignación; preservar histórico inactivo; asignación no concede permisos.

Inicio específico no futuro/anterior a Edición. Plazo de revisión no anterior a inicio de fase; maquetación no anterior a su inicio específico; plazo futuro permitido. Cumplimiento no futuro ni anterior a inicio aplicable. Permitir cumplimiento tardío con «Fuera de plazo» y pendiente «Vencida»; no alterar fechas para ocultar retraso ni añadir bloqueos secuenciales.

Pesos 0/33,33/66,67/100 %. Botón Enviar a CGFT confirmado; no publicar. R03 reemplaza chequeo de fases previas, no requisitos propios. Persistir por seguimiento (follow_up, started_on, due_on, responsible_user_id), no plantilla ni JSON libre.

Versión 2: tres actividades; versión 1 cerrada: seis para manuales ya en CGFT/publicados. Conservar is_legacy y plantillas is_active=false. Retroceder desde CGFT archiva seis y abre tres pendientes. Migraciones existentes 2026_10_23_000003/000004: no inventan responsables/plazos/textos; reversión de datos requiere respaldo, no rollback destructivo. Respaldo previo documentado storage/app/backups/before-edition-20261023-154846.json.

## 8. R07 — roles y publicados

| Rol | Objetivo (además de consulta transversal para funcionales) |
| --- | --- |
| admin | Todo; usuarios/catálogos, plazos y referencias institucionales. |
| none | Bienvenida/cierre, sin módulos ni dashboard. |
| supervisor | Consulta/dashboard/PDF, sin operación. |
| planificador | Incorporar y operar Planificación. |
| investigador | Incorporar y operar Investigación. |
| experimentador | Incorporar y operar Experimentación. |
| edición | Incorporar y operar Edición; conservar tilde técnica. |
| cgft | Incorporar/operar CGFT y alta directa en Publicados. |
| publicados (nuevo) | Incorporar y modificar metadatos de publicados. |

Todos los funcionales consultan manuales, ficha, historial y dashboard completos, con excepción contextual de notas R08. Roles se acumulan; supervisor no resta, none se retira con funcionales; sin funcionales vuelve none. Solo admin gestiona cuentas/roles/catálogos; proteger último admin activo. Inactivo no obtiene capacidades aunque conserve roles.

Publicados requiere nombre, mapa/tipo compatible, clasificación y metadatos estadísticos. Fecha obligatoria, inicialmente hoy, editable pasada/futura: publicado inmediato aunque sea futura, cuenta en total y año declarado y queda fuera de proyección. Alta directa no inventa CGFT ni previas. Publicación por recorrido conserva botón explícito y debe permitir fecha declarada sin confundir con digitación/cumplimiento.

Alta por cgft no concede sola edición general de metadatos publicados; modificación confirmada admin/publicados. En la arquitectura actual dashboard/administración usan permisos y operación roles en accessiblePhaseSlugs/policy: separar lectura/escritura y comprobar servidor, no solo añadir permisos o esconder botones.

## 9. R08 — notas de fase

Una observación/nota nullable por manual/fase, independiente de observación general y seguimiento de Edición. Compartida en ese contexto, no privada por cuenta. Admin/responsables de fase editan incluso transferido; no abre otras actividades anteriores.

Mostrar solo en matriz/historial contextual de esa fase. Excluir dashboard, ficha general, PDF estadístico y detalle de eventos generales que eluda restricción. Conservar autor, fecha y contenido anterior/nuevo, también al limpiar. Límite técnico a definir explícitamente al preparar contrato; no convertirla en obligatoria.

## 10. R09 — historial y transferidos

Complementar eventos/estado único por fase para distinguir cada permanencia, entrada, salida y retroceso; diseño físico aún pendiente. Diferenciar fecha efectiva de fecha de registro: envío real a CGFT 10 de marzo digitado 20 se reporta el 10; conservar actor/registro.

Historia cronológica de incorporación, transferencias, fechas, cambios relevantes y participantes. Distinguir composición actual de evidencia histórica; no prometer participantes antiguos si no se puede acreditar. Reconstruir solo datos demostrables y mostrar faltantes/cobertura. Ausencia en fase previa de alta avanzada no equivale a transferencia acreditada ni tarea incumplida.

Debajo de matriz: sección plegable/paginada de manuales enviados a fase siguiente, con fechas que tenían, cambios y antecedentes de esa permanencia. Consulta sin alterar operación actual; notas editables solo por excepción R08. Conservar múltiples permanencias, pero no duplicar conteo estadístico por reingreso.

## 11. R10 — dashboard, productividad y PDF

- Total activo = cinco fases sin publicados + publicados. Incluir futuros y cada actualización como proceso; excluir inactivos. No duplicar CGFT/publicado.
- Cantidades/porcentajes por mapa/fase y Generado/Actualización; denominador visible/coherente con filtros. Porcentaje de fase no es de proceso completo.
- Histórico cuenta permanencia en cualquier momento del período, aunque inicio sea anterior; una vez por manual/fase/período aunque reingrese. No confundir manuales distintos con transferencias.
- Año calendario en curso por defecto; selección de años/períodos para comparación. Publicaciones por año declarado; envíos CGFT por fecha efectiva aunque estén hoy en otra fase/publicados. Fechas corregidas auditadas; datos vigentes y antecedentes distinguibles.
- No inventar intervalos ante información insuficiente; señalar cobertura histórica.

| Pestaña | Contenido aprobado |
| --- | --- |
| Situación actual | Filtros mapa/tipo/clasificación; total, en proceso, publicados y atención; barras por fase/mapa; listado paginado. |
| Productividad histórica | Permanencias, publicaciones anuales, envíos CGFT y comparación de períodos. |
| Proyección | Cronograma mensual no publicados, real/estimado y retrasos. |
| Consistencia | Referencia institucional por mapa y diferencias explicables. |

Indicador/gráfico abre listado correspondiente con filtros/permisos conservados. No saturar tarjetas/mensajes ni mostrar todo a la vez. Listado incluye nombre, mapa/tipo, fase/subestado, actividad, avance y tiempos total/en fase.

Reutilizar criterios para dashboard/PDF con agregaciones/paginación; no cargar toda la colección de seguimientos para cada indicador. Conservar DomPDF A4 horizontal y fecha/hora Guayaquil; PDF sin dependencia de JS/iconos para expresar estado y sin notas. Igual alcance en pantalla/exportación.

## 12. R11 — referencia institucional

Referencia administrativa independiente por mapa, no calculada desde la misma colección que valida. Comparar activos de todas las fases, publicados futuros/actuales y actualizaciones; no inactivos ni filtros parciales contra referencia completa. Mostrar diferencia/origen, sin ajustar registros o referencia automáticamente para forzar igualdad. Bajo administración, con contrato/auditoría que no bloquee operación por una diferencia.

## 13. R12 — tiempos, proyección y alertas

- Conservar tiempo total/en fase. Hoy se calculan con fechas registradas y se detienen en published_on; fecha futura debe tener presentación/pruebas explícitas sin etiquetar como real transcurrido lo proyectado.
- Duración global por fase en meses, inicialmente tres; no individual por manual. Admin puede fijar Investigación 4 y Edición 2. Cambiar recalcula todos los manuales en proceso, no solo nuevos.
- Proyección informativa, no bloquea editar/avanzar ni altera fechas reales, permisos o historia.
- Al superar plazo configurado mostrar retraso; Planificación única excepción sin semaforización. Conservar alerta de más de un año desde primer inicio conocido de Investigación, sin reiniciar al transferir ni inventar inicio faltante. Publicados sin alertas activas/proyección, incluso futuros.
- Usar meses/años calendario, no 90/365 días; «más de» no incluye igualdad exacta.
- Gantt mensual: fila por manual, columnas meses, color estable y etiqueta por fase; consulta por meses/años, real/proyectado distintos. No imponer biblioteca ni arrastre/edición no solicitada.
- Sin inicio: fila visible, sin fases dibujadas hasta asignar fechas; no usar alta en ARTEMIS como inicio supuesto.
- Fase vencida y abierta: alertar retraso y proyectar siguiente desde día consultado, desplazando posteriores y rotulando como ejemplo. No reescribir inicio/fechas originales para ocultar retrasos. Una fase futura proyectada no equivale a inicio real.

## 14. R13 — personas, cuentas y CSV

Conservar selector remoto y optimizar para unas 30.000 personas: resultados limitados, paginación, espera breve y cancelación de búsquedas obsoletas. Evaluar cédula exacta/prefijo y palabras de nombre/apellido; medir antes de elegir índices/motor o dependencias. Optimizar también listado administrativo hoy completo.

CSV solo integrantes: cédula de texto preservando ceros; detectar existentes y repetidos internos, agregar solo nuevos. No actualizar existentes durante importación ni crear cuentas/roles. Revisión previa y resultado de incorporados/omitidos/errores; validación, transacción/concurrencia y unicidad para impedir duplicados simultáneos. Edición individual administrativa separada.

Mantener CRUD personas/unidades e incorporar alta individual de cuentas por admin sin mezclar con personas ni registro público. Revisar listado de grados unido por code cuando FK usa id y conteos de asociaciones versus manuales distintos antes de reutilizarlos como estadística.

## 15. R14 — acceso y restablecimiento

Conservar login correo/contraseña normalizados, remember, hash, CSRF, limitación de intentos, regeneración de sesión e inactivos bloqueados. Registro público crea activa con none y grado NA si existe, sin elegir roles; espera autorización. Logout invalida sesión actual.

Flujo confirmado que reemplaza la propuesta anterior de correo:

1. Usuario solicita personalmente, fuera de ARTEMIS; no nuevo trámite público de solicitud.
2. Admin abre gestión/perfil del usuario y pulsa Restablecer contraseña.
3. Invalida contraseña anterior y habilita provisional exacta **123456**, sin vencimiento/código adicional. No es 123456.
4. Usuario ingresa correo y provisional en Contraseña; no se agrega Cédula al login.
5. Solo accede a guardar nueva contraseña con validación/confirmación antes de módulos.
6. Al guardar deja de servir la provisional; conservar roles/permisos y no cerrar otras sesiones, decisión expresa del usuario.

No servicio de correo ni enlaces; no conectar por defecto recuperación heredada por email. Hash de credenciales y auditoría de activación/finalización sin contraseñas en eventos/logs. Provisional no funciona para cuentas no habilitadas ni elimina bloqueo de inactivos. Alcance: grupo pequeño/red controlada; clave compartida no es autenticación reforzada ni prueba independiente de identidad.

## 16. R15 — identidad ARTEMIS Command Nexus

Nombre interno; visible ARTEMIS. Mantener AdminLTE instalado, sin actualizar tecnología/versiones implícitamente. Adaptar por pantalla el layout Bootstrap 5 actual; nunca ambas versiones juntas.

| Color | HEX | Aplicación |
| --- | --- | --- |
| Azul estratégico | #152438 | Sidebar/estructura institucional. |
| Azul acero | #29445C | Navegación secundaria/superficies. |
| Rojo doctrina | #B52C39 | Identidad/acción principal. |
| Cian tecnológico | #00A7B5 | Indicadores/navegación activa. |
| Oro táctico | #D8A64B | Hitos/prioridades. |
| Blanco humo | #F3F6F9 | Fondo. |
| Blanco | #FFFFFF | Tarjetas/formularios. |
| Gris de texto | #697B8C | Información secundaria. |
| Gris de bordes | #D9E2EA | Separadores. |

Orientación 60 % neutros, 25 % azules, 15 % acentos; no restricción matemática. Sustituye guía verde como objetivo, CSS aún no actualizado. Contraste comprobado; no texto pequeño cian/oro/gris sin validarlo. Distinguir acción roja de alarma mediante contexto/superficie/texto.

Recursos por nombre en raíz del aplicativo: **imagenes/Logo_pequeño.png** (marca compacta/sidebar/navegación/acceso/favicons adaptados) y **imagenes/Logo de portada.png** (bienvenida/portada institucional). Verificar visualmente antes de integrar; no sustituir por escudo CEDMT ni generar variantes sin autorización. No editar imágenes en esta etapa.

Nunito inicialmente y Arial/DejaVu Sans en PDF; Font Awesome local consistente: clipboard-list, search, flask, edit, stamp, check-circle, users, file-pdf. Estilos centralizados en public/css/artemis.css. Formularios lógicos, foco/etiquetas/aria-live, texto oscuro y estados con texto, responsive sin ocultar datos. Tarjetas bajas, filas compactas y solo MANUAL congelado horizontalmente.

Modales comunes para información/error/validación, sin alert/confirm/prompt nativos ni toasts. Confirmar cambios de fase/publicación y eventual eliminación de manual, no desmarcados. No existe ruta de borrado de manuales y este rediseño no la autoriza. Modal con Escape/Cancelar/retorno de foco, mensajes mediante textContent; guardado persistente por celda, no modal por tecla.

Reutilizar jQuery/DataTables en AdminLTE, Alpine/Axios y JS propio; Select2/Chart.js/SweetAlert2/Pace configurados pero globalmente desactivados, no asumir uso por existir configuración. Evaluar versiones/CDN antes de activar; no dependencias innecesarias. Interacción parcial, gráficos útiles/paneles/nav contextual, animaciones discretas, sin saturación.

## 17. R16 — ficha y navegación

Nombre de manual abre ficha: datos generales/mapa/tipo/clasificación, fase/actividad, comités/unidades/responsables, fechas y recorrido, sin notas exclusivas. Roles funcionales consultan cualquier manual; modificar solo con permiso y validación servidor. Reutilizar formulario actual para datos/comités y matriz para seguimiento; Edición conduce al mecanismo existente, no duplicar formularios. Menú por consulta, operación y administración, sin eliminar funcionalidades por simplificar.

## 18. R17/R18 — integridad, operación y entrega

- Solo documentos autorizados en esta entrega. No ejecutar aplicación, migraciones, tests/build, instalaciones ni configurar infraestructura; no implementación por aprobar documentos.
- Futuros cambios acotados, explicados y autorizados; migración aditiva, respaldo, correspondencias y prueba en copia. No fresh ni seeders generales; autorización sintética histórica no es autorización de destrucción.
- Mantener transacciones, lock_version/409, bloqueos, colas, validación de campos/fechas/IDs y auditoría. Evitar N+1 y cargas completas; reutilizar criterios PHP/SQL/web/PDF. No sobrescribir historial ni reducir avances con cumplimiento/historial salvo migración sintética acotada.
- Conservar nombres técnicos heredados, relaciones únicas y Edición versionada. publication_year es DATE heredada; fecha de publicación actual published_on. No regenerar seguimiento por nombre/tipo/nota. No conceder roles por asignar responsable/importar integrante.
- Instalar después con composer install/check-platform-reqs y npm ci/build, respetando lockfiles y APP_KEY existente; no exponer .env. MySQL local documentado reposbiblioteca en 127.0.0.1:3306; credenciales demostrativas se consultan en seeders, no producción.
- DemoSeeder y algunos seeders base son de carga limpia local/testing, no migración idempotente. ResearchValidationUnitsSeeder añade vínculos sintéticos faltantes sin reemplazar; backfills sintéticos de Investigación no son política para datos reales. Reversión histórica requiere respaldo.
- Grados iniciales códigos 1–17: S.P, Sldo, Cbos, Cbop, Sgos, Sgop, Subs, Subp, Subm, Subt, Tnte, Capt, Mayo, Tcrn, Crnl, Grab, NA. Unidades BC1, GCB4, EFE9. No asumir código=id fuera de esa carga.
- PHPUnit fuerza SQLite en memoria; comprobar antes de RefreshDatabase. Suites: DoctrineWorkflowTest, EditionWorkflowTest, Auth/RegistrationTest, Auth/AuthenticationTest, CommitteeMemberCellphoneTest. Hay pruebas heredadas de rutas desconectadas; no afirmar éxito global por selección dirigida.
- En este equipo pdo_sqlite puede cargarse por proceso con php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit. En implementación, checks pertinentes y git diff --check; build solo si corresponde. No se ejecutan en esta etapa.
- Delegación posterior según PLAN_IMPLEMENTACION; ninguna tarea delegada ni iniciada por documentarla. Código, .gitignore, dependencias/configuración y datos fuera del alcance actual.

## 19. Evidencia y límites del diagnóstico original — 24/10/2026

Fuentes adicionales: database/seeders/ManualPhasesSeeder.php, CatalogSubphasesSeeder.php, ManualTypesSeeder.php, RoleSeeder.php; database/migrations; resources/views/manuals, dashboard.blade.php, report/dashboardPDF.blade.php, layouts.artemis/legacy/app; public/js/matrix.js, assignment-picker.js, edition-responsibles.js, alerts.js; config/adminlte.php; MembersController, MilitaryUnitController y UserController.

La lista anterior refleja límites al elaborar el diagnóstico original, no el estado actual. Desde entonces se aplicaron/inspeccionaron migraciones en MySQL, se ejecutaron pruebas aisladas y dirigidas, y se verificaron pantallas/PDF; ver resultados, cobertura concreta y límites remanentes en `PLAN_IMPLEMENTACION.md`, `TAREAS_PENDIENTES.md` e `INTEGRACION_VERIFICACION.md`. No se certifican restauraciones de respaldo ni rendimiento del PDF en volúmenes masivos.

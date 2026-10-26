# ARTEMIS — pendientes para próximas sesiones

Actualizado: 25 de octubre de 2026. Este registro no inicia implementación. La prioridad siguiente es completar la presentación analítica del dashboard.

## 1. Dashboard: gráficos pendientes

La base funcional existe: agregaciones, filtros, indicadores, listado paginado, enlaces, alertas y PDF. La identidad visual está unificada. Esto no significa que el dashboard gráfico esté terminado: hoy presenta tarjetas y barras simples por mapa; faltan pasteles/donas y gráficos analíticos completos. La tarea28 sigue parcialmente completada en este aspecto.

| ID | Prioridad | Tarea | Aceptación |
| --- | --- | --- | --- |
| G01 | Alta | Dona o pastel de situación actual: en proceso frente a publicados. | Categorías excluyentes; publicados futuros incluidos una sola vez; cantidades, porcentajes y denominador de la selección visibles. |
| G02 | Alta | Dona o pastel por clasificación: Generado, Actualización y Sin información. | Mantener desconocidos explícitos; no asignar clasificación automáticamente ni ocultar su proporción. |
| G03 | Alta | Barras por fase y por mapa. | Cinco fases sin publicados y categoría Publicados independiente; colores estables, etiquetas y valores legibles. Sustituir/complementar las barras simples actuales con una comparación útil. |
| G04 | Media | Gráficos históricos de publicaciones y envíos a CGFT por mes/año. | Usar fecha declarada de publicación y fecha efectiva de transferencia; diferenciar datos futuros y cobertura desconocida; no inventar puntos históricos. |
| G05 | Alta | Interacción entre gráficos, filtros y listado. | Clic en segmento/barra abre el listado correspondiente, conserva filtros y reinicia paginación. Gráfico, indicadores, listado y PDF comparten alcance y totales. |
| G06 | Media | Organizar Situación actual, Productividad, Proyección y Consistencia como secciones/pestañas del dashboard. | Reutilizar páginas y servicios existentes; evitar duplicar indicadores o recargar todos los módulos a la vez. |
| G07 | Alta | Accesibilidad, móvil y funcionamiento sin Internet de los gráficos. | Leyenda y tabla alternativa, navegación por teclado cuando corresponda, datos no expresados solo con color, estados sin datos y filtros sin resultados. Sin CDN obligatorio ni dependencias añadidas sin necesidad. |
| G08 | Alta | Verificar gráficos y exportación. | Casos con cero registros, desconocidos, publicados futuros, filtros combinados y valores reales de prueba; comprobar visualmente escritorio/móvil. Si se incorporan gráficos al PDF, preparar alternativa estática compatible con DomPDF y verificarla antes de afirmar paridad gráfica. |

Los tipos anteriores son una propuesta de presentación para desarrollar en la próxima sesión; no constituyen gráficos ya implementados. Elegir primero los gráficos que respondan preguntas concretas y evitar decoración redundante.

## 2. Recorridos manuales pendientes

- CSV: completar en navegador carga del archivo, revisión, errores/duplicados, confirmación y resumen. Ya existen pruebas automáticas e importación concurrente comprobada en MySQL.
- CGFT: completar en navegador la publicación mediante el botón y una fecha declarada pasada/futura; comprobar ficha, dashboard, histórico y PDF. La validación de servidor ya tiene pruebas.
- Cerrar la tarea37 con una matriz de aceptación consolidada y las limitaciones de cobertura histórica. No confundir pruebas dirigidas correctas con certificación global de todas las rutas heredadas.
- Evaluar rendimiento del PDF con volúmenes mayores únicamente cuando haga falta. El benchmark de30.000 personas permanece desactivado salvo solicitud explícita; para desarrollo usar fixtures pequeñas.

## 3. Estado que debe conservarse

- Notas por fase, transferidos, cronograma, plazos, referencias, cuentas y reset local ya tienen implementación; no volver a registrarlos como funcionalidades nuevas pendientes.
- La unificación visual24/25 está cerrada para las rutas activas, incluido login. Los gráficos G01–G08 son una ampliación analítica pendiente, no una repetición del cambio de paleta.
- Migraciones anteriores aplicadas a MySQL; preservar usuario, hash de contraseña, estado y roles del admin habitual. No ejecutar seeders generales ni recargar datos para desarrollar gráficos.
- Mantener la estrategia económica: una tarea acotada para Luna y revisión/integración del agente principal; pocas fixtures y pruebas pertinentes, sin duplicar exploraciones ni suites.
- Preview de la base habitual: http://127.0.0.1:8766/login. El puerto8765 es QA aislada y debe usar caché separada/en memoria para no compartir IDs de permisos con MySQL.

Fuentes de evidencia: PLAN_IMPLEMENTACION.md y los documentos de verificación por módulo. Este archivo es el índice vigente de trabajo futuro.

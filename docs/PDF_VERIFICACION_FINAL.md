# Verificación final de reportes PDF

Fecha: 25 de octubre de 2026. Alcance: render de reportes usando `storage/app/qa/browser-20261025.sqlite` exclusivamente.

## Método y evidencia

- Se forzó conexión `sqlite`, se fijó la ruta de la base de QA y `url = null`, se ejecutó `DB::purge()` y se verificó que la conexión activa apuntara a ese archivo. Se autenticó el fixture sintético `admin@qa.test` con `Auth::setUser`; no se modificaron usuarios ni contraseñas.
- Se invocaron `DashboardController::report` sin filtros y `HistoricalDashboardController::report` con `metric=phase`, `phase=research`, `year=2026` y Requests construidos.
- `current-final.pdf`: 1 página, A4 horizontal (841.89 × 595.28 pt). `history-final.pdf`: 1 página, A4 horizontal (841.89 × 595.28 pt).
- Ambos PDF se renderizaron a PNG a 120 dpi y se inspeccionaron visualmente en todas sus páginas. No se observaron cortes, solapes ni texto ilegible. Los títulos, tablas, fechas y pies son legibles.
- Extracción de texto con pdfplumber: el sentinel `QA Nota de Planificación` está ausente en ambos reportes. El histórico muestra cobertura `3 de 11`, permanencia en Investigación y fecha de entrada `2026-06-25`; el período visible corresponde a `2026-01-01`–`2026-12-31`.

## Archivos

- `storage/app/qa/current-final.pdf` y `storage/app/qa/history-final.pdf`
- Renderes: `storage/app/qa/current-final-1.png` y `storage/app/qa/history-final-1.png`

## Limitaciones

Verificación acotada a esta base SQLite sintética. No se usó navegador y no se inspeccionaron datos ni conexiones de producción. No se modificaron fuentes, migraciones, rutas ni pruebas.

## Verificación visual de paleta unificada

Fecha: 25 de octubre de 2026, 21:31 (America/Guayaquil). Se regeneraron `storage/app/qa/current-visual.pdf` y `storage/app/qa/history-visual.pdf` con la paleta navy/steel/red. Ambos conservan 1 página A4 horizontal (841.89 × 595.28 pt). Los dos nuevos PNG se inspeccionaron completos; títulos, tablas y fechas son legibles, sin cortes ni solapes observados. La base SQLite, el usuario QA y el sentinel se mantuvieron bajo el mismo alcance de la verificación anterior.

# ARTEMIS — Control de generación de doctrina

Aplicación Laravel 10 para gestionar manuales en cinco fases: **Planificación → Investigación → Experimentación → Edición → CGFT**. CGFT contiene Pendiente y Legalizado; al finalizar se publica el manual.

Incluye matrices por fase, guardado automático de cumplimiento con fecha obligatoria, mapas doctrinarios, avances configurables por manual (2–8 por Investigación/Experimentación), comités propios de cada fase, roles múltiples, historial y reportes PDF.

## Ejecutar en local

Requisitos: PHP 8.1 o posterior compatible con composer.lock, Composer, MySQL, Node y npm.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan serve
```

Configura .env con las credenciales de tu MySQL y una base local. La plantilla utiliza reposbiblioteca. No sobrescribas un .env ya configurado. Abre http://127.0.0.1:8000/login.

Para una base nueva:

```powershell
php artisan migrate --seed
```

**Reconstrucción destructiva de una base local de pruebas**, únicamente si deseas borrar su contenido:

```powershell
php artisan migrate:fresh --seed
```

Esta versión se preparó con una reconstrucción local autorizada. La adecuación de una base antigua con datos reales requiere un plan de migración y correspondencias de fases; no aplicar fresh en ese escenario.

## Accesos de desarrollo

| Correo | Contraseña | Rol |
| --- | --- | --- |
| admin@admin.com | admin | Administrador completo |
| supervisor@artemis.test | Prueba123! | Dashboard y reportes |
| planificador@artemis.test | Prueba123! | Planificación |
| investigador@artemis.test | Prueba123! | Investigación |
| experimentador@artemis.test | Prueba123! | Experimentación |
| edicion@artemis.test | Prueba123! | Edición |
| cgft@artemis.test | Prueba123! | CGFT |
| none@artemis.test | Prueba123! | Solo ingreso, sin módulos |
| planificacion-investigacion@artemis.test | Prueba123! | Planificador + investigador |
| investigacion-experimentacion@artemis.test | Prueba123! | Investigador + experimentador |

El seed en local/testing crea cuatro manuales por fase (20) y cuatro publicados, con avances, tiempos y alertas variados. Agrega doce integrantes sintéticos con celulares, tres unidades, dos mapas y diez tipos. Algunos seeders requieren una base limpia y no son una migración repetible sobre datos existentes.

## Uso de la matriz

1. Elige una fase y filtra por manual, mapa o tipo.
2. Registra la fecha de inicio de la fase si no existe.
3. Marca una actividad e ingresa su fecha: el cambio y porcentaje se guardan sin recargar.
4. Para corregir un cumplimiento, desmarca e indica un motivo.
5. Editar permite datos generales, cantidades y comités de Investigación/Experimentación. No hay comités en Planificación.
6. Al completar una fase, el manual avanza una sola etapa. Se publica al finalizar CGFT.
7. Un retroceso es explícito, exige motivo y solo se permite con todas las actividades de esa fase pendientes.

Las actividades no configuradas aparecen como «No aplica» y no afectan el porcentaje.

Rojo significa **más de tres meses calendario en la fase**. La alerta anual empieza al superar un año desde el inicio registrado de Investigación. Planificación no tiene semaforización. Se muestran tiempo total y tiempo en fase; ambos se detienen al publicar.

## Pruebas

phpunit.xml fuerza SQLite en memoria para evitar modificar MySQL. Habilita pdo_sqlite en el PHP de pruebas. En este equipo:

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit tests/Feature/DoctrineWorkflowTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/CommitteeMemberCellphoneTest.php
npm run build
git diff --check
```

Hay pruebas heredadas de perfil/recuperación/verificación para rutas que no forman parte del flujo activo; la ejecución dirigida anterior verifica el alcance implementado.

La guía de negocio y desarrollo está en AGENTS.MD (actualmente excluido por .gitignore).

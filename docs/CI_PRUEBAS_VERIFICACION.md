# Corrección de pruebas heredadas para CI

Fecha: 27 de octubre de 2026. Autorización: actualizar pruebas al comportamiento vigente y ejecutar la suite completa en SQLite aislado; sin cambiar lógica de negocio ni MySQL. No se hizo commit, push ni despliegue.

## Cambios

- `EmailVerificationTest`: las rutas heredadas de verificación y notificaciones no están expuestas; no verifican la cuenta ni envían notificaciones.
- `PasswordConfirmationTest`: el endpoint heredado no confirma contraseñas ni establece `auth.password_confirmed_at`, con credenciales correctas o incorrectas.
- `PasswordResetTest`: la recuperación pública por email está desconectada; no envía correo, genera tokens ni habilita restablecimiento. Incluso un token válido creado solo en SQLite no permite cambiar credenciales por la ruta heredada.
- `PasswordUpdateTest`: cubre el flujo activo `/password/change`, confirmación coincidente, actualización de hash/flag, auditoría y conservación del remember token. Rechaza invitados, inactivos, cuentas sin reset habilitado y el intento de usar `/password` como alternativa.
- `ProfileTest`: las rutas de autoservicio permanecen desconectadas y no alteran identidad, email, verificación ni cuenta; tampoco permiten borrarla con contraseña correcta o incorrecta. La gestión administrativa vigente continúa cubierta por `AccountAccessTest`.
- `ExampleTest`: el inicio exige login, muestra bienvenida sin permiso dashboard, redirige al dashboard con permiso y no permite saltar el cambio obligatorio de contraseña.

Se conservaron las seis clases dentro de la suite completa y se añadieron siete casos netos. No se añadieron exclusiones, omisiones, `continue-on-error` ni cambios al workflow. No se modificaron aplicación, rutas, configuración, esquema, factories, seeders ni dependencias.

## Ejecución

PHP 8.3.29 / PHPUnit 10.5.38, usando `phpunit.ci.xml` y `scripts/ci/bootstrap.php`, que validó SQLite `:memory:` y directorios nuevos de storage/cachés antes de los tests. Se habilitó pdo_sqlite mediante la DLL local existente, solo para el proceso.

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/bin/phpunit --configuration phpunit.ci.xml --filter 'EmailVerificationTest|PasswordConfirmationTest|PasswordResetTest|PasswordUpdateTest|ProfileTest|ExampleTest'
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/bin/phpunit --configuration phpunit.ci.xml
```

- Selección dirigida: **26 pruebas, 114 aserciones, sin fallos ni errores**, salida 0. Incluye la clase Example de Unit por el filtro de nombre.
- Suite completa: **169 pruebas, 1.419 aserciones, 168 correctas y 1 omitida**, sin fallos ni errores, salida 0; 46,342 segundos y 84 MiB reportados por PHPUnit.
- La única omisión sigue siendo el benchmark opt-in de 30.000 personas, preexistente en `MemberDirectoryImportTest`. No se ejecutó ni se presenta como aprobado.
- Sintaxis PHP de los seis archivos y `git diff --check`: correctos.

El mensaje de log `testing.ERROR: Test isolation failure` durante la suite es una excepción provocada deliberadamente por `ManualPhaseHistoryTest::test_history_failure_rolls_back_manual_transition_and_event_together`, que verifica rollback y espera HTTP 500. Ese caso pasó; no representa un error del resultado PHPUnit ni una conexión a MySQL.

## Límites y publicación

El resultado anterior de 162 pruebas/16 fallos/2 errores queda como antecedente; no describe el estado de esta copia tras la corrección. La validación nueva es local, no una ejecución remota de GitHub Actions. El usuario mostró previamente el job Docker correcto en GitHub, pero no se volvió a construir Docker durante este ajuste de tests.

Los cambios quedan sin confirmar ni publicar. Para que Railway deje de omitir el deployment por ese fallo, aún se necesita un commit/push autorizado y una ejecución remota correcta de CI en el SHA publicado. Mantener Wait for CI y el gate obligatorio. No se consultó ni modificó MySQL; las migraciones y fixtures internas de PHPUnit solo utilizaron SQLite aislado. No se certifican aquí datos importados, variables Railway, conectividad productiva ni disponibilidad final de la aplicación.

# Verificación de cuentas

La migración `2026_10_25_000011_add_account_password_update_and_audit.php` agrega `users.must_change_password` con valor predeterminado `false` y crea `account_access_audits`. No transforma credenciales existentes.

El administrador activo se valida por rol `admin` dentro de la transacción. Las cuentas activas con ese rol se bloquean por ID ascendente antes de cambiar cuentas o sus roles, conservando al menos un administrador activo. Las modificaciones no cambian `remember_token`, roles de otras cuentas ni sesiones ajenas.

En el esquema aislado de concurrencia `artemis_qa_concurrency_20261025`, dos transacciones simultáneas llegaron a quitarse el rol admin y dejaron cero administradores activos; la base de la aplicación real se verificó intacta. Para serializar esa decisión, `lockActiveAdmins()` bloquea primero la fila estable del rol admin como mutex y después lee/bloquea el conjunto de usuarios administradores activos por ID ascendente. La repetición del caso concurrente en MySQL QA queda a cargo de la verificación del integrador.

El reset reemplaza el hash y activa la obligación de cambio. La persona debe elegir una contraseña confirmada que cumpla `Password::defaults()` y no sea `132456`; esa contraseña provisional no se puede reutilizar una vez completado el cambio. La auditoría solo conserva actor, cuenta objetivo, acción y fecha.

Comprobación dirigida prevista en SQLite en memoria:

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit tests/Feature/AccountAccessTest.php
```

La migración no debe probarse contra datos reales en esta verificación.

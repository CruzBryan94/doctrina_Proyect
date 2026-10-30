# Flujo de desarrollo, CI y despliegue de ARTEMIS

Actualización: 30 de octubre de 2026. Flujo: **`devDoctrina → Pull Request → produccion → Railway`**. También se admitirá `dev` como origen si se crea en el futuro; ambas ramas solo ejecutan CI y nunca despliegan.

**Inicialización mínima ejecutada el 27/10 con autorización posterior:** Railway tiene 33 tablas con sus relaciones, catálogos y una sola cuenta administradora existente; no se transfirieron manuales, integrantes ni historia. Se conservaron las credenciales del admin. La aplicación salió de mantenimiento y `/login` responde 200. Ver [RAILWAY_INICIALIZACION_MINIMA.md](docs/RAILWAY_INICIALIZACION_MINIMA.md). La transferencia completa mencionada en la preparación inicial quedó sustituida por este alcance mínimo; no se habilitaron migraciones automáticas.

**Verificación posterior del 27/10:** las pruebas heredadas se actualizaron con autorización, sin modificar aplicación ni MySQL. La suite completa local ahora pasa: **169 pruebas, 1.419 aserciones, sin fallos/errores y 1 benchmark opt-in omitido** (salida 0). Cambios sin commit/push; GitHub Actions debe volver a pasar en el commit que se publique. Ver [CI_PRUEBAS_VERIFICACION.md](docs/CI_PRUEBAS_VERIFICACION.md). Las cifras de 162 pruebas fallidas que siguen en esta guía son el antecedente de la preparación inicial, no el resultado actual.

## Estado y límites actuales

La rama **`produccion` ya existe** localmente y en las referencias de origin; las ramas de desarrollo existentes son `devDoctrina` y `main`. El flujo operativo actual usa `devDoctrina`; no es necesario crear una rama `dev`. El repositorio de origen es `CruzBryan94/doctrina_Proyect`; su raíz contiene `artisan`.

El usuario ya creó servicios Railway. En las capturas MySQL informa `ready for connections` y el servicio web rechaza el arranque porque antes exigía la rama literal `production`. El script y el workflow se ajustaron para exigir `produccion`; **`APP_ENV=production` conserva su valor Laravel**, independiente del nombre Git. En Railway se debe comprobar que la rama vinculada sea `produccion` y que no se haya definido manualmente `RAILWAY_GIT_BRANCH`. No se cambiaron ajustes remotos durante esta corrección.

La preparación inicial tuvo incidencias heredadas: **162 tests, 1.326 aserciones, 16 fallos, 2 errores y 1 omisión**. Se corrigieron las expectativas de email/perfil/confirmación/reset scaffold en la verificación posterior indicada arriba, sin habilitar funcionalidades retiradas ni excluir clases. El benchmark opt-in de 30.000 personas permanece omitido por defecto y no equivale a una prueba fallida. La aprobación remota de CI y los requisitos operativos de liberación continúan siendo necesarios.

La imagen Linux tampoco pudo verificarse: se inició temporalmente Docker Desktop, pero el build falló antes de descargar imágenes por un error DNS del motor al resolver `registry-1.docker.io`. No se cambió la configuración de red del equipo. Los jobs remotos, reglas de GitHub y ajustes Railway están pendientes. Usar esta guía junto con [RAILWAY_DEPLOYMENT_PLAN.md](RAILWAY_DEPLOYMENT_PLAN.md); no activar Autodeploy antes de completar los pendientes.

## 1. Ramas existentes y flujo

Se conservan `devDoctrina`, `main` y `produccion`. Los cambios cotidianos se realizan en `devDoctrina`; `main` no es una rama de publicación en este flujo. Los PRs de `devDoctrina` a `produccion` pasan el mismo gate que un PR desde `dev`. Ninguna rama de desarrollo debe vincularse como fuente de Railway.

1. Revisar `git status` y el diff antes de subir cambios. No mover un árbol sucio entre ramas sin decidir cómo conservarlo.
2. Trabajar en `devDoctrina`, ejecutar pruebas disponibles y subir la rama a GitHub.
3. Abrir PR con base `produccion` y compare `devDoctrina`. Esperar CI, revisión y aprobación; no hay merge automático.
4. La rama `main` no promueve ni despliega cambios en este proceso.

No hay workflow que cree ramas, haga commits, fusione o publique a `produccion`. Railway sigue configurado para que solo un cambio en `produccion` pueda generar un despliegue.

## 2. Protección obligatoria en GitHub

En **Settings → Rules → Rulesets** o **Branches → Branch protection**, crear regla para el nombre exacto `produccion`:

- Requerir Pull Request y al menos una aprobación humana de un revisor distinto del autor. Un autor único no puede aprobar su propio PR: necesita incorporar un revisor autorizado para cumplir esta regla, no eludirla.
- Requerir resolución de conversaciones y descartar aprobaciones obsoletas cuando se agregan cambios; requerir aprobación del último push revisable si está disponible.
- Exigir el check **`ARTEMIS CI required`**, proveniente de GitHub Actions. Seleccionar el nombre realmente mostrado por GitHub tras la primera ejecución; no otro job con nombre parecido.
- Requerir que la rama esté actualizada con `produccion` antes del merge. No activar merge queue con este workflow: si se desea en el futuro, habrá que añadir y validar `merge_group`.
- Aplicar también a administradores; no configurar bypass ordinario de PR/checks. Bloquear force push y borrado. Revisar permisos de escritura y reglas superpuestas.
- Desactivar auto-merge del repositorio y hacer los merges manualmente. Desactivar borrado automático de head branches o proteger también `dev` contra borrado, porque es una rama permanente.

Revisar disponibilidad según visibilidad/plan de GitHub: repositorios privados pueden necesitar un plan compatible para exigir protección. No considerar que una regla documentada ya está aplicada. Si GitHub no permite imponerla, **no activar publicación automática** hasta acordar una solución con protección equivalente. No usar un token con privilegios para saltarla.

El gate de CI exige PR desde `dev` o `devDoctrina` del mismo repositorio; un PR desde `main` o desde un fork no podrá promoverse. Mantener el desarrollo activo en `devDoctrina`.

Fuentes: [protección de ramas](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches), [checks requeridos](https://docs.github.com/en/pull-requests/how-tos/merge-and-close-pull-requests/troubleshooting-required-status-checks).

## 3. Trabajo cotidiano en dev

Con árbol limpio o cambios preservados conscientemente:

```bash
git switch devDoctrina
git pull --ff-only origin devDoctrina
git status
```

Desarrollar y probar localmente. Revisar cada archivo antes de seleccionarlo; ejemplos que el usuario ejecuta, no automatismos de Codex:

```bash
git diff
git add <archivos-revisados>
git diff --cached
git commit -m "Describe el cambio aprobado"
git push origin devDoctrina
```

No incluir `.env`, datos de pruebas, dumps, SQLite, credenciales o backups. No ejecutar push a `produccion` ni usar force push. La política de publicación depende de la protección de GitHub y de la rama vinculada en Railway; el nombre de una rama local por sí solo no impide errores de configuración.

Los push a `devDoctrina` ejecutan GitHub Actions, pero **no deben crear ningún deployment Railway**. Comprobarlo al activar la integración.

## 4. Qué ejecuta GitHub Actions

Archivo: `.github/workflows/ci.yml`. Se dispara en PR con destino `produccion` y en push a `dev`, `produccion` y `devDoctrina`. El gate acepta PRs del mismo repositorio desde `dev` o `devDoctrina`. No hay filtros por archivos ni condiciones que omitan la suite cuando cambia solo documentación. No usar `[skip ci]` o equivalentes en versiones destinadas a producción.

| Job | Qué verifica | Qué no hace |
| --- | --- | --- |
| `PHP, Vite and full test suite` | PHP 8.3 y extensiones, Composer desde lock, requisitos de plataforma, npm ci, Vite, comandos de caché en aislamiento y Unit/Feature completos. | Sin credenciales productivas, MySQL de producción, actualizaciones de dependencias ni publicación. |
| `Docker build and configuration` | Construye imagen, comprueba Bash/FPM/Caddy, ausencia de archivos de desarrollo, arranque `/login` sin servicio DB y conservación de un archivo en volumen desechable al reiniciar. | No sube imagen al registro, no crea Railway ni restaura datos. |
| `ARTEMIS CI required` | Siempre evalúa los dos resultados; exige ambos `success` y PR desde `dev` o `devDoctrina` del mismo repo. | No transforma un fallo, skip o cancelación de un job dependiente en aprobación. |

Se usa runner efímero GitHub alojado en Ubuntu 24.04, no self-hosted con acceso a la red/secretos de producción. El token integrado tiene solo lectura de contenido y checkout no conserva credenciales. No se usa `pull_request_target`, secrets de repositorio/entorno, PAT, token Railway ni claves MySQL reales. No añadir secretos productivos a Actions aunque el repositorio sea privado. Las acciones están fijadas por SHA y deben actualizarse mediante PR revisado.

`composer install` lleva `--no-scripts` para evitar efectos de aplicación durante instalación; no lleva `--no-dev` en el job de tests porque PHPUnit es dependencia de desarrollo. La imagen productiva sí usa `--no-dev --no-scripts`, verifica plataforma y realiza descubrimiento de paquetes al arrancar con el entorno correcto. No se ejecuta `composer update`.

`phpunit.ci.xml` conserva todas las clases de Unit/Feature, incluidos los tests heredados actualmente fallidos. Su bootstrap `scripts/ci/bootstrap.php` crea `storage/framework/testing/ci-<aleatorio>/`, ya ignorado por Git, y redirige storage y cachés de configuración/rutas/paquetes/servicios/eventos a ese directorio. Fuerza SQLite en memoria, URL de DB vacía, sesión/caché en array y correo en array; comprueba la configuración Laravel efectiva **antes de `RefreshDatabase`**. Cada ejecución arranca de cero, sin usar las cachés locales operativas. Esos directorios se pueden conservar para diagnóstico y desaparecen con el runner efímero.

Los tests usan migraciones y seeders propios para construir fixtures **solo en SQLite desechable**. Esto no es una migración de despliegue ni toca tablas existentes en MySQL. No se ejecuta Artisan migrate/seed como paso independiente del workflow. La suite incluye pruebas de comandos de reconstrucción histórica, también confinadas a SQLite.

Para repetir localmente, disponer de pdo_sqlite y ejecutar:

```bash
php scripts/ci/validate-laravel.php
php vendor/bin/phpunit --configuration phpunit.ci.xml
```

En este equipo Windows fue necesario habilitar la extensión en ese proceso:

```powershell
php -d extension=C:\Users\Bryan\scoop\apps\php8.3\current\ext\php_pdo_sqlite.dll vendor/bin/phpunit --configuration phpunit.ci.xml
```

No sustituir la configuración por una conexión MySQL real si falta SQLite: debe fallar. No reutilizar el bootstrap de CI en el arranque productivo. SQLite no certifica bloqueos, collations o carreras MySQL; probar esos aspectos en copia temporal mediante aprobación separada, sin añadir una DB Railway de staging permanente.

## 5. Crear, revisar y fusionar un PR

1. En GitHub → Pull requests → New pull request, seleccionar **base `produccion` / compare `devDoctrina`**. Si en el futuro se utiliza `dev`, también está permitida como rama origen. Revisar el diff completo y el template preparado en `.github/PULL_REQUEST_TEMPLATE.md`.
2. Esperar la ejecución del último commit. En Checks/Actions abrir cada job: una cruz roja exige corregir y volver a hacer push a `dev`. No fusionar con check pendiente, cancelado u omitido. El check obligatorio no se puede sustituir por el éxito de un test dirigido local.
3. Si la suite falla por las rutas scaffold ya identificadas, mantener bloqueado el PR. Mantener/actualizar las expectativas de prueba mediante trabajo revisado; no excluir tests, usar `continue-on-error` ni reconectar rutas retiradas para obtener aprobación artificial.
4. Verificar que no hay cambios de esquema implícitos, secretos, borrados de archivos persistentes ni nuevas tareas residentes. Revisar también cambios en workflow, Dockerfile y scripts, pues podrían quitar controles en un PR futuro.
5. Un revisor autorizado aprueba el contenido y la persona responsable pulsa Merge manualmente. No usar auto-merge. Resolver conflictos en `dev` con revisión y volver a ejecutar CI; no forzar `produccion`.
6. El merge crea un nuevo commit en `produccion`. **Ese push ejecuta CI otra vez**: es indispensable para Wait for CI, aunque el PR ya haya pasado. El artefacto final lo construye Railway a partir de ese commit, no desde tu árbol local.

Un cambio que agregue migraciones no queda autorizado a aplicarlas por tener CI verde. Si la versión exige esquema nuevo, no promoverla hasta completar el procedimiento separado de aprobación/respaldo/compatibilidad de la sección 9.

## 6. Configuración manual de Railway, sin desplegar ahora

`railway.json` controla configuración del contenedor, no crea ni vincula servicios ni configura la rama GitHub. Las opciones siguientes quedan pendientes y deben completarse cuando el usuario autorice activar despliegues:

1. Revisar el estado inicial de `produccion`, CI y protecciones. Si la creación/conexión de un servicio dispara un despliegue, **no realizarla todavía**. Al activarla en otra fase, no seleccionar por defecto `main` o `devDoctrina`.
2. Conectar el repositorio correcto y fijar explícitamente **trigger branch `produccion`**. Root Directory `/` en este repositorio. Elegir Dockerfile y config `/railway.json`.
3. Una vez autorizado el primer despliegue y completados secretos/volúmenes, habilitar **Autodeploy** y **Wait for CI** en Settings del servicio. Autorizar en GitHub los permisos de la app Railway para leer Actions. Confirmar que producción tiene el workflow con evento `push`.
4. Desactivar **PR environments/previews** y no enlazar `dev`, `devDoctrina` o `main` a ningún servicio de publicación. No crear staging permanente ni plantillas que añadan workers, cron o Redis. Inspeccionar todos los servicios/entornos para evitar una segunda integración olvidada.
5. Web: una réplica y una región, volumen montado en `/app/storage`, ruta de salud `/login`. MySQL: servicio independiente y privado en la misma región, imagen/versionado compatible, volumen en su ruta de datos. `railway.json` es solo para web: no aplicarlo al servicio de DB.
6. Configurar variables seguras solo en Railway según el plan: APP_KEY estable, APP_URL HTTPS, APP_ENV production, APP_DEBUG false, conexión MySQL por referencias privadas, sesión/caché file, cola sync, logs stderr y cookie segura. No definir un `DATABASE_URL` contradictorio ni variables de ubicación de cachés de CI. Nunca generar una nueva APP_KEY al reiniciar.
7. Dejar Start Command sin override para usar ENTRYPOINT de la imagen. `preDeployCommand` está vacío en el archivo; revisar/eliminar cualquier comando previo de migración en panel, scripts externos o integraciones. Confirmar la configuración efectiva de cada deployment, no solo el formulario.
8. Aplicar límites iniciales de RAM/CPU con margen según el plan y después de ensayos; no confundirlos con reserva/costo fijo. No cambiar MySQL de versión ni reducir su RAM sin pruebas y respaldo.

La rama, Autodeploy, Wait for CI, PR previews, variables, volumen, región y protecciones **no están activados por los archivos preparados**. Son intervención manual obligatoria. El JSON exige montaje `/app/storage`, pre-deploy vacío, réplica única, healthcheck y drenaje de 150 segundos; verificar que Railway refleja esos valores.

Como defensa adicional ante una vinculación equivocada, el arranque rechaza cualquier contexto Railway cuya `RAILWAY_GIT_BRANCH` no sea `produccion`, también si no llega esa metadata. Esa variable debe provenir del trigger GitHub; no definirla manualmente para eludir el control. Una publicación por CLI o imagen sin metadata GitHub queda bloqueada deliberadamente. No sustituye protecciones/Wait for CI ni impide que un administrador cambie el propio mecanismo. Referencia: [variables provistas por Railway](https://docs.railway.com/variables/reference).

### Alcance de Wait for CI

Railway requiere un workflow con evento push en la rama vinculada. Espera la conclusión de los workflows de GitHub Actions del commit; un fallo bloquea. Los workflows omitidos/neutrales no bloquean, y hay matices en cancelaciones cuando otro workflow tuvo éxito. Por ello el flujo no cancela ejecuciones automáticamente, no aplica filtros de paths y mantiene la protección GitHub como barrera previa. Revisar cualquier workflow adicional antes de añadirlo; no saltar CI ni considerar Wait for CI un control absoluto contra acciones manuales de un administrador. Fuente: [Autodeploy y Wait for CI](https://docs.railway.com/deployments/github-autodeploys).

Al activar la integración, validar con evidencia: push a `dev` produce solo CI; PR no produce preview; merge aprobado en `produccion` queda WAITING hasta terminar CI de ese SHA; un fallo de CI no publica; únicamente el SHA aprobado llega al deployment. Esta prueba externa no se ejecutó en la preparación.

## 7. Qué sucede al arrancar la imagen

La arquitectura sigue el plan existente: **Caddy + PHP 8.3 FPM `ondemand`**, dos hijos, timeout ocioso de 10 segundos y reciclaje de 300 solicitudes. Una base MySQL independiente conserva datos. Node 22 y Composer viven solo en etapas de construcción; no hay proceso Node en producción. Laravel permanece en 10.48.25 según lockfile y las vistas siguen siendo Blade.

La imagen tiene bases fijadas por digest de manifiesto oficial. Esto reduce deriva de imágenes base; los paquetes del repositorio Debian aún pueden cambiar entre builds. Las actualizaciones de bases, acciones o paquetes deben revisarse y volver a probarse; no se configuraron auto-updates externos de imágenes.

`docker/start.sh` valida entorno productivo, APP_KEY existente, HTTPS y variables DB obligatorias; no imprime sus valores. Crea directorios al montar el volumen, ajusta permisos de esos directorios y ejecuta `package:discover`, `config:cache`, `route:cache`, `view:cache` y `permission:cache-reset`. No borra archivos/sesiones/caché general ni genera claves. La caché de permisos se invalida de forma acotada y se recalcula normalmente. Una copia inicial de archivos debe quedar con el UID/GID esperado; no se recorre ni se cambia masivamente el contenido del volumen al iniciar.

Tini recoge procesos; el script supervisa Caddy y FPM y detiene el contenedor con error si termina cualquiera. Reenvía TERM a Caddy y QUIT a FPM para cierre ordenado. El drenaje de Railway se propone en 150 s y se debe ensayar con operaciones reales en curso.

Caddy sirve únicamente `/app/public`; sus variables FastCGI fijan HTTPS porque se presupone tráfico externo exclusivamente por Railway Edge/TLS. Antes del primer acceso real verificar encabezados, cookies, URLs, IP y throttling. No se cambió `TrustProxies.php`; **la IP real del cliente sigue siendo una comprobación pendiente** que puede requerir un ajuste autorizado. No usar esta imagen como publicación HTTP directa sin revisar esa suposición.

Sesiones, archivos y caché de archivos se conservan en `/app/storage`; fuentes y vistas compiladas son regenerables. Los logs de error se envían a stderr y Railway los conserva según retención del plan; no son archivo histórico ilimitado. La auditoría de negocio se conserva en MySQL. Volúmenes no son backups y pueden provocar una breve interrupción al redeploy; no se promete alta disponibilidad con una réplica.

## 8. Consultar errores y recuperar una versión

En GitHub → Actions → ARTEMIS CI → ejecución del SHA, abrir el paso que falló. Errores de Composer/plataforma requieren revisar extensiones/lockfile, los de Vite el build, y los de PHPUnit las aserciones. No desactivar el check para aprobar. Los errores de pruebas actuales no implican que MySQL haya recibido escrituras.

En Railway → servicio web → Deployments, comprobar rama y SHA, estado WAITING/build/deploy/healthcheck, Build Logs y Deploy Logs. Luego abrir Logs y Metrics para errores PHP, 502/504, OOM, RAM/CPU y reinicios. Revisar también logs/métricas del servicio MySQL; ocultar claves, datos personales y SQL sensible al compartir evidencias.

Rollback, siempre operado por una persona autorizada:

1. Identificar el último deployment exitoso de **un commit aprobado de `produccion`** y confirmar compatibilidad con la DB actual. No desplegar desde `dev` ni `devDoctrina` para corregir rápido.
2. Si hay incidentes de datos, detener escrituras y preservar evidencia antes de restaurar. Si solo falla aplicación/configuración, seleccionar Rollback del deployment previo en Railway cuando esté disponible, y revisar las variables actuales: rollback de imagen no garantiza revertir cambios de variables.
3. Confirmar salud, login, sesión, lecturas y un flujo autorizado. MySQL/volumen no retroceden al cambiar imagen. No ejecutar `migrate:rollback`, `fresh`, seeders ni restaurar backups sobre escrituras recientes sin un plan separado.
4. Para que la recuperación quede en Git, preparar revert de los commits defectuosos en `dev`, revisar el diff y abrir nuevo PR a `produccion`; corregir antes de promover cualquier otro cambio pendiente de `dev`. El revert debe retirar también los commits defectuosos de la rama de desarrollo, no solo revertir un merge exclusivo de `produccion`. CI y revisión normales siguen siendo obligatorios.
5. Si Autodeploy está pausado durante la incidencia, verificar el SHA de recuperación y reactivarlo conscientemente. Evitar que un merge pendiente vuelva a publicar la versión defectuosa.

## 9. Seguridad de MySQL y cambios de esquema

Nada preparado ejecuta migraciones o seeders de producción. Se detectaron migraciones históricas con inserciones, actualizaciones y borrados de catálogos; `manuals:reconstruct-history --apply` también escribe. Esos archivos/comandos no deben ejecutarse por conveniencia durante una publicación. Composer tiene scripts de creación de proyecto/key generation y publicación de assets; no invocarlos como mecanismo de release.

MySQL debe usar red privada, credencial de aplicación con permisos mínimos para las consultas/escrituras existentes y una credencial administrativa separada para mantenimiento. No conceder DDL al usuario de aplicación como sustituto de un procedimiento de cambios. No publicar el puerto DB ni compartir su password con GitHub Actions.

La carga inicial es una **transferencia autorizada de la base existente**, con su esquema exacto, no una reconstrucción mediante migraciones o seeders. Antes: confirmar versión, charset/collation, modo estricto, tamaño y espacio; respaldar y ensayar restauración. Durante corte: controlar escrituras y comparar conteos/integridad. La presente preparación no autoriza copiar datos a la nube.

Para futuros cambios de esquema: revisión separada de SQL/migración y compatibilidad de versiones anterior/nueva; aprobación explícita, backup consistente y restauración ensayada, ejecución manual controlada y verificación. Documentar quién, cuándo y qué cambió, más recuperación de datos si hubo escrituras. No agregar `artisan migrate --force` al workflow, ENTRYPOINT o pre-deploy.

## 10. Control de costos

Mantener solo un web y un MySQL, una región y una réplica por servicio. Sin staging Railway permanente, workers, cron, Redis o monitores residentes innecesarios. Las pruebas y builds ocurren en runners efímeros; los minutos de Actions tienen su propia cuota/costo GitHub, independiente de Railway.

El plan estima provisionalmente USD 6–12/mes; no se midió la imagen ni se garantiza ese rango. Hobby tiene mínimo USD 5 con uso incluido, no USD 5 más todo el uso. Registrar por servicio RAM media/pico, CPU, disco, egreso y backups en Usage; medir las primeras 24–72 h y revisar una semana representativa. Alerta orientativa USD 7 y límite duro USD 12 solo si el usuario acepta que alcanzarlo detendrá cargas. Revisar el ámbito del límite si hay otros proyectos.

MySQL será probablemente el principal costo ocioso. FPM ondemand elimina hijos sin tráfico, pero no apaga Caddy/maestro; Serverless web es una opción posterior con arranque en frío y verificación real, no activada aquí. No suponer que DB dormirá ni programar pings keep-alive que anulen el ahorro. No bajar memoria a costa de romper PDF/CSV o generar OOM. Consultar [precios](https://docs.railway.com/pricing/plans) y [controles de costo](https://docs.railway.com/pricing/cost-control) antes de activar.

## 11. Verificaciones realizadas y lista previa a activación

| Verificación | Resultado local |
| --- | --- |
| Ramas/remoto | Se conservan `devDoctrina`, `main` y `produccion`; no se crearon ni eliminaron ramas. |
| PHP y plataforma | PHP 8.3.29, `composer check-platform-reqs` correcto sobre vendor instalado. No se reinstaló ni actualizó vendor local. |
| Aislamiento | Bootstrap confirma SQLite en memoria y storage/cachés únicos antes de pruebas; MySQL no consultado. |
| Cachés de arranque | Descubrimiento, config/rutas/vistas y caché de permisos correctos en aislamiento. |
| Suite completa | 162 pruebas; 16 fallos, 2 errores, 1 omisión; salida 1, bloqueante. No se anuncia éxito global. |
| Frontend | `npm ci` + `npm run build` correctos en copia temporal; Node local 24.12.0/Vite 5.4.11. Advertencias previas Sass/Browserslist; Node 22 de CI pendiente de ejecutar. |
| Sintaxis | PHP de scripts, Bash del init, YAML workflow, JSON Railway contra esquema oficial y XML contra XSD PHPUnit correctos. Actionlint 1.7.12 pasó sin incidencias; ShellCheck no estaba disponible. |
| Imagen Docker | Build intentado con Docker Desktop 29.8.2: bloqueado por DNS de `registry-1.docker.io` antes de descargar imágenes. No se alcanzó validación real FPM/Caddy/arranque/persistencia; está preparada en CI. |
| GitHub/Railway | No se cambiaron opciones, no se ejecutó CI remoto ni se crearon servicios/deployments. |

Antes de activar, completar manualmente:

- [ ] Revisar/publicar archivos con commit humano autorizado y conservar cambios previos.
- [x] Confirmar que `produccion` existe; conservarla sin recrearla.
- [x] Conservar ramas existentes y habilitar `devDoctrina` como origen válido de PR hacia `produccion`.
- [ ] Mantener los tests heredados mediante trabajo revisado y obtener suite completa correcta.
- [ ] Ejecutar los dos jobs en GitHub y verificar imagen Linux y Node 22.
- [ ] Aplicar reglas de PR/aprobación/check obligatorio sin bypass y confirmar su disponibilidad en el plan GitHub.
- [ ] Autorizar y configurar Railway solo desde `produccion`, Autodeploy + Wait for CI y previews desactivados.
- [ ] Preparar secretos, volúmenes, backup/restauración e importación inicial autorizada; sin migraciones automáticas.
- [ ] Verificar proxy/IP, acceso restringido o público, PDF/CSV, memoria, drenaje, sesión/redeploy y costos.
- [ ] Probar controles de rama/CI/Wait for CI y registrar SHA desplegado cuando se autorice el primer release.

Mientras existan checks fallidos o verificaciones críticas pendientes, conservar bloqueada la liberación. Configurar GitHub/Railway por sí solo no convierte esta preparación parcialmente verificada en una versión aprobada.

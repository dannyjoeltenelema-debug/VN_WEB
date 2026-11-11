# Portal web — Unidad Educativa Particular Técnica Cristiana Vida Nueva

Proyecto Laravel con MySQL. Página **informativa** con solo tres funcionalidades dinámicas.
El análisis y diseño se hacen fuera de este repositorio; aquí se implementa lo ya decidido.
Este archivo se importa desde `CLAUDE.md` y es la referencia principal del proyecto.
**No agregues tablas, módulos ni funcionalidades que no estén en este archivo.** Si algo
no está definido, pregunta antes de inventarlo.

## Tecnologías

- **Backend:** Laravel 13 (PHP 8.3+). Vistas con **Blade**.
- **Base de datos:** MySQL, base `vn_web` (configurada en `.env`).
- **Entorno local:** Laragon. **Control de versiones:** Git + GitHub.
- **Frontend:** Bootstrap 5 por **CDN** (no usar Vite, npm ni Tailwind salvo que se pida).
  Iconos: Bootstrap Icons por CDN, con moderación. Interfaz limpia y adaptable a móvil.
- **Interactividad:** JavaScript vanilla con `fetch` (jQuery permitido si simplifica algo).
  El agendamiento de citas consulta los turnos disponibles por AJAX sin recargar la página;
  los endpoints devuelven JSON y validan con Form Requests.
- No agregar paquetes de Composer ni librerías nuevas sin consultarlo antes.

## Alcance

**Contenido estático (Blade, sin base de datos):** inicio, proceso de admisión, oferta académica,
sucursales (Quito y Cojimíes), contacto.

**Funcionalidades dinámicas:**
1. **Citas con departamentos** — padres de familia y público agendan sin iniciar sesión.
2. **Trabaja con nosotros** — RR.HH. publica ofertas; el postulante llena un formulario y sube su hoja de vida (PDF) sin iniciar sesión.
3. **Anuncios** — imagen PNG con fecha de inicio y fin; carrusel en el inicio + apartado "Anuncios".

## Base de datos

Diseño aprobado: `docs/database/bd_vida_nueva_mysql.sql` (fuente de verdad).
Las migraciones de Laravel deben reproducirlo fielmente (tablas, tipos, índices, CHECK, triggers, vistas).

12 tablas: `sucursal`, `rol`, `usuario`, `departamento`, `horario_atencion`, `bloqueo_agenda`,
`cita`, `anuncio`, `oferta_laboral`, `requisito_oferta`, `postulacion`, `evaluacion_postulacion`.

### Adaptaciones a Laravel (decididas)
- Nombres de tablas y columnas en **español y singular**, como en el script. Cada modelo declara `protected $table`.
- Timestamps: `const CREATED_AT = 'creado_en'; const UPDATED_AT = 'actualizado_en';` (o `null` si la tabla no tiene esa columna).
- La tabla `usuario` reemplaza a la `users` por defecto de Laravel. Para que funcione la autenticación:
  la columna de contraseña se llama `password` (no `password_hash`) y se agrega `remember_token`.
  Configurar el provider en `config/auth.php` con el modelo `App\Models\Usuario`.
- Conservar las migraciones de Laravel de `sessions`, `cache` y `jobs`. Eliminar la de `users` y `password_reset_tokens` solo si se reemplazan correctamente.
- Triggers y vistas se crean en migraciones con `DB::unprepared()` / `DB::statement()`, con su `down()` correspondiente.
- Campos ENUM → enums de PHP respaldados (`app/Enums`) y casts en los modelos.
- Datos iniciales (sucursales, roles, horario general, departamento de ejemplo) en seeders.

## Reglas de negocio

**Roles** (un rol por usuario; los permisos salen de los interruptores de la tabla `rol`, nunca de `if` por nombre de rol):

| Rol | Anuncios | Citas | Empleos |
|---|---|---|---|
| SUPER_ADMIN, RECTORADO (`acceso_total`) | ✓ | todas | ✓ |
| INSPECCION | ✓ | solo su departamento | — |
| RRHH | — | — | ✓ |
| DEPARTAMENTO | — | solo su departamento | — |

Un usuario cuyo rol gestiona citas sin `acceso_total` debe tener `departamento_id`.

**Citas**
- Cada departamento pertenece a una sucursal. Hoy solo Quito atiende citas (`sucursal.atiende_citas`).
- Horario general: lunes a viernes 14:00–15:20. `horario_atencion` con `departamento_id` NULL = general; si un departamento tiene filas propias vigentes, reemplazan al general. `dia_semana` 1=lunes … 7=domingo.
- Turnos de `duracion_cita_min` (15) dentro del horario; solo turnos completos (14:00, 14:15, 14:30, 14:45, 15:00).
- `cupo_por_turno` (2) personas por turno, configurable.
- Reserva hasta `dias_max_anticipacion` (14) días; el mismo día solo turnos que aún no empiezan.
- No mostrar días con `bloqueo_agenda`: sucursal y departamento NULL = feriado nacional; solo sucursal = feriado/cierre local; departamento = cierre de ese departamento.
- Una persona (cédula/pasaporte) solo puede tener una cita PENDIENTE futura por departamento, y nunca dos citas a la misma hora. Estas reglas y el cupo ya están en el trigger de la base; la aplicación debe además validarlas antes y mostrar mensajes claros.
- La persona no puede cancelar. Estados: PENDIENTE, ATENDIDA, NO_ASISTIO, CANCELADA (esta última solo la institución).
- Código visible `CIT-AAAA-NNNNNN`. Al reservar, enviar correo de confirmación.

**Trabaja con nosotros**
- Oferta: estructura tipo Encuentra Empleo (instrucción, área, experiencia, jornada, sueldo opcional → "A convenir", vacantes, sucursal; NULL = todas). Estados BORRADOR / PUBLICADA / CERRADA.
- Requisitos adicionales en `requisito_oferta` (importancia 1–3, obligatorio).
- Postulación: solo PDF, máximo 5 MB, guardado en disco **privado** (`storage/app/private`), nunca accesible por URL pública; la descarga pasa por un controlador con autorización. Una postulación por persona y oferta. Consentimiento de datos obligatorio (LOPDP).
- Puntuación 0–10 en `evaluacion_postulacion`; cuenta la más reciente. Hoy: método REGLAS (comparar instrucción, experiencia y requisitos) o MANUAL. **Futuro: IA** — diseñar la evaluación detrás de una interfaz (`EvaluadorPostulacion`) para poder cambiar la implementación sin tocar el resto. La de IA se ejecutará en cola (`jobs`).
- Lista de RR.HH.: primero las de `puntaje >= oferta.puntaje_minimo` (7.0), ordenadas de mayor a menor; las de menor puntaje en un filtro aparte, nunca se borran.

**Anuncios**
- Solo PNG. Visible si `NOW()` está entre `fecha_inicio` y `fecha_fin`. Carrusel = `mostrar_en_carrusel = 1`, ordenado por `orden`.
- Tarea programada diaria (Laravel Scheduler): elimina el registro y el archivo de los anuncios vencidos.

**Feriados**: tarea programada que carga los feriados nacionales de Ecuador desde una API con `origen = 'API'`; el administrador puede agregar o corregir manualmente (`origen = 'MANUAL'`).

## Hosting
Hosting compartido con cPanel: MySQL 8.0.16+ o MariaDB 10.4+. Cron de cPanel ejecutando `php artisan schedule:run` cada minuto. Zona horaria `America/Guayaquil`.

## Forma de trabajo
- Trabajar **una fase a la vez**, la que indique el prompt. No adelantar fases.
- Cada fase en su propia rama de Git (`fase-N-nombre`). No hacer commit ni push sin que yo lo pida.
- Nunca subir `.env`, credenciales ni archivos de `storage/` con datos de postulantes.
- Antes de cambios grandes, presentar el plan y esperar aprobación.
- Al terminar una fase: ejecutar las migraciones/pruebas correspondientes, resumir qué se hizo y qué falta.
- Código, comentarios y textos de interfaz en español.

## Fases
1. Migraciones, seeders y enums a partir del script SQL.
2. Modelos Eloquent con relaciones y casts.
3. Autenticación del panel y autorización por rol.
4. Módulo de anuncios.
5. Trabaja con nosotros (ofertas, postulaciones, puntuación por reglas).
6. Citas (cálculo de turnos disponibles, reserva pública, panel por departamento).
7. Tareas programadas (anuncios vencidos, feriados).
8. Páginas informativas.
9. (Futuro) Puntuación con IA.

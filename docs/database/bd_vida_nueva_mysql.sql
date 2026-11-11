-- =====================================================================
--  BASE DE DATOS: Portal web
--  UNIDAD EDUCATIVA PARTICULAR TÉCNICA CRISTIANA VIDA NUEVA
--  Motor: MySQL 8.0.16+  /  MariaDB 10.4+   (InnoDB, utf8mb4)
--  Fase: DISEÑO - Modelo físico (12 tablas)
-- ---------------------------------------------------------------------
--  Funcionalidades:
--   1. Citas con departamentos (público, sin login)
--   2. Trabaja con nosotros: ofertas laborales y postulaciones con CV
--   3. Anuncios en imagen PNG con tiempo de vigencia
--  Contenido informativo (admisión, oferta académica, sucursales)
--  va directamente en las páginas HTML, no en la base de datos.
-- =====================================================================

-- La base de datos se llama vn_web (creada en Laragon).
-- En Laravel este script es solo REFERENCIA: la base se crea con migraciones.
-- Para cargarlo a mano:  mysql -u root vn_web < bd_vida_nueva_mysql.sql

SET NAMES utf8mb4;
SET time_zone = '-05:00';   -- Ecuador continental

-- =====================================================================
-- 1. SUCURSAL
--    Catálogo usado por departamento, oferta_laboral y bloqueo_agenda.
-- =====================================================================
CREATE TABLE sucursal (
    id              TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ciudad          VARCHAR(60)  NOT NULL,
    atiende_citas   BOOLEAN      NOT NULL DEFAULT 0,
    activo          BOOLEAN      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sucursal_ciudad (ciudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. ROL
--    Un usuario = un rol. Los interruptores definen qué secciones
--    del panel puede usar. acceso_total = ve y gestiona todo.
-- =====================================================================
CREATE TABLE rol (
    id                  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo              VARCHAR(30)  NOT NULL,
    nombre              VARCHAR(60)  NOT NULL,
    acceso_total        BOOLEAN      NOT NULL DEFAULT 0,
    gestiona_anuncios   BOOLEAN      NOT NULL DEFAULT 0,
    gestiona_citas      BOOLEAN      NOT NULL DEFAULT 0,
    gestiona_empleos    BOOLEAN      NOT NULL DEFAULT 0,
    activo              BOOLEAN      NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rol_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. DEPARTAMENTO
--    Cada departamento pertenece a una sucursal
--    ("Inspección - Quito" e "Inspección - Cojimíes" son dos filas).
-- =====================================================================
CREATE TABLE departamento (
    id                      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id             TINYINT UNSIGNED  NOT NULL,
    nombre                  VARCHAR(100) NOT NULL,
    descripcion             VARCHAR(255) NULL,
    telefono                VARCHAR(20)  NOT NULL,
    extension               VARCHAR(10)  NULL,
    email                   VARCHAR(150) NOT NULL,
    duracion_cita_min       TINYINT UNSIGNED NOT NULL DEFAULT 15,
    cupo_por_turno          TINYINT UNSIGNED NOT NULL DEFAULT 2,
    dias_max_anticipacion   TINYINT UNSIGNED NOT NULL DEFAULT 14,
    activo                  BOOLEAN      NOT NULL DEFAULT 1,
    creado_en               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departamento_sucursal_nombre (sucursal_id, nombre),
    CONSTRAINT fk_departamento_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursal (id),
    CONSTRAINT ck_departamento_duracion CHECK (duracion_cita_min BETWEEN 5 AND 120),
    CONSTRAINT ck_departamento_cupo     CHECK (cupo_por_turno BETWEEN 1 AND 10),
    CONSTRAINT ck_departamento_dias     CHECK (dias_max_anticipacion BETWEEN 0 AND 90)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. USUARIO (operadores del panel)
--    departamento_id: obligatorio si el rol gestiona citas sin acceso
--    total (Inspección, Departamento). Se valida con trigger.
-- =====================================================================
CREATE TABLE usuario (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id              TINYINT UNSIGNED  NOT NULL,
    departamento_id     SMALLINT UNSIGNED NULL,
    nombres             VARCHAR(100) NOT NULL,
    apellidos           VARCHAR(100) NOT NULL,
    email               VARCHAR(150) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,      -- bcrypt / argon2 (password_hash() de PHP)
    activo              BOOLEAN      NOT NULL DEFAULT 1,
    ultimo_acceso       DATETIME     NULL,
    creado_en           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_email (email),
    KEY ix_usuario_departamento (departamento_id),
    CONSTRAINT fk_usuario_rol          FOREIGN KEY (rol_id)          REFERENCES rol (id),
    CONSTRAINT fk_usuario_departamento FOREIGN KEY (departamento_id) REFERENCES departamento (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. HORARIO_ATENCION
--    departamento_id NULL  = horario general (aplica a todos).
--    Con valor             = horario propio que reemplaza al general.
--    dia_semana: 1=lunes ... 7=domingo  (equivale a WEEKDAY(fecha)+1)
-- =====================================================================
CREATE TABLE horario_atencion (
    id                  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    departamento_id     SMALLINT UNSIGNED NULL,
    dia_semana          TINYINT UNSIGNED NOT NULL,
    hora_inicio         TIME NOT NULL,
    hora_fin            TIME NOT NULL,
    vigente_desde       DATE NOT NULL,
    vigente_hasta       DATE NULL,                  -- NULL = sin fecha de fin
    PRIMARY KEY (id),
    KEY ix_horario_busqueda (departamento_id, dia_semana, vigente_desde),
    CONSTRAINT fk_horario_departamento FOREIGN KEY (departamento_id) REFERENCES departamento (id) ON DELETE CASCADE,
    CONSTRAINT ck_horario_dia      CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT ck_horario_horas    CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_horario_vigencia CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. BLOQUEO_AGENDA (feriados y cierres; siempre la tarde completa)
--    sucursal NULL y departamento NULL   -> feriado nacional
--    sucursal con valor, departamento NULL -> feriado/cierre de esa sucursal
--    departamento con valor               -> cierre de ese departamento
--    Las columnas *_clave evitan duplicados aunque haya NULL.
-- =====================================================================
CREATE TABLE bloqueo_agenda (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id         TINYINT UNSIGNED  NULL,
    departamento_id     SMALLINT UNSIGNED NULL,
    fecha               DATE NOT NULL,
    motivo              VARCHAR(150) NOT NULL,
    origen              ENUM('API','MANUAL') NOT NULL DEFAULT 'MANUAL',
    creado_por          INT UNSIGNED NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sucursal_clave      TINYINT UNSIGNED  AS (IFNULL(sucursal_id, 0)) STORED,
    departamento_clave  SMALLINT UNSIGNED AS (IFNULL(departamento_id, 0)) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bloqueo (fecha, sucursal_clave, departamento_clave),
    CONSTRAINT fk_bloqueo_sucursal     FOREIGN KEY (sucursal_id)     REFERENCES sucursal (id),
    CONSTRAINT fk_bloqueo_departamento FOREIGN KEY (departamento_id) REFERENCES departamento (id),
    CONSTRAINT fk_bloqueo_usuario      FOREIGN KEY (creado_por)      REFERENCES usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. CITA (reserva pública, sin login; la persona no puede cancelar)
-- =====================================================================
CREATE TABLE cita (
    id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo                      VARCHAR(20)  NOT NULL,          -- CIT-2026-000045
    departamento_id             SMALLINT UNSIGNED NOT NULL,
    fecha                       DATE NOT NULL,
    hora_inicio                 TIME NOT NULL,
    hora_fin                    TIME NOT NULL,
    tipo_identificacion         ENUM('CEDULA','PASAPORTE') NOT NULL DEFAULT 'CEDULA',
    identificacion              VARCHAR(20)  NOT NULL,
    nombres                     VARCHAR(100) NOT NULL,
    apellidos                   VARCHAR(100) NOT NULL,
    email                       VARCHAR(150) NOT NULL,
    telefono                    VARCHAR(20)  NOT NULL,
    motivo                      VARCHAR(500) NOT NULL,
    acepta_tratamiento_datos    BOOLEAN NOT NULL,
    estado                      ENUM('PENDIENTE','ATENDIDA','NO_ASISTIO','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    gestionado_por              INT UNSIGNED NULL,
    observacion_interna         VARCHAR(500) NULL,
    ip_origen                   VARCHAR(45)  NULL,
    creado_en                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cita_codigo (codigo),
    KEY ix_cita_agenda (departamento_id, fecha, hora_inicio, estado),
    KEY ix_cita_persona (identificacion, estado, fecha),
    CONSTRAINT fk_cita_departamento FOREIGN KEY (departamento_id) REFERENCES departamento (id),
    CONSTRAINT fk_cita_usuario      FOREIGN KEY (gestionado_por)  REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT ck_cita_horas        CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_cita_datos        CHECK (acepta_tratamiento_datos = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. ANUNCIO (imagen PNG con vigencia; los vencidos se eliminan por cron)
-- =====================================================================
CREATE TABLE anuncio (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo                  VARCHAR(150) NOT NULL,          -- también texto alternativo (accesibilidad)
    imagen_ruta             VARCHAR(255) NOT NULL,
    enlace_url              VARCHAR(255) NULL,
    mostrar_en_carrusel     BOOLEAN NOT NULL DEFAULT 0,
    orden                   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    fecha_inicio            DATETIME NOT NULL,
    fecha_fin               DATETIME NOT NULL,
    publicado_por           INT UNSIGNED NOT NULL,
    creado_en               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_anuncio_vigencia (fecha_inicio, fecha_fin),
    CONSTRAINT fk_anuncio_usuario FOREIGN KEY (publicado_por) REFERENCES usuario (id),
    CONSTRAINT ck_anuncio_fechas  CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. OFERTA_LABORAL (estructura basada en Encuentra Empleo)
--    sucursal_id NULL = el puesto es para ambas sucursales
--    sueldo NULL      = se muestra "A convenir"
-- =====================================================================
CREATE TABLE oferta_laboral (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id             TINYINT UNSIGNED NULL,
    titulo                  VARCHAR(150) NOT NULL,
    actividades             TEXT NOT NULL,
    nivel_instruccion       ENUM('BACHILLER','TECNICO_TECNOLOGICO','TERCER_NIVEL','CUARTO_NIVEL','DOCTORADO') NOT NULL,
    area_estudio            VARCHAR(100) NULL,
    experiencia_min_anios   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    area_experiencia        VARCHAR(100) NULL,
    jornada                 ENUM('TIEMPO_COMPLETO','MEDIO_TIEMPO','POR_HORAS') NOT NULL,
    sueldo                  DECIMAL(8,2) NULL,
    vacantes                TINYINT UNSIGNED NOT NULL DEFAULT 1,
    informacion_adicional   TEXT NULL,
    fecha_publicacion       DATE NULL,
    fecha_cierre            DATE NOT NULL,
    puntaje_minimo          DECIMAL(3,1) NOT NULL DEFAULT 7.0,
    estado                  ENUM('BORRADOR','PUBLICADA','CERRADA') NOT NULL DEFAULT 'BORRADOR',
    publicado_por           INT UNSIGNED NOT NULL,
    creado_en               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_oferta_estado (estado, fecha_cierre),
    CONSTRAINT fk_oferta_sucursal FOREIGN KEY (sucursal_id)   REFERENCES sucursal (id),
    CONSTRAINT fk_oferta_usuario  FOREIGN KEY (publicado_por) REFERENCES usuario (id),
    CONSTRAINT ck_oferta_sueldo   CHECK (sueldo IS NULL OR sueldo > 0),
    CONSTRAINT ck_oferta_vacantes CHECK (vacantes >= 1),
    CONSTRAINT ck_oferta_puntaje  CHECK (puntaje_minimo BETWEEN 0 AND 10),
    CONSTRAINT ck_oferta_fechas   CHECK (fecha_publicacion IS NULL OR fecha_cierre >= fecha_publicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 10. REQUISITO_OFERTA ("conocimientos del cargo", para puntuar)
-- =====================================================================
CREATE TABLE requisito_oferta (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    oferta_laboral_id   INT UNSIGNED NOT NULL,
    descripcion         VARCHAR(255) NOT NULL,
    importancia         TINYINT UNSIGNED NOT NULL DEFAULT 2,   -- 1 baja, 2 media, 3 alta
    obligatorio         BOOLEAN NOT NULL DEFAULT 0,
    orden               TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY ix_requisito_oferta (oferta_laboral_id, orden),
    CONSTRAINT fk_requisito_oferta     FOREIGN KEY (oferta_laboral_id) REFERENCES oferta_laboral (id) ON DELETE CASCADE,
    CONSTRAINT ck_requisito_importancia CHECK (importancia BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 11. POSTULACION (pública, sin login; CV solo PDF, máx. 5 MB)
--     El PDF se guarda en una carpeta NO pública del hosting.
-- =====================================================================
CREATE TABLE postulacion (
    id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    oferta_laboral_id           INT UNSIGNED NOT NULL,
    tipo_identificacion         ENUM('CEDULA','PASAPORTE') NOT NULL DEFAULT 'CEDULA',
    identificacion              VARCHAR(20)  NOT NULL,
    nombres                     VARCHAR(100) NOT NULL,
    apellidos                   VARCHAR(100) NOT NULL,
    email                       VARCHAR(150) NOT NULL,
    telefono                    VARCHAR(20)  NOT NULL,
    ciudad_residencia           VARCHAR(80)  NOT NULL,
    nivel_instruccion           ENUM('BACHILLER','TECNICO_TECNOLOGICO','TERCER_NIVEL','CUARTO_NIVEL','DOCTORADO') NOT NULL,
    titulo_obtenido             VARCHAR(150) NULL,
    anios_experiencia           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    cv_ruta                     VARCHAR(255) NOT NULL,
    cv_nombre_original          VARCHAR(255) NOT NULL,
    cv_tamano_bytes             INT UNSIGNED NOT NULL,
    acepta_tratamiento_datos    BOOLEAN NOT NULL,
    estado                      ENUM('RECIBIDA','EN_REVISION','PRESELECCIONADA','DESCARTADA','CONTRATADA') NOT NULL DEFAULT 'RECIBIDA',
    revisado_por                INT UNSIGNED NULL,
    ip_origen                   VARCHAR(45) NULL,
    creado_en                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_postulacion_persona_oferta (oferta_laboral_id, identificacion),
    KEY ix_postulacion_estado (oferta_laboral_id, estado),
    CONSTRAINT fk_postulacion_oferta  FOREIGN KEY (oferta_laboral_id) REFERENCES oferta_laboral (id),
    CONSTRAINT fk_postulacion_usuario FOREIGN KEY (revisado_por)      REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT ck_postulacion_cv      CHECK (cv_tamano_bytes BETWEEN 1 AND 5242880),
    CONSTRAINT ck_postulacion_pdf     CHECK (LOWER(cv_nombre_original) LIKE '%.pdf'),
    CONSTRAINT ck_postulacion_datos   CHECK (acepta_tratamiento_datos = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 12. EVALUACION_POSTULACION (cuenta la más reciente)
--     Hoy: REGLAS o MANUAL.  Futuro: IA, sin cambiar la estructura.
-- =====================================================================
CREATE TABLE evaluacion_postulacion (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    postulacion_id      INT UNSIGNED NOT NULL,
    puntaje             DECIMAL(3,1) NOT NULL,
    metodo              ENUM('REGLAS','MANUAL','IA') NOT NULL,
    justificacion       TEXT NULL,
    detalle             JSON NULL,          -- resultado por requisito
    proveedor_ia        VARCHAR(50) NULL,
    modelo_ia           VARCHAR(80) NULL,
    evaluado_por        INT UNSIGNED NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_evaluacion_postulacion (postulacion_id, creado_en),
    CONSTRAINT fk_evaluacion_postulacion FOREIGN KEY (postulacion_id) REFERENCES postulacion (id) ON DELETE CASCADE,
    CONSTRAINT fk_evaluacion_usuario     FOREIGN KEY (evaluado_por)   REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT ck_evaluacion_puntaje     CHECK (puntaje BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TRIGGERS (reglas de negocio que deben cumplirse siempre)
-- =====================================================================
DELIMITER $$

-- Usuario: si su rol gestiona citas sin acceso total, debe tener departamento
CREATE TRIGGER tg_usuario_bi BEFORE INSERT ON usuario FOR EACH ROW
BEGIN
    IF NEW.departamento_id IS NULL AND EXISTS (
        SELECT 1 FROM rol WHERE id = NEW.rol_id AND gestiona_citas = 1 AND acceso_total = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este rol requiere un departamento asignado';
    END IF;
END$$

CREATE TRIGGER tg_usuario_bu BEFORE UPDATE ON usuario FOR EACH ROW
BEGIN
    IF NEW.departamento_id IS NULL AND EXISTS (
        SELECT 1 FROM rol WHERE id = NEW.rol_id AND gestiona_citas = 1 AND acceso_total = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este rol requiere un departamento asignado';
    END IF;
END$$

-- Cita: cupo por turno, una cita pendiente por departamento,
-- y nunca dos citas a la misma fecha y hora en departamentos distintos.
CREATE TRIGGER tg_cita_bi BEFORE INSERT ON cita FOR EACH ROW
BEGIN
    DECLARE v_cupo TINYINT UNSIGNED;
    DECLARE v_ocupados INT;

    -- Bloquea la fila del departamento: dos reservas simultáneas se atienden en fila
    SELECT cupo_por_turno INTO v_cupo
      FROM departamento WHERE id = NEW.departamento_id FOR UPDATE;

    SELECT COUNT(*) INTO v_ocupados
      FROM cita
     WHERE departamento_id = NEW.departamento_id
       AND fecha = NEW.fecha AND hora_inicio = NEW.hora_inicio
       AND estado = 'PENDIENTE';
    IF v_ocupados >= v_cupo THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El turno seleccionado ya no tiene cupo';
    END IF;

    IF EXISTS (SELECT 1 FROM cita
                WHERE identificacion = NEW.identificacion
                  AND departamento_id = NEW.departamento_id
                  AND estado = 'PENDIENTE' AND fecha >= CURDATE()) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya tiene una cita pendiente con este departamento';
    END IF;

    IF EXISTS (SELECT 1 FROM cita
                WHERE identificacion = NEW.identificacion
                  AND fecha = NEW.fecha
                  AND hora_inicio < NEW.hora_fin AND hora_fin > NEW.hora_inicio
                  AND estado = 'PENDIENTE') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya tiene otra cita en ese mismo horario';
    END IF;
END$$

DELIMITER ;

-- =====================================================================
-- VISTAS
-- =====================================================================

-- Anuncios que se ven hoy (carrusel: filtrar mostrar_en_carrusel = 1)
CREATE VIEW v_anuncio_vigente AS
SELECT id, titulo, imagen_ruta, enlace_url, mostrar_en_carrusel, orden, fecha_inicio, fecha_fin
  FROM anuncio
 WHERE NOW() BETWEEN fecha_inicio AND fecha_fin;

-- Ofertas visibles en "Trabaja con nosotros"
CREATE VIEW v_oferta_abierta AS
SELECT o.id, o.titulo, o.actividades, o.nivel_instruccion, o.area_estudio,
       o.experiencia_min_anios, o.area_experiencia, o.jornada,
       o.sueldo,                                   -- NULL -> mostrar "A convenir"
       o.vacantes, o.informacion_adicional, o.fecha_publicacion, o.fecha_cierre,
       IFNULL(s.ciudad, 'Todas las sucursales') AS lugar_trabajo
  FROM oferta_laboral o
  LEFT JOIN sucursal s ON s.id = o.sucursal_id
 WHERE o.estado = 'PUBLICADA'
   AND (o.fecha_publicacion IS NULL OR o.fecha_publicacion <= CURDATE())
   AND o.fecha_cierre >= CURDATE();

-- Lista de RR.HH.: postulaciones con su puntaje más reciente
CREATE VIEW v_postulacion_puntaje AS
SELECT p.id, p.oferta_laboral_id, o.titulo AS oferta,
       p.nombres, p.apellidos, p.email, p.telefono, p.estado, p.creado_en,
       e.puntaje, e.metodo, e.justificacion,
       CASE WHEN e.puntaje IS NULL THEN 'SIN_EVALUAR'
            WHEN e.puntaje >= o.puntaje_minimo THEN 'CONSIDERADA'
            ELSE 'BAJO_MINIMO' END AS clasificacion
  FROM postulacion p
  JOIN oferta_laboral o ON o.id = p.oferta_laboral_id
  LEFT JOIN (
        SELECT postulacion_id, puntaje, metodo, justificacion,
               ROW_NUMBER() OVER (PARTITION BY postulacion_id ORDER BY creado_en DESC, id DESC) AS n
          FROM evaluacion_postulacion
  ) e ON e.postulacion_id = p.id AND e.n = 1;

-- =====================================================================
-- DATOS INICIALES
-- =====================================================================
INSERT INTO sucursal (ciudad, atiende_citas) VALUES
 ('Quito',    1),
 ('Cojimíes', 0);

INSERT INTO rol (codigo, nombre, acceso_total, gestiona_anuncios, gestiona_citas, gestiona_empleos) VALUES
 ('SUPER_ADMIN',  'Super Administrador', 1, 1, 1, 1),
 ('RECTORADO',    'Rectorado',           1, 1, 1, 1),
 ('INSPECCION',   'Inspección',          0, 1, 1, 0),
 ('RRHH',         'Recursos Humanos',    0, 0, 0, 1),
 ('DEPARTAMENTO', 'Departamento',        0, 0, 1, 0);

-- Horario general: lunes a viernes, 14:00 a 15:20
INSERT INTO horario_atencion (departamento_id, dia_semana, hora_inicio, hora_fin, vigente_desde) VALUES
 (NULL, 1, '14:00', '15:20', '2026-01-01'),
 (NULL, 2, '14:00', '15:20', '2026-01-01'),
 (NULL, 3, '14:00', '15:20', '2026-01-01'),
 (NULL, 4, '14:00', '15:20', '2026-01-01'),
 (NULL, 5, '14:00', '15:20', '2026-01-01');

-- Departamentos de Quito: completar teléfonos/correos reales y agregar los demás
INSERT INTO departamento (sucursal_id, nombre, telefono, email)
SELECT id, 'Inspección', '0000000000', 'inspeccion.quito@ejemplo.edu.ec' FROM sucursal WHERE ciudad = 'Quito';

-- El primer Super Admin se crea desde la aplicación (para generar el
-- password_hash con PHP password_hash()). Nunca insertar contraseñas en texto plano.

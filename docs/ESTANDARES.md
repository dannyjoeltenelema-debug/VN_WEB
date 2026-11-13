# Estándares de código — obligatorios en todo el proyecto

Objetivo: código limpio, entendible, seguro y profesional. Si una tarea obliga a romper
alguna de estas reglas, detente y explica por qué antes de hacerlo.

## 1. Separación estricta de capas (nada de código espagueti)

- **Prohibido** en archivos Blade:
  - atributos `style="..."`;
  - etiquetas `<style>` y `<script>` con código;
  - manejadores en línea (`onclick=`, `onchange=`, `onsubmit=`…);
  - lógica de negocio, consultas a la base de datos o `@php` con lógica.
- Blade solo presenta datos: layouts, componentes (`<x-...>`), parciales, `@if`/`@foreach` simples.
- CSS solo en archivos `.css`; JavaScript solo en archivos `.js`.
- Para pasar datos de PHP a JavaScript: atributos `data-*` en el HTML (por ejemplo `data-url-turnos="{{ route(...) }}"`). Nada de variables JS impresas desde Blade.
- Esta separación también permite una Content-Security-Policy estricta (ver Seguridad).

## 2. Estructura de carpetas

```
app/
├── Enums/                 Enums respaldados (EstadoCita, Jornada, NivelInstruccion…)
├── Http/
│   ├── Controllers/
│   │   ├── Publico/       Controladores del sitio público
│   │   └── Panel/         Controladores del panel de operadores
│   ├── Requests/          Form Requests (toda validación de entrada vive aquí)
│   └── Middleware/        Cabeceras de seguridad, etc.
├── Models/                Modelos Eloquent (relaciones, casts, scopes; sin lógica de negocio pesada)
├── Policies/              Autorización por rol
├── Rules/                 Reglas de validación propias (p. ej. CedulaEcuatoriana)
├── Services/              Lógica de negocio (un servicio = una responsabilidad)
│   └── Contracts/         Interfaces de los servicios
└── Providers/             Registro de interfaces → implementaciones

resources/views/
├── layouts/               publico.blade.php, panel.blade.php
├── components/            Componentes Blade reutilizables
├── partials/              navbar, footer…
├── publico/               Una carpeta por sección o página pública
└── panel/                 Vistas del panel

public/css/
├── tokens.css             Variables CSS (colores, tipografía, radios, espacios). Única fuente de colores
├── base.css               Reset mínimo, tipografía y utilidades propias
├── componentes/           Un archivo por componente (navbar.css, boton.css, tarjeta.css…)
└── paginas/               Estilos específicos de una página (inicio.css, cita.css…)

public/js/
├── core/                  Utilidades compartidas (http.js con fetch + CSRF, dom.js…)
└── modulos/               Un módulo por funcionalidad (carrusel/, cita/, postulacion/…)
                           separado en api.js (llamadas), vista.js (DOM) y main.js (arranque)
```

- JavaScript con módulos ES (`<script type="module" src="...">`), sin variables globales.
- Cada vista carga solo el CSS y el JS que necesita (con `@push` / `@stack` en el layout).
- Sin Vite ni npm: los archivos se sirven desde `public/` con `asset()`.

## 3. Principios SOLID aplicados a Laravel

- **S — Responsabilidad única.**
  - Controladores delgados: reciben el Form Request, llaman a un servicio y devuelven una vista o JSON.
  - Nada de controladores de más de ~7 acciones ni métodos largos.
- **O — Abierto/cerrado.** Las partes que cambiarán se diseñan detrás de una interfaz. Ejemplo: `EvaluadorPostulacion`, con `EvaluadorPorReglas` hoy y `EvaluadorConIA` en el futuro, sin modificar quien lo usa.
- **L — Sustitución de Liskov.** Toda implementación de una interfaz cumple el mismo contrato: mismos tipos, mismas excepciones y mismo significado.
- **I — Segregación de interfaces.** Interfaces pequeñas y específicas, no "interfaces dios".
- **D — Inversión de dependencias.**
  - Las clases dependen de interfaces, inyectadas por constructor.
  - El enlace interfaz → clase se hace en un Service Provider.
  - Nada de `new Servicio()` dentro de controladores.
- No crear capas sin necesidad: **no usar patrón Repository** sobre Eloquent salvo que haya una razón concreta y se consulte antes.

## 4. Estilo de código PHP

- `declare(strict_types=1);` en cada archivo PHP nuevo.
- Tipos en todos los parámetros, propiedades y retornos.
- PSR-12, formateado con **Laravel Pint** (`./vendor/bin/pint`) antes de terminar cada fase.
- Nombres del dominio en español, coherentes con la base de datos (`Cita`, `OfertaLaboral`, `DisponibilidadCitaService`).
- Métodos cortos y con nombres que expliquen qué hacen. Comentarios solo para explicar el *porqué*, no el *qué*.
- Valores fijos (estados, jornadas…) siempre con Enums, nunca con strings sueltos.
- Sin código comentado, sin `dd()`/`dump()` olvidados, sin archivos sin uso.

## 5. Seguridad

**Entrada de datos**
- Toda entrada se valida en un Form Request: tipos, longitudes iguales a las de la base de datos y formatos.
- Cédula ecuatoriana: regla propia `CedulaEcuatoriana`, que valida el dígito verificador.
- Modelos con `$fillable` explícito. Nunca `$guarded = []`.
- Consultas solo con Eloquent o Query Builder con parámetros. Nunca concatenar entrada del usuario en SQL.

**Salida (XSS)**
- Siempre `{{ }}` en Blade. `{!! !!}` está prohibido con cualquier dato que venga de usuarios.
- En JavaScript, insertar texto con `textContent`, nunca con `innerHTML` y datos externos.

**Formularios y AJAX**
- `@csrf` en todo formulario. `fetch` envía el token desde `<meta name="csrf-token">` (centralizado en `public/js/core/http.js`).
- Límite de envíos (`RateLimiter`) en los formularios públicos: reservar cita, postular, y la consulta de turnos.
- Un campo trampa oculto (honeypot) en los formularios públicos contra bots. Sin paquetes externos.
- Los endpoints AJAX devuelven JSON con mensajes claros y nunca exponen trazas ni datos internos.

**Archivos**
- **Hojas de vida:**
  - Validar `mimes:pdf`, `mimetypes:application/pdf` y `max:5120`.
  - Guardar en el disco **privado** (`storage/app/private/cv`) con nombre aleatorio generado por el sistema.
  - Nunca son accesibles por URL pública. Se descargan solo a través de un controlador protegido por Policy.
- **Imágenes de anuncios:**
  - Validar `mimes:png`, tamaño máximo y dimensiones.
  - Guardar con nombre aleatorio en el disco público.

**Panel**
- Autenticación de Laravel, con límite de intentos de inicio de sesión.
- Autorización con Policies/Gates basadas en los interruptores de `rol` (`acceso_total`, `gestiona_*`). Nunca comparar nombres de rol en el código.
- Cada consulta del panel filtra por lo que el usuario puede ver; por ejemplo, las citas solo de su departamento si no tiene acceso total.

**Cabeceras y configuración**
- Middleware de cabeceras de seguridad: `Content-Security-Policy` (solo self + los CDN usados), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.
- En producción: `APP_DEBUG=false`, HTTPS y cookies de sesión `secure` y `http_only`.
- Recursos de CDN (Bootstrap, Bootstrap Icons) con atributos `integrity` y `crossorigin`.

**Datos personales (LOPDP)**
- No registrar cédulas, correos ni teléfonos en los logs.
- Consentimiento obligatorio en los formularios.

## 6. Accesibilidad y calidad del frontend

- HTML semántico (`header`, `nav`, `main`, `section`, `footer`, `button` reales), un `h1` por página.
- Toda imagen con `alt`. Todo campo con `<label>`. Foco visible con teclado.
- Contraste mínimo 4.5:1; los colores permitidos están en `docs/ui/GUIA-UI.md`.
- Diseño adaptable a celular primero. Probar en 375 px, 768 px y 1280 px.

## 7. Pruebas

- Pruebas de funcionalidad (Feature tests) para cada endpoint público y cada regla de negocio: cupos, citas duplicadas, archivo no PDF, permisos por rol…
- Al terminar una fase: `php artisan test` y `./vendor/bin/pint` sin errores.

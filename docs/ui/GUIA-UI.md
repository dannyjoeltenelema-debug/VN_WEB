# Guía de interfaz (UI) — diseño aprobado

Los mockups aprobados están en `docs/ui/mockups/` (`Home.dc.html`, `Cita.dc.html`). Son
**referencia visual**: estructura, textos, colores y tamaños. Están escritos con estilos en
línea y un formato propio del lienzo de diseño. **No copiar ese código**: traducirlo a
Blade + clases CSS + módulos JS según `docs/ESTANDARES.md`. Las etiquetas
`<sc-if>`, `<sc-for>`, `{{ }}` y la clase `Component` de esos archivos son del lienzo de
diseño y no deben aparecer en el proyecto.

Logos (en los mockups aparecen como `/_blob/...`):
- Institucional: `public/img/logo-vida-nueva.png`.
- Club: `public/img/logo-exploradores.png`.

## Tokens de diseño (definir como variables en `public/css/tokens.css`)

| Token | Valor | Uso |
|---|---|---|
| `--vn-verde` | `#113f32` | Marca: títulos de sección, navbar activo, botones secundarios, íconos principales |
| `--vn-verde-oscuro` | `#0c2f25` | Footer |
| `--vn-verde-medio` | `#1f6f55` | Palabra resaltada del título del hero |
| `--vn-verde-tinte` | `#eef3f0` / `#e6eee9` | Fondos de íconos, etiquetas y espacios para fotos |
| `--vn-ocre-cta` | `#9a6a1c` | Botones de acción principales (texto blanco, contraste 4.7:1) |
| `--vn-ocre` | `#c68b2c` | **Solo detalles**: líneas, bordes decorativos. **Nunca con texto blanco encima** (2.95:1) |
| `--vn-ocre-tinte` | `#f7efe1` | Fondo de íconos ocre |
| `--vn-beige-boton` | `#efe9dd` | Botón suave ("Ver oferta académica") |
| `--vn-hueso` | `#faf8f4` | Fondo del hero y de la página de citas |
| `--vn-hueso-calido` | `#faf7f1` | Fondo de la sección Exploradores |
| `--vn-gris-fondo` | `#f8f9fa` | Secciones alternas |
| `--vn-borde` | `#e8ece9` | Bordes de tarjetas |
| `--vn-texto` | `#1f2a26` (títulos serif `#16241f`) | Texto principal |
| `--vn-texto-suave` | `#4d5a55` / `#5b6662` | Párrafos y etiquetas |
| `--vn-error` | `#a12a1f` sobre `#fdf0ef` | Mensajes de error |

Los colores de Bootstrap (`--bs-primary`, etc.) se sobrescriben en `tokens.css` con estos valores.

## Tipografía (Google Fonts, por CDN)

- **Plus Jakarta Sans** (400–800): todo el sitio.
- **Playfair Display** (700–800): solo el `h1` del hero, las cifras del hero, los títulos de las tarjetas flotantes del hero, el `h1` de la página de citas y el título de confirmación.
- Etiquetas superiores de sección: 13 px, peso 700, MAYÚSCULAS, espaciado 0.08em, color `--vn-ocre-cta`.
- Títulos de sección (`h2`): 36 px, peso 800, color `--vn-verde`.

## Formas y espacios

- Botones en forma de píldora (`border-radius: 999px`), altura mínima 44 px.
- Tarjetas: radio 20 px, borde de 1 px `--vn-borde`, sin sombra. Solo las tarjetas flotantes del hero llevan sombra suave.
- Campos de formulario: altura 48 px, radio 12 px.
- Ancho máximo del contenido: 1240 px. Secciones con 88 px de espacio vertical (56 px en celular).
- Íconos: **Bootstrap Icons** por CDN, de trazo simple y usados con moderación.

## Navegación (navbar de todas las páginas públicas)

`Logo + "Unidad Educativa / Vida Nueva"` · Inicio · Institucional ▾ (Nosotros, Exploradores Vida Nueva, Sucursales) · Admisiones · Oferta académica · Anuncios · Contacto · [Trabaja con nosotros] (contorno verde) · [Agendar cita] (ocre)

- Fijo arriba y con fondo blanco.
- En menos de 1100 px se recoge en un menú de hamburguesa (componente collapse de Bootstrap).
- "Agendar cita" aparece **solo** en el navbar, en el botón del hero y en la lista de enlaces del footer.

## Páginas públicas

| Ruta | Página | Tipo |
|---|---|---|
| `/` | Inicio | Mixta |
| `/nosotros` | Nosotros | Estática |
| `/exploradores` | Club Exploradores Vida Nueva | Estática |
| `/sucursales` | Sucursales (Quito y Cojimíes) | Estática |
| `/admisiones` | Proceso de admisión | Estática |
| `/oferta-academica` | Oferta académica | Estática |
| `/contacto` | Contacto | Estática |
| `/anuncios` | Anuncios vigentes | Dinámica |
| `/citas/agendar` | Agendar cita | Dinámica (AJAX) |
| `/trabaja-con-nosotros` | Ofertas laborales abiertas | Dinámica |
| `/trabaja-con-nosotros/{oferta}` | Detalle de la oferta y formulario de postulación | Dinámica |

## Inicio (orden de secciones, ver `Home.dc.html`)

1. **Hero** (fondo hueso):
   - Etiqueta con ícono; `h1` serif con "excelencia académica" en `--vn-verde-medio`; párrafo.
   - Botones "Agendar una cita" (ocre) y "Ver oferta académica" (beige).
   - Cifras con divisores: **900+ Estudiantes · 50+ Profesionales · 40+ Años de trayectoria**.
   - A la derecha, foto con radio 28 px y dos tarjetas flotantes:
     - "Bachillerato técnico — Preparados para el futuro";
     - "Formación cristiana — Valores para la vida".
2. **Carrusel de anuncios:** solo los vigentes con `mostrar_en_carrusel`, con flechas y puntos. Si no hay anuncios, la sección no se muestra.
3. **Tres pilares:** Excelencia académica, Formación en valores, Club de Exploradores.
4. **Oferta académica:** 3 tarjetas con foto (Inicial, Básica, Bachillerato Técnico) y un enlace a su página.
5. **Admisiones:** 4 pasos numerados y un botón a la página.
6. **Exploradores:** fondo hueso cálido, insignia del club, texto, etiquetas de actividades y botón verde.
7. **Sucursales:** 2 tarjetas (Quito con la etiqueta "Atiende citas en línea").
8. **Footer** verde oscuro: logo, Institución, Servicios, Contacto, redes y derechos.

Los textos entre [corchetes] en los mockups son datos pendientes que entregará la institución: déjalos como marcadores visibles.

## Agendar cita (ver `Cita.dc.html`)

- Encabezado simple (logo + "Volver al inicio"), título serif e indicador de 4 pasos: Departamento · Fecha y hora · Tus datos · Confirmación.
- Dos columnas: el paso actual (izquierda) y el **Resumen de tu cita** (derecha, se apila en celular).
- **Paso 1:**
  - Sucursal: solo las que tienen `atiende_citas = 1` se pueden elegir; las demás aparecen desactivadas con "Citas en línea próximamente".
  - Departamentos activos de esa sucursal, como tarjetas seleccionables.
- **Paso 2:**
  - Días hábiles hasta `dias_max_anticipacion`. Los días bloqueados aparecen grises como "Cerrado".
  - Turnos con su estado: "2 cupos libres" / "1 cupo libre" (ocre) / "Sin cupo" / "Ya pasó".
  - Días y turnos se consultan por **AJAX sin recargar**.
- **Paso 3:**
  - Campos: tipo y número de identificación, nombres, apellidos, correo, celular, motivo y consentimiento.
  - La validación en el navegador marca los campos en rojo; el servidor valida de nuevo siempre.
- **Paso 4:**
  - Ícono de éxito, código `CIT-AAAA-NNNNNN` y aviso de correo enviado.
  - Texto: "Si no puedes asistir, comunícate con {departamento} al {teléfono}". No hay opción de cancelar.
- Botones "Atrás" y "Continuar"/"Confirmar cita" (ocre). "Continuar" queda desactivado hasta completar el paso.

## Trabaja con nosotros (sin mockup: aplicar el mismo sistema visual)

- **Lista:**
  - Encabezado como el de citas: etiqueta, título serif y párrafo.
  - Tarjetas de oferta con: título, sucursal ("Todas las sucursales" si es NULL), jornada, sueldo o "A convenir", vacantes, fecha de cierre y botón "Ver oferta".
  - Si no hay ofertas abiertas: un estado vacío amable.
- **Detalle:**
  - A la izquierda, la información en bloques como Encuentra Empleo: actividades, perfil (instrucción, área de estudio, experiencia, área de experiencia), requisitos (`requisito_oferta`, marcando los obligatorios) e información adicional.
  - A la derecha (debajo en celular), el formulario de postulación: identificación, nombres, apellidos, correo, teléfono, ciudad, nivel de instrucción, título, años de experiencia, hoja de vida (PDF, máx. 5 MB, con indicación visible) y consentimiento.
- **Confirmación:** mismo estilo que la confirmación de cita, sin código.

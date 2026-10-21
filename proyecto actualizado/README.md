# Pase Inteligente Escolar

Sistema de autenticación biométrica facial para la recogida segura de menores
en instituciones educativas. Verifica automáticamente la identidad del tutor
autorizado y notifica en tiempo real al salón del alumno.

## Arquitectura

Dos módulos independientes que comparten la misma base de datos MySQL:

```
 Cámara ─► reconocimiento-facial (Python) ──┐
            detección DNN + clasificación   │
            LBPH + notificaciones           ├──► MySQL (database/schema.sql)
                                            │
 Navegador ─► panel-web (PHP, patrón MVC) ──┘
              dashboard, tutores, bitácora
```

## Estructura del repositorio

| Carpeta | Contenido |
|---|---|
| `docs/` | Anteproyecto (PDF y planificación en sprints) |
| `database/` | `schema.sql`: tablas `alumnos`, `tutores`, `bitacora_recogida` |
| `reconocimiento-facial/` | Scripts Python: detección, registro, entrenamiento, verificación, pantalla del salón |
| `panel-web/` | Panel administrativo en PHP MVC (models, views, controllers) |

## Cómo levantarlo

### 1. Base de datos
1. Inicia MySQL (XAMPP o un MySQL compartido en la nube).
2. Importa `database/schema.sql` (phpMyAdmin → Importar).

### 2. Módulo de reconocimiento facial (Python)
```bash
cd reconocimiento-facial
python -m venv .venv
.venv\Scripts\activate          # Windows
pip install -r requirements.txt

python listar_camaras.py                  # ver qué índice de cámara usar
python detectar_rostro.py --webcam --camara 1
python registrar.py --camara 1            # registra un tutor (rostro + MySQL)
python pantalla.py                        # pantalla del salón (otra terminal)
python main.py --verificar --camara 1     # punto de recogida (otra terminal)
```
La conexión a MySQL se configura con variables de entorno:
`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.

### 3. Panel web (PHP)
1. Copia `panel-web/` a `C:\xampp\htdocs\`.
2. Copia `config/database.example.php` como `config/database.php` y llénalo.
3. Abre `http://localhost/panel-web/public/index.php`.

## Tecnologías

- **Detección de rostro:** DNN (SSD / ResNet-10) con OpenCV
- **Reconocimiento:** LBPH (OpenCV)
- **Backend web:** PHP (MVC) + MySQL
- **Control de versiones:** Git y GitHub

## Datos sensibles

`dataset/` (fotos de rostros) y `models/` (modelo entrenado) contienen datos
biométricos: **no se suben al repositorio** (ver `.gitignore`). Las credenciales
tampoco: `panel-web/config/database.php` se crea localmente.

## Flujo de trabajo en equipo

- `main` siempre estable; no se hace push directo.
- Una rama por tarea (`feat/...`, `fix/...`, `docs/...`) y Pull Request con revisión.
- Mensajes de commit con prefijo: `feat:`, `fix:`, `docs:`, `chore:`.
- Cambios de tablas: se editan en `database/schema.sql` y se avisa al equipo.

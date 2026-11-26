"""Punto de recogida con reconocimiento facial.

Flujo:
  1. La cámara detecta rostros y LBPH los compara con los tutores registrados.
  2. Si el rostro coincide con un tutor registrado durante varios cuadros seguidos,
     aparece "Padre del menor autorizado" con los botones Aceptar / Rechazar.
  3. Aceptar: registra la recogida como autorizada y envía el correo al profesor
     (con el bloqueo de cinco minutos de la base de datos).
     Rechazar: registra el intento como NO autorizado y no envía nada.
  4. Un rostro que no coincide con nadie queda en la bitácora como intento no autorizado.

Con CONFIRMAR_CON_PERSONAL = False la autorización es automática al reconocer el rostro.
Se recomienda dejarlo en True: LBPH puede equivocarse o engañarse con una foto.

Se puede ejecutar con el botón Run del editor (cámara 0 por defecto).
Teclas: A = Aceptar (o aviso manual si no hay panel), R = Rechazar, Q = salir.
"""
import argparse
import os
import threading
import time
import unicodedata

import cv2

# models/ y notificaciones.log usan rutas relativas: trabajar siempre desde esta
# carpeta, sin importar desde dónde se ejecute el programa.
os.chdir(os.path.dirname(os.path.abspath(__file__)))

from database import Database
from detector import detectar_rostros
from notificar import avisar_llegada_confirmada, notificar_intento_no_autorizado
from verify import evaluate_frame, load_model

CONFIRMAR_CON_PERSONAL = True   # True: el personal pulsa Aceptar/Rechazar
INTERVALO_DETECCION = 3         # analiza 1 de cada N cuadros para no frenar el video
CICLOS_CONFIRMACION = 4         # análisis seguidos con el mismo rostro antes de decidir
COOLDOWN = 60                   # segundos antes de volver a evaluar el mismo rostro
VENTANA = 'Punto de recogida - aviso de llegada'
VERDE = (0, 200, 0)             # BGR
ROJO = (0, 0, 255)
BLANCO = (255, 255, 255)


def sin_acentos(texto):
    """cv2.putText no dibuja acentos ni ñ; se quitan solo para mostrarlos en pantalla."""
    return unicodedata.normalize('NFKD', str(texto)).encode('ascii', 'ignore').decode('ascii')


def confirmar_llegada(db):
    """Aviso manual (tecla A cuando no hay un tutor reconocido en pantalla)."""
    try:
        tutor_id = int(input('ID del tutor comprobado presencialmente (0 cancela): '))
    except ValueError:
        print('ID inválido.')
        return
    if tutor_id <= 0:
        return
    destino = db.obtener_destino(tutor_id)
    if not destino:
        print('Sin aula o profesor activo para este tutor.')
        return
    print(f"Tutor: {destino['tutor_nombre']} | Alumno: {destino['alumno_nombre']} | Aula: {destino['aula']}")
    if input('¿Comprobaste identidad y autorización de recogida presencialmente? [s/N]: ').strip().lower() != 's':
        print('Aviso cancelado.')
        return
    avisar_llegada_confirmada(db, tutor_id, autorizado=True)


def autorizar(db, tutor, confianza):
    """Registra la recogida autorizada y avisa al profesor. Corre en un hilo para
    que el correo (hasta 15 s) no congele la cámara."""
    try:
        if not (os.getenv('SMTP_USER') and os.getenv('SMTP_PASSWORD')):
            db.registrar_recogida(tutor['id'], tutor['alumno_id'], confianza, True)
            print(f"Recogida autorizada y registrada: {tutor['nombre']} -> {tutor['alumno_nombre']}.")
            print('No se envió correo: faltan las variables SMTP_USER y SMTP_PASSWORD.')
            return
        if avisar_llegada_confirmada(db, tutor['id'], confianza, True):
            print(f"Recogida autorizada: {tutor['nombre']} -> {tutor['alumno_nombre']}.")
    except RuntimeError as exc:
        print(f'Error de base de datos: {exc}')


def rechazar(db, tutor, confianza):
    try:
        db.registrar_recogida(tutor['id'], tutor['alumno_id'], confianza, False)
        print(f"Entrega rechazada por el personal: {tutor['nombre']}.")
    except RuntimeError as exc:
        print(f'Error de base de datos: {exc}')


def registrar_desconocido(db, confianza):
    try:
        db.registrar_recogida(None, None, confianza, False)
    except RuntimeError as exc:
        print(f'Error de base de datos: {exc}')
    notificar_intento_no_autorizado(confianza)


def buscar_tutor(db, nombre_label):
    """nombre_label viene de labels.json con formato '<label>_<nombre>', ej. '0_Luis'."""
    try:
        label_id = int(str(nombre_label).split('_')[0])
        return db.buscar_tutor_por_label(label_id)
    except (ValueError, RuntimeError) as exc:
        print(f'No se pudo buscar al tutor ({nombre_label}): {exc}')
        return None


def analizar(frame, recognizer, label_map):
    """Con modelo LBPH: reconoce. Sin modelo: solo detecta rostros (apoyo visual)."""
    if recognizer is not None:
        return evaluate_frame(frame, recognizer, label_map)
    return [{'bbox': (x, y, w, h), 'nombre': None, 'confianza': None, 'autorizado': False}
            for (x, y, w, h, _c) in detectar_rostros(frame)]


def dibujar_resultados(frame, resultados, reconocimiento):
    for r in resultados:
        x, y, w, h = r['bbox']
        if not reconocimiento:
            color, texto = VERDE, ''
        elif r['autorizado']:
            color, texto = VERDE, f"{sin_acentos(r['nombre'])} ({r['confianza']})"
        else:
            color, texto = ROJO, 'NO RECONOCIDO'
        cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)
        if texto:
            cv2.putText(frame, texto, (x, max(20, y - 8)), cv2.FONT_HERSHEY_SIMPLEX, .6, color, 2)
    cv2.putText(frame, f'Rostros detectados: {len(resultados)}', (10, 60),
                cv2.FONT_HERSHEY_SIMPLEX, .65, VERDE, 2)
    cv2.putText(frame, 'A: aceptar / aviso manual | R: rechazar | Q: salir', (10, 30),
                cv2.FONT_HERSHEY_SIMPLEX, .6, BLANCO, 2)


def dibujar_panel(frame, tutor):
    """Mensaje 'Padre del menor autorizado' con botones. Devuelve las áreas
    (x1, y1, x2, y2) de Aceptar y Rechazar para detectar el clic."""
    alto, ancho = frame.shape[:2]
    fuente = cv2.FONT_HERSHEY_SIMPLEX

    capa = frame.copy()
    cv2.rectangle(capa, (0, alto - 170), (ancho, alto), (30, 30, 30), -1)
    cv2.addWeighted(capa, 0.8, frame, 0.2, 0, frame)

    cv2.putText(frame, 'Padre del menor autorizado', (20, alto - 135), fuente, .8, (0, 220, 0), 2)
    cv2.putText(frame, f"Tutor: {sin_acentos(tutor['nombre'])}", (20, alto - 105), fuente, .6, BLANCO, 1)
    cv2.putText(frame, f"Alumno: {sin_acentos(tutor['alumno_nombre'])}", (20, alto - 80), fuente, .6, BLANCO, 1)

    mitad = ancho // 2
    btn_aceptar = (20, alto - 60, mitad - 10, alto - 15)
    btn_rechazar = (mitad + 10, alto - 60, ancho - 20, alto - 15)
    cv2.rectangle(frame, btn_aceptar[:2], btn_aceptar[2:], (0, 160, 0), -1)
    cv2.putText(frame, 'Aceptar (A)', (btn_aceptar[0] + 15, btn_aceptar[1] + 30), fuente, .7, BLANCO, 2)
    cv2.rectangle(frame, btn_rechazar[:2], btn_rechazar[2:], (0, 0, 190), -1)
    cv2.putText(frame, 'Rechazar (R)', (btn_rechazar[0] + 15, btn_rechazar[1] + 30), fuente, .7, BLANCO, 2)
    return btn_aceptar, btn_rechazar


def dentro(punto, area):
    x, y = punto
    x1, y1, x2, y2 = area
    return x1 <= x <= x2 and y1 <= y <= y2


def punto_de_recogida(db, camara=0):
    try:
        recognizer, label_map = load_model()
    except Exception as exc:  # modelo ausente, cv2.face no instalado, etc.
        print(f'No se pudo cargar el modelo LBPH ({exc}).')
        print('Solo habrá detección de rostros; no se autorizará a nadie automáticamente.')
        recognizer, label_map = None, None
    reconocimiento = recognizer is not None

    cap = cv2.VideoCapture(camara, cv2.CAP_MSMF)
    if not cap.isOpened():
        cap.release()
        raise RuntimeError(f'No se pudo abrir la cámara {camara}')

    estado = {'click': None}

    def on_mouse(evento, x, y, flags, param):
        if evento == cv2.EVENT_LBUTTONDOWN:
            estado['click'] = (x, y)

    cv2.namedWindow(VENTANA)
    cv2.setMouseCallback(VENTANA, on_mouse)
    print('Punto de recogida activo. A = aceptar, R = rechazar, Q = salir.')

    resultados = []
    deteccion_activa = True
    n_cuadro = 0
    racha_nombre, racha = None, 0
    ultimo_evento = {}   # clave -> momento de la última evaluación
    pendiente = None     # tutor reconocido esperando Aceptar/Rechazar
    banner = None        # (texto, color, expira)
    botones = None

    try:
        while True:
            ok, frame = cap.read()
            if not ok:
                print('No se recibió imagen de la cámara.')
                break

            ahora = time.time()
            n_cuadro += 1
            ciclo = deteccion_activa and n_cuadro % INTERVALO_DETECCION == 0

            if ciclo:
                try:
                    resultados = analizar(frame, recognizer, label_map)
                except FileNotFoundError as exc:
                    print(exc)
                    print('Se continúa sin detección de rostros.')
                    deteccion_activa, resultados = False, []

            if ciclo and reconocimiento and pendiente is None:
                reconocidos = [r for r in resultados if r['autorizado']]
                desconocidos = [r for r in resultados if not r['autorizado']]
                if reconocidos:
                    r = reconocidos[0]
                    racha = racha + 1 if r['nombre'] == racha_nombre else 1
                    racha_nombre = r['nombre']
                    if racha >= CICLOS_CONFIRMACION and ahora - ultimo_evento.get(r['nombre'], 0) >= COOLDOWN:
                        ultimo_evento[r['nombre']] = ahora
                        racha = 0
                        tutor = buscar_tutor(db, r['nombre'])
                        if tutor is None:
                            banner = ('Rostro reconocido pero sin tutor registrado en la BD', ROJO, ahora + 4)
                        elif CONFIRMAR_CON_PERSONAL:
                            pendiente = {'tutor': tutor, 'confianza': r['confianza'], 'clave': r['nombre']}
                        else:
                            threading.Thread(target=autorizar, args=(db, tutor, r['confianza']), daemon=True).start()
                            banner = (f"Autorizado: {sin_acentos(tutor['nombre'])}", VERDE, ahora + 4)
                elif desconocidos:
                    racha = racha + 1 if racha_nombre == '_desconocido' else 1
                    racha_nombre = '_desconocido'
                    if racha >= CICLOS_CONFIRMACION and ahora - ultimo_evento.get('_desconocido', 0) >= COOLDOWN:
                        ultimo_evento['_desconocido'] = ahora
                        racha = 0
                        registrar_desconocido(db, desconocidos[0]['confianza'])
                        banner = ('Persona no reconocida: intento registrado', ROJO, ahora + 4)
                else:
                    racha_nombre, racha = None, 0

            dibujar_resultados(frame, resultados, reconocimiento)
            if banner and ahora < banner[2]:
                cv2.putText(frame, banner[0], (10, 90), cv2.FONT_HERSHEY_SIMPLEX, .65, banner[1], 2)
            botones = dibujar_panel(frame, pendiente['tutor']) if pendiente else None
            cv2.imshow(VENTANA, frame)

            tecla = cv2.waitKey(1) & 0xFF
            clic, estado['click'] = estado['click'], None
            if tecla in (ord('q'), ord('Q')):
                break

            if pendiente is not None:
                acepta = tecla in (ord('a'), ord('A')) or (clic and dentro(clic, botones[0]))
                rechaza = tecla in (ord('r'), ord('R')) or (clic and dentro(clic, botones[1]))
                if acepta or rechaza:
                    tutor, conf = pendiente['tutor'], pendiente['confianza']
                    if acepta:
                        threading.Thread(target=autorizar, args=(db, tutor, conf), daemon=True).start()
                        banner = (f"Entrega autorizada: {sin_acentos(tutor['nombre'])}", VERDE, time.time() + 4)
                    else:
                        rechazar(db, tutor, conf)
                        banner = ('Entrega rechazada', ROJO, time.time() + 4)
                    ultimo_evento[pendiente['clave']] = time.time()
                    pendiente = None
            elif tecla in (ord('a'), ord('A')):
                try:
                    confirmar_llegada(db)
                except RuntimeError as exc:
                    print(f'Error de base de datos: {exc}')
    finally:
        cap.release()
        cv2.destroyAllWindows()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--verificar', action='store_true', help='Opcional: abre el punto de recogida')
    parser.add_argument('--camara', type=int, default=0, help='Índice de cámara (0 por defecto)')
    args = parser.parse_args()
    punto_de_recogida(Database(), args.camara)
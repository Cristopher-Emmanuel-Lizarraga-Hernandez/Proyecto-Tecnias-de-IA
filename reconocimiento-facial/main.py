"""
main.py — Punto de entrada del sistema de pase inteligente escolar.

Conecta: detección/reconocimiento facial (verify.py) + base de datos
(database.py) + notificaciones (notificar.py) en un solo flujo, simulando
el punto de recogida en la entrada de la escuela.

Cuando se valida el rostro de un tutor, aparece el mensaje
"Padre del menor autorizado" con dos botones (Aceptar / Rechazar) para que
el personal confirme la entrega. También funcionan las teclas A y R.

Uso:
    python main.py --setup                 # crea alumno y tutor de ejemplo
    python main.py --verificar             # corre la verificación en vivo
    python main.py --verificar --camara 1  # usa otra cámara
"""
import argparse
import time
import unicodedata

import cv2

from database import Database
from notificar import notificar_llegada_autorizada, notificar_intento_no_autorizado
from verify import evaluate_frame, load_model

VENTANA = "Punto de recogida - pase inteligente"
COOLDOWN = 60              # segundos antes de volver a registrar el mismo evento
FRAMES_CONFIRMACION = 5    # frames seguidos con el mismo rostro antes de validar


def sin_acentos(texto) -> str:
    """cv2.putText no dibuja acentos ni ñ; los quitamos solo para mostrarlos."""
    return unicodedata.normalize("NFKD", str(texto)).encode("ascii", "ignore").decode("ascii")


def setup_demo(db: Database):
    """Crea un alumno y lo asocia a un tutor de ejemplo (embedding_label 0),
    asumiendo que ya corriste enroll.py + train.py para ese label."""
    alumno_id = db.registrar_alumno("Alumno Demo", "1A")
    tutor_id = db.registrar_tutor("Tutor Demo", alumno_id, embedding_label=0)
    print(f"Alumno creado (id={alumno_id}), Tutor creado (id={tutor_id}, label=0)")
    print("Asegúrate de que 'label=0' en models/labels.json corresponda a este tutor.")


def dibujar_panel(frame, tutor):
    """Dibuja el mensaje de validación y los botones. Devuelve las áreas
    (x1, y1, x2, y2) de Aceptar y Rechazar para detectar el clic."""
    alto, ancho = frame.shape[:2]
    fuente = cv2.FONT_HERSHEY_SIMPLEX

    # Fondo semitransparente
    capa = frame.copy()
    cv2.rectangle(capa, (0, alto - 170), (ancho, alto), (30, 30, 30), -1)
    cv2.addWeighted(capa, 0.8, frame, 0.2, 0, frame)

    cv2.putText(frame, "Padre del menor autorizado", (20, alto - 135),
                fuente, 0.8, (0, 220, 0), 2)
    cv2.putText(frame, f"Tutor: {sin_acentos(tutor['nombre'])}", (20, alto - 105),
                fuente, 0.6, (255, 255, 255), 1)
    cv2.putText(frame, f"Alumno: {sin_acentos(tutor['alumno_nombre'])}", (20, alto - 80),
                fuente, 0.6, (255, 255, 255), 1)

    mitad = ancho // 2
    btn_aceptar = (20, alto - 60, mitad - 10, alto - 15)
    btn_rechazar = (mitad + 10, alto - 60, ancho - 20, alto - 15)

    cv2.rectangle(frame, btn_aceptar[:2], btn_aceptar[2:], (0, 160, 0), -1)
    cv2.putText(frame, "Aceptar (A)", (btn_aceptar[0] + 15, btn_aceptar[1] + 30),
                fuente, 0.7, (255, 255, 255), 2)

    cv2.rectangle(frame, btn_rechazar[:2], btn_rechazar[2:], (0, 0, 190), -1)
    cv2.putText(frame, "Rechazar (R)", (btn_rechazar[0] + 15, btn_rechazar[1] + 30),
                fuente, 0.7, (255, 255, 255), 2)

    return btn_aceptar, btn_rechazar


def dentro(punto, area) -> bool:
    x, y = punto
    x1, y1, x2, y2 = area
    return x1 <= x <= x2 and y1 <= y <= y2


def punto_de_recogida(db: Database, camara=0):
    recognizer, label_map = load_model()
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")

    # Captura de clics del mouse sobre la ventana
    estado = {"click": None}

    def on_mouse(evento, x, y, flags, param):
        if evento == cv2.EVENT_LBUTTONDOWN:
            estado["click"] = (x, y)

    cv2.namedWindow(VENTANA)
    cv2.setMouseCallback(VENTANA, on_mouse)

    print("Punto de recogida activo. A = aceptar, R = rechazar, Q = salir.")

    ultimo_evento = {}      # clave -> timestamp del último registro
    pendiente = None        # tutor esperando decisión del personal
    racha_nombre = None
    racha = 0

    while True:
        ok, frame = cap.read()
        if not ok:
            break

        resultados = evaluate_frame(frame, recognizer, label_map)
        ahora = time.time()

        for r in resultados:
            x, y, w, h = r["bbox"]
            color = (0, 200, 0) if r["autorizado"] else (0, 0, 255)
            cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)

        if pendiente is None:
            # --- Rostros no autorizados: se registran con enfriamiento ---
            for r in resultados:
                if not r["autorizado"] and ahora - ultimo_evento.get("no_autorizado", 0) >= COOLDOWN:
                    ultimo_evento["no_autorizado"] = ahora
                    db.registrar_recogida(None, None, r["confianza"], False)
                    notificar_intento_no_autorizado(r["confianza"])

            # --- Rostro autorizado: exige varios frames seguidos ---
            autorizados = [r for r in resultados if r["autorizado"]]
            if autorizados:
                r = autorizados[0]
                if r["nombre"] == racha_nombre:
                    racha += 1
                else:
                    racha_nombre, racha = r["nombre"], 1

                if racha >= FRAMES_CONFIRMACION and ahora - ultimo_evento.get(r["nombre"], 0) >= COOLDOWN:
                    label_id = int(r["nombre"].split("_")[0]) if "_" in r["nombre"] else None
                    tutor = db.buscar_tutor_por_label(label_id) if label_id is not None else None
                    if tutor:
                        pendiente = {"tutor": tutor, "confianza": r["confianza"], "clave": r["nombre"]}
                    else:
                        print(f"Rostro reconocido ({r['nombre']}) pero sin tutor registrado en la BD.")
                        ultimo_evento[r["nombre"]] = ahora
                    racha = 0
            else:
                racha_nombre, racha = None, 0

            estado["click"] = None  # ignora clics cuando no hay panel
            tecla = cv2.waitKey(1) & 0xFF
        else:
            btn_aceptar, btn_rechazar = dibujar_panel(frame, pendiente["tutor"])
            tecla = cv2.waitKey(1) & 0xFF
            clic = estado["click"]

            aceptar = tecla in (ord("a"), ord("A")) or (clic and dentro(clic, btn_aceptar))
            rechazar = tecla in (ord("r"), ord("R")) or (clic and dentro(clic, btn_rechazar))

            if aceptar or rechazar:
                tutor = pendiente["tutor"]
                conf = pendiente["confianza"]
                if aceptar:
                    db.registrar_recogida(tutor["id"], tutor["alumno_id"], conf, True)
                    notificar_llegada_autorizada(tutor["nombre"], tutor["alumno_nombre"], conf)
                else:
                    # El personal rechazó la entrega: queda en la bitácora como no autorizada
                    db.registrar_recogida(tutor["id"], tutor["alumno_id"], conf, False)
                    notificar_intento_no_autorizado(conf)
                ultimo_evento[pendiente["clave"]] = ahora
                pendiente = None
            estado["click"] = None

        cv2.imshow(VENTANA, frame)
        if tecla == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Sistema de pase inteligente escolar")
    parser.add_argument("--setup", action="store_true", help="Crea alumno/tutor de ejemplo en la BD")
    parser.add_argument("--verificar", action="store_true", help="Corre el punto de recogida en vivo")
    parser.add_argument("--camara", type=int, default=0, help="Índice de cámara a usar")
    args = parser.parse_args()

    db = Database()

    if args.setup:
        setup_demo(db)
    elif args.verificar:
        punto_de_recogida(db, args.camara)
    else:
        parser.error("Usa --setup o --verificar")
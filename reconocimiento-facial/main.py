"""
main.py — Punto de entrada del sistema de pase inteligente escolar.

Conecta: detección/reconocimiento facial (verify.py) + base de datos
(database.py) + notificaciones (notificar.py) en un solo flujo, simulando
el punto de recogida en la entrada de la escuela.

Uso:
    python main.py --setup                 # crea alumno y tutor de ejemplo
    python main.py --verificar --webcam     # corre la verificación en vivo
"""
import argparse
import cv2

from database import Database
from notificar import notificar_llegada_autorizada, notificar_intento_no_autorizado
from verify import evaluate_frame, load_model


def setup_demo(db: Database):
    """Crea un alumno y lo asocia a un tutor de ejemplo (embedding_label 0),
    asumiendo que ya corriste enroll.py + train.py para ese label."""
    alumno_id = db.registrar_alumno("Alumno Demo", "1A")
    tutor_id = db.registrar_tutor("Tutor Demo", alumno_id, embedding_label=0)
    print(f"Alumno creado (id={alumno_id}), Tutor creado (id={tutor_id}, label=0)")
    print("Asegúrate de que 'label=0' en models/labels.json corresponda a este tutor.")


def punto_de_recogida(db: Database, camara=0):
    recognizer, label_map = load_model()
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")

    print("Punto de recogida activo. Presiona 'q' para salir.")
    ya_notificado = set()  # evita spamear notificaciones del mismo label repetidamente

    while True:
        ok, frame = cap.read()
        if not ok:
            break
        resultados = evaluate_frame(frame, recognizer, label_map)

        for r in resultados:
            x, y, w, h = r["bbox"]
            color = (0, 200, 0) if r["autorizado"] else (0, 0, 255)
            cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)

            label_id = int(r["nombre"].split("_")[0]) if "_" in r["nombre"] else None
            clave = (r["nombre"], r["autorizado"])

            if clave in ya_notificado:
                continue
            ya_notificado.add(clave)

            tutor = db.buscar_tutor_por_label(label_id) if label_id is not None else None

            if r["autorizado"] and tutor:
                db.registrar_recogida(tutor["id"], tutor["alumno_id"], r["confianza"], True)
                notificar_llegada_autorizada(tutor["nombre"], tutor["alumno_nombre"], r["confianza"])
            elif not r["autorizado"]:
                db.registrar_recogida(None, None, r["confianza"], False)
                notificar_intento_no_autorizado(r["confianza"])

        cv2.imshow("Punto de recogida - pase inteligente", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
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
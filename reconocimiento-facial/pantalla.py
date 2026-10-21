"""
pantalla.py — Simula la pantalla del salón de clases.

Corre en una ventana aparte (idealmente en otra laptop/tablet en el salón).
Lee el archivo estado_pantalla.json que actualiza notificar.py cada vez que
main.py detecta a un tutor, y muestra un aviso grande en pantalla.

Uso:
    python pantalla.py
(Corre este script AL MISMO TIEMPO que main.py --verificar en otra terminal)

Presiona 'q' para cerrar.
"""
import json
import os
import time
from datetime import datetime

import cv2
import numpy as np

ESTADO_PATH = "estado_pantalla.json"
SEGUNDOS_VISIBLE = 8  # cuánto tiempo se muestra el aviso antes de volver a "esperando"

VERDE = (80, 200, 80)
ROJO = (60, 60, 230)
GRIS = (90, 90, 90)
BLANCO = (255, 255, 255)


def leer_estado():
    if not os.path.exists(ESTADO_PATH):
        return None
    try:
        with open(ESTADO_PATH, encoding="utf-8") as f:
            return json.load(f)
    except (json.JSONDecodeError, OSError):
        return None


def estado_vigente(estado):
    if not estado:
        return False
    ts = datetime.fromisoformat(estado["timestamp"])
    return (datetime.now() - ts).total_seconds() <= SEGUNDOS_VISIBLE


def dibujar_pantalla(estado):
    lienzo = np.zeros((480, 800, 3), dtype=np.uint8)

    if estado_vigente(estado):
        if estado["tipo"] == "autorizado":
            lienzo[:] = VERDE
            cv2.putText(lienzo, "TUTOR AUTORIZADO", (140, 150),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.2, BLANCO, 3)
            cv2.putText(lienzo, f"{estado['tutor']}", (140, 230),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, BLANCO, 2)
            cv2.putText(lienzo, f"Alumno: {estado['alumno']}", (140, 280),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, BLANCO, 2)
            cv2.putText(lienzo, "Ya puedes salir", (140, 340),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.9, BLANCO, 2)
        else:
            lienzo[:] = ROJO
            cv2.putText(lienzo, "PERSONA NO RECONOCIDA", (90, 200),
                        cv2.FONT_HERSHEY_SIMPLEX, 1.0, BLANCO, 3)
            cv2.putText(lienzo, "Se notifico al personal", (130, 260),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, BLANCO, 2)
    else:
        lienzo[:] = GRIS
        cv2.putText(lienzo, "Esperando...", (280, 240),
                    cv2.FONT_HERSHEY_SIMPLEX, 1.0, BLANCO, 2)

    return lienzo


if __name__ == "__main__":
    cv2.namedWindow("Pantalla del salon", cv2.WINDOW_NORMAL)
    while True:
        estado = leer_estado()
        frame = dibujar_pantalla(estado)
        cv2.imshow("Pantalla del salon", frame)
        if cv2.waitKey(500) & 0xFF == ord("q"):
            break
    cv2.destroyAllWindows()

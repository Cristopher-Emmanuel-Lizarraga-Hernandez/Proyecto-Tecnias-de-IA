"""
detector.py — Detección de rostro con red neuronal (DNN), compartida por
todos los scripts del proyecto (enroll.py, verify.py, registrar.py, main.py,
detectar_rostro.py).

Por qué DNN en vez de Haar Cascade:
- Haar Cascade falla con rostros de perfil, poca luz, o parcialmente cubiertos.
- El modelo DNN (SSD sobre ResNet-10, entrenado por el equipo de OpenCV)
  detecta con mucha más precisión y da un puntaje de confianza por rostro,
  lo que permite filtrar detecciones dudosas.

Requiere los archivos del modelo en modelo_dnn/:
    deploy.prototxt
    res10_300x300_ssd_iter_140000.caffemodel
"""
import os
import cv2

MODEL_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "modelo_dnn")
PROTOTXT = os.path.join(MODEL_DIR, "deploy.prototxt")
WEIGHTS = os.path.join(MODEL_DIR, "res10_300x300_ssd_iter_140000.caffemodel")

CONFIDENCE_MINIMA = 0.6  # descarta detecciones con menos del 60% de confianza

_net = None


def _cargar_red():
    global _net
    if _net is None:
        if not (os.path.exists(PROTOTXT) and os.path.exists(WEIGHTS)):
            raise FileNotFoundError(
                "Faltan los archivos del modelo en modelo_dnn/. "
                "Descarga deploy.prototxt y res10_300x300_ssd_iter_140000.caffemodel."
            )
        _net = cv2.dnn.readNetFromCaffe(PROTOTXT, WEIGHTS)
    return _net


def detectar_rostros(frame_bgr, confianza_minima=CONFIDENCE_MINIMA):
    """
    Detecta rostros en una imagen a color (BGR, como la entrega la cámara).
    Devuelve una lista de tuplas (x, y, w, h, confianza), en píxeles reales
    de la imagen original — igual formato que devolvía Haar Cascade, para
    no tener que tocar el resto de los scripts.
    """
    net = _cargar_red()
    alto, ancho = frame_bgr.shape[:2]

    blob = cv2.dnn.blobFromImage(
        cv2.resize(frame_bgr, (300, 300)), 1.0, (300, 300),
        (104.0, 177.0, 123.0),  # valores promedio de normalización del modelo
    )
    net.setInput(blob)
    detecciones = net.forward()

    resultados = []
    for i in range(detecciones.shape[2]):
        confianza = float(detecciones[0, 0, i, 2])
        if confianza < confianza_minima:
            continue
        caja = detecciones[0, 0, i, 3:7] * [ancho, alto, ancho, alto]
        x1, y1, x2, y2 = caja.astype(int)
        x1, y1 = max(0, x1), max(0, y1)
        x2, y2 = min(ancho, x2), min(alto, y2)
        if x2 <= x1 or y2 <= y1:
            continue
        resultados.append((x1, y1, x2 - x1, y2 - y1, confianza))

    return resultados


def recortar_rostro_gris(frame_bgr, caja, tamano=200):
    """Recorta el rostro y lo devuelve en escala de grises, tamaño fijo —
    formato que usa el reconocedor LBPH (enroll.py/train.py/verify.py)."""
    x, y, w, h, _ = caja
    gris = cv2.cvtColor(frame_bgr, cv2.COLOR_BGR2GRAY)
    recorte = gris[y:y + h, x:x + w]
    return cv2.resize(recorte, (tamano, tamano))

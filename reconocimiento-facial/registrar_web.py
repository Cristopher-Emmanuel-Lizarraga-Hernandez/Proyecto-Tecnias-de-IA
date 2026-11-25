"""
registrar_web.py — Procesa las fotos de un tutor (tomadas por la cámara del NAVEGADOR),
guarda sus rostros en dataset/ y reentrena el modelo LBPH.

Lo ejecuta el panel PHP. NO pregunta nada por consola y NO escribe en MySQL:
PHP crea al alumno y al tutor en la base de datos solo si este script termina bien.

Uso (también lo puedes probar a mano):
    python registrar_web.py --label 4 --nombre "Ana Lopez" --imagenes C:\\ruta\\fotos --minimo 15

Códigos de salida:  0 = correcto | 2 = pocos rostros detectados | 3 = error | 4 = label repetido
"""
import argparse
import os
import re
import shutil
import sys
import unicodedata

import cv2
import numpy as np

from enroll import save_face
from registrar import DATASET_DIR, reentrenar_modelo

EXTENSIONES = (".jpg", ".jpeg", ".png")


def slug(texto: str) -> str:
    """'José Pérez' -> 'Jose_Perez' (solo letras, números y guion bajo; evita rutas peligrosas)."""
    ascii_ = unicodedata.normalize("NFKD", texto).encode("ascii", "ignore").decode()
    return re.sub(r"[^A-Za-z0-9]+", "_", ascii_).strip("_") or "tutor"


def leer_imagen(ruta: str):
    """Lee una imagen aunque la ruta tenga acentos (cv2.imread falla con ellos en Windows)."""
    datos = np.fromfile(ruta, dtype=np.uint8)
    if datos.size == 0:
        return None
    try:
        return cv2.imdecode(datos, cv2.IMREAD_COLOR)
    except cv2.error:
        return None


def main() -> int:
    p = argparse.ArgumentParser(description="Enrolamiento facial desde fotos del navegador")
    p.add_argument("--label", type=int, required=True)
    p.add_argument("--nombre", required=True)
    p.add_argument("--imagenes", required=True, help="Carpeta con las fotos tomadas por el navegador")
    p.add_argument("--minimo", type=int, default=15, help="Rostros mínimos que deben detectarse")
    a = p.parse_args()

    if not os.path.isdir(a.imagenes):
        print("No se encontró la carpeta de fotos.")
        return 3

    # Evita dos carpetas con el mismo label (se confundirían los tutores).
    if os.path.isdir(DATASET_DIR):
        repetidas = [d for d in os.listdir(DATASET_DIR) if d.startswith(f"{a.label}_")]
        if repetidas:
            print(f"Ya existe en dataset/ una carpeta con el label {a.label} ({repetidas[0]}). "
                  "Revisa o borra esa carpeta antes de registrar.")
            return 4

    carpeta = os.path.join(DATASET_DIR, f"{a.label}_{slug(a.nombre)}")
    os.makedirs(carpeta, exist_ok=True)
    try:
        archivos = sorted(f for f in os.listdir(a.imagenes) if f.lower().endswith(EXTENSIONES))
        guardados = 0
        for nombre in archivos:
            frame = leer_imagen(os.path.join(a.imagenes, nombre))
            if frame is not None:
                guardados = save_face(frame, carpeta, guardados)

        if guardados < a.minimo:
            shutil.rmtree(carpeta, ignore_errors=True)
            print(f"Solo se detectó un rostro en {guardados} de {len(archivos)} fotos "
                  f"(se necesitan al menos {a.minimo}). Mejora la iluminación, mira de frente a la "
                  "cámara y que no haya otras personas detrás.")
            return 2

        total_rostros, total_tutores = reentrenar_modelo()
        print(f"OK: {guardados} rostros guardados; modelo con {total_rostros} rostros de {total_tutores} persona(s).")
        return 0
    except Exception as e:  # noqa: BLE001
        shutil.rmtree(carpeta, ignore_errors=True)
        print(f"Error: {e}")
        return 3


if __name__ == "__main__":
    sys.exit(main())
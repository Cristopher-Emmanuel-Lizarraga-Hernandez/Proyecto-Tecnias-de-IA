"""
registrar.py — Flujo completo de alta de un tutor nuevo.

1. Pide datos del alumno y del tutor.
2. Escanea el rostro del tutor por webcam (25 capturas).
3. Reentrena el modelo LBPH con todos los rostros acumulados en dataset/.
4. Da de alta al alumno y al tutor en la base de datos MySQL.
5. Muestra una pantalla de confirmación.

Uso:
    python registrar.py --camara 0
"""
import argparse
import json
import os

import cv2

from database import Database
from enroll import save_face
from train import load_dataset

DATASET_DIR = "dataset"
MODEL_PATH = "models/lbph_model.yml"
LABELS_PATH = "models/labels.json"


def escanear_rostro(out_dir, camara, n_samples=25):
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")
    count = 0
    print("Escaneando rostro... mira a la cámara. Presiona 'q' para cancelar.")
    while count < n_samples:
        ok, frame = cap.read()
        if not ok:
            break
        count = save_face(frame, out_dir, count)
        cv2.putText(frame, f"Escaneando: {count}/{n_samples}", (10, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
        cv2.imshow("Registro - escaneo de rostro", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break
    cap.release()
    cv2.destroyAllWindows()
    return count


def reentrenar_modelo():
    faces, labels, label_map = load_dataset()
    if not faces:
        raise SystemExit("No hay rostros en dataset/. El escaneo pudo haber fallado.")
    recognizer = cv2.face.LBPHFaceRecognizer_create()
    recognizer.train(faces, labels)
    os.makedirs("models", exist_ok=True)
    recognizer.write(MODEL_PATH)
    with open(LABELS_PATH, "w", encoding="utf-8") as f:
        json.dump(label_map, f, ensure_ascii=False, indent=2)
    return len(faces), len(label_map)


def mostrar_confirmacion(nombre_tutor, nombre_alumno):
    import numpy as np
    lienzo = np.zeros((300, 700, 3), dtype=np.uint8)
    lienzo[:] = (80, 180, 80)
    cv2.putText(lienzo, "REGISTRO EXITOSO", (150, 100), cv2.FONT_HERSHEY_SIMPLEX, 1.1, (255, 255, 255), 3)
    cv2.putText(lienzo, f"Tutor: {nombre_tutor}", (100, 160), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
    cv2.putText(lienzo, f"Alumno: {nombre_alumno}", (100, 200), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
    cv2.putText(lienzo, "Presiona cualquier tecla para cerrar", (100, 260), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 1)
    cv2.imshow("Confirmacion de registro", lienzo)
    cv2.waitKey(0)
    cv2.destroyAllWindows()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Registro completo de un tutor nuevo")
    parser.add_argument("--camara", type=int, default=0, help="Índice de cámara a usar")
    parser.add_argument("--n", type=int, default=25, help="Número de capturas de rostro")
    args = parser.parse_args()

    nombre_alumno = input("Nombre del alumno: ").strip()
    grupo = input("Grupo del alumno (ej. 3A): ").strip()
    nombre_tutor = input("Nombre del tutor: ").strip()
    parentesco = input("Parentesco (padre/madre/tutor): ").strip() or "tutor"

    db = Database()
    label = db.siguiente_embedding_label()
    carpeta = os.path.join(DATASET_DIR, f"{label}_{nombre_tutor.replace(' ', '_')}")
    os.makedirs(carpeta, exist_ok=True)

    capturas = escanear_rostro(carpeta, args.camara, args.n)
    if capturas == 0:
        raise SystemExit("No se capturó ningún rostro. Intenta de nuevo con mejor iluminación.")

    total_rostros, total_tutores = reentrenar_modelo()
    print(f"Modelo reentrenado: {total_rostros} rostros, {total_tutores} tutor(es).")

    alumno_id = db.registrar_alumno(nombre_alumno, grupo)
    db.registrar_tutor(nombre_tutor, alumno_id, embedding_label=label, parentesco=parentesco)
    print(f"Alumno y tutor registrados en la base de datos (label={label}).")

    mostrar_confirmacion(nombre_tutor, nombre_alumno)

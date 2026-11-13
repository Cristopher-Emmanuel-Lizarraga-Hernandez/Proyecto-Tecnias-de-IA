"""
train.py — Entrena el modelo LBPH con todos los rostros en dataset/

Uso:
    python train.py
"""
import os
import json
import cv2
import numpy as np

DATASET_DIR = "dataset"
MODEL_PATH = "models/lbph_model.yml"
LABELS_PATH = "models/labels.json"


def load_dataset():
    faces = []
    labels = []
    label_map = {}  # label_id (int) -> "id_nombre"
    next_label = 0

    for carpeta in sorted(os.listdir(DATASET_DIR)):
        carpeta_path = os.path.join(DATASET_DIR, carpeta)
        if not os.path.isdir(carpeta_path):
            continue
        label_map[next_label] = carpeta
        for fname in os.listdir(carpeta_path):
            img_path = os.path.join(carpeta_path, fname)
            img = cv2.imread(img_path, cv2.IMREAD_GRAYSCALE)
            if img is None:
                continue
            faces.append(img)
            labels.append(next_label)
        next_label += 1

    return faces, np.array(labels), label_map


if __name__ == "__main__":
    os.makedirs("models", exist_ok=True)
    faces, labels, label_map = load_dataset()

    if len(faces) == 0:
        raise SystemExit("No hay rostros en dataset/. Corre enroll.py primero.")

    recognizer = cv2.face.LBPHFaceRecognizer_create()
    recognizer.train(faces, labels)
    recognizer.write(MODEL_PATH)

    with open(LABELS_PATH, "w", encoding="utf-8") as f:
        json.dump(label_map, f, ensure_ascii=False, indent=2)

    print(f"Modelo entrenado con {len(faces)} rostros de {len(label_map)} tutor(es).")
    print(f"Guardado en {MODEL_PATH}")

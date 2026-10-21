"""
enroll.py — Registro (enrollment) de tutores autorizados.
Ahora usa detector.py (DNN) para detectar rostros con mayor precisión.

Uso:
    python enroll.py --id 1 --nombre "Juan Perez" --webcam
    python enroll.py --id 1 --nombre "Juan Perez" --imagenes ./fotos_juan/
"""
import argparse
import os
import cv2

from detector import detectar_rostros, recortar_rostro_gris

DATASET_DIR = "dataset"


def save_face(frame_bgr, out_dir, count):
    rostros = detectar_rostros(frame_bgr)
    if not rostros:
        return count
    # Tomamos el rostro con mayor confianza (por si aparece más de uno)
    mejor = max(rostros, key=lambda r: r[4])
    face_crop = recortar_rostro_gris(frame_bgr, mejor)
    filepath = os.path.join(out_dir, f"{count:03d}.jpg")
    cv2.imwrite(filepath, face_crop)
    return count + 1


def enroll_from_webcam(out_dir, camara=0, n_samples=25):
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")
    count = 0
    print("Mirando a la cámara... presiona 'q' para cancelar.")
    while count < n_samples:
        ok, frame = cap.read()
        if not ok:
            break
        count = save_face(frame, out_dir, count)
        cv2.putText(frame, f"Capturas: {count}/{n_samples}", (10, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
        cv2.imshow("Enrollment - pase inteligente", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break
    cap.release()
    cv2.destroyAllWindows()
    print(f"Listo: {count} rostros guardados en {out_dir}")


def enroll_from_images(images_dir, out_dir):
    count = 0
    for fname in sorted(os.listdir(images_dir)):
        path = os.path.join(images_dir, fname)
        img = cv2.imread(path)
        if img is None:
            continue
        count = save_face(img, out_dir, count)
    print(f"Listo: {count} rostros guardados en {out_dir}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Enrollment de tutores")
    parser.add_argument("--id", required=True, help="ID numérico del tutor")
    parser.add_argument("--nombre", required=True, help="Nombre del tutor")
    parser.add_argument("--webcam", action="store_true", help="Capturar desde webcam")
    parser.add_argument("--imagenes", help="Carpeta con fotos existentes del tutor")
    parser.add_argument("--camara", type=int, default=0, help="Índice de cámara")
    parser.add_argument("--n", type=int, default=25, help="No. de capturas (webcam)")
    args = parser.parse_args()

    safe_name = args.nombre.strip().replace(" ", "_")
    out_dir = os.path.join(DATASET_DIR, f"{args.id}_{safe_name}")
    os.makedirs(out_dir, exist_ok=True)

    if args.webcam:
        enroll_from_webcam(out_dir, args.camara, args.n)
    elif args.imagenes:
        enroll_from_images(args.imagenes, out_dir)
    else:
        parser.error("Debes indicar --webcam o --imagenes")

"""
verify.py — Verificación de tutor en el punto de recogida.
Ahora usa detector.py (DNN) para localizar el rostro, y LBPH para
reconocer de quién se trata.

Uso:
    python verify.py --webcam --camara 2
    python verify.py --imagen ./prueba.jpg

Nota sobre el umbral (CONFIDENCE_THRESHOLD):
LBPH devuelve una "distancia" (no una probabilidad): mientras MÁS BAJA,
más segura es la coincidencia. Valores típicos: <50 = buena coincidencia,
>80 = casi seguro que NO es la misma persona. Ajusta este umbral con tus
propias pruebas (sección de experimentación del anteproyecto).
"""
import argparse
import json
import cv2

from detector import detectar_rostros, recortar_rostro_gris

MODEL_PATH = "models/lbph_model.yml"
LABELS_PATH = "models/labels.json"
CONFIDENCE_THRESHOLD = 65


def load_model():
    recognizer = cv2.face.LBPHFaceRecognizer_create()
    recognizer.read(MODEL_PATH)
    with open(LABELS_PATH, encoding="utf-8") as f:
        label_map = json.load(f)
    return recognizer, label_map


def evaluate_frame(frame_bgr, recognizer, label_map):
    """Detecta rostros con DNN y los compara contra el modelo LBPH entrenado."""
    rostros = detectar_rostros(frame_bgr)
    results = []
    for caja in rostros:
        x, y, w, h, _confianza_deteccion = caja
        face_crop = recortar_rostro_gris(frame_bgr, caja)
        label_id, confidence = recognizer.predict(face_crop)
        autorizado = confidence < CONFIDENCE_THRESHOLD
        nombre = label_map.get(str(label_id), "desconocido")
        results.append({
            "bbox": (x, y, w, h),
            "nombre": nombre,
            "confianza": round(confidence, 1),
            "autorizado": autorizado,
        })
    return results


def run_webcam(camara=0):
    recognizer, label_map = load_model()
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")

    print("Verificando... presiona 'q' para salir.")
    while True:
        ok, frame = cap.read()
        if not ok:
            break
        for r in evaluate_frame(frame, recognizer, label_map):
            x, y, w, h = r["bbox"]
            color = (0, 200, 0) if r["autorizado"] else (0, 0, 255)
            texto = f"{r['nombre']} ({r['confianza']})" if r["autorizado"] else "NO AUTORIZADO"
            cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)
            cv2.putText(frame, texto, (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)
            if r["autorizado"]:
                print(f"[MATCH] {r['nombre']} — confianza {r['confianza']} -> notificar salón")
            else:
                print(f"[SIN MATCH] confianza {r['confianza']} -> alertar personal")
        cv2.imshow("Verificacion - pase inteligente", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break
    cap.release()
    cv2.destroyAllWindows()


def run_image(path):
    recognizer, label_map = load_model()
    img = cv2.imread(path)
    if img is None:
        raise SystemExit(f"No se pudo leer la imagen: {path}")
    results = evaluate_frame(img, recognizer, label_map)
    if not results:
        print("No se detectó ningún rostro en la imagen.")
    for r in results:
        estado = "AUTORIZADO" if r["autorizado"] else "NO AUTORIZADO"
        print(f"{estado} — {r['nombre']} — confianza {r['confianza']}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Verificación de tutor")
    parser.add_argument("--webcam", action="store_true")
    parser.add_argument("--imagen", help="Ruta a una imagen de prueba")
    parser.add_argument("--camara", type=int, default=0, help="Índice de cámara")
    args = parser.parse_args()

    if args.webcam:
        run_webcam(args.camara)
    elif args.imagen:
        run_image(args.imagen)
    else:
        parser.error("Debes indicar --webcam o --imagen")

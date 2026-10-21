"""
detectar_rostro.py — Detección de rostro en tiempo real (versión mejorada
con red neuronal — ver detector.py).

Uso:
    python detectar_rostro.py --webcam --camara 2
    python detectar_rostro.py --imagen ./foto.jpg
"""
import argparse
import cv2

from detector import detectar_rostros


def dibujar_rostros(frame, rostros):
    for (x, y, w, h, confianza) in rostros:
        cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
        cv2.putText(
            frame, f"{confianza * 100:.0f}%", (x, y - 8),
            cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2,
        )
    cv2.putText(
        frame, f"Rostros detectados: {len(rostros)}", (10, 30),
        cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2,
    )
    return frame


def run_webcam(camara=0):
    cap = cv2.VideoCapture(camara, cv2.CAP_DSHOW)
    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la cámara {camara}")

    print("Detectando rostros (DNN)... presiona 'q' para salir.")
    while True:
        ok, frame = cap.read()
        if not ok:
            break
        rostros = detectar_rostros(frame)
        frame = dibujar_rostros(frame, rostros)
        cv2.imshow("Deteccion de rostro (DNN) - pase inteligente", frame)
        if cv2.waitKey(1) & 0xFF == ord("q"):
            break
    cap.release()
    cv2.destroyAllWindows()


def run_imagen(path):
    img = cv2.imread(path)
    if img is None:
        raise SystemExit(f"No se pudo leer la imagen: {path}")
    rostros = detectar_rostros(img)
    img = dibujar_rostros(img, rostros)
    salida = "deteccion_resultado.jpg"
    cv2.imwrite(salida, img)
    print(f"{len(rostros)} rostro(s) detectado(s). Resultado guardado en {salida}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Detección de rostro (DNN)")
    parser.add_argument("--webcam", action="store_true", help="Usar la cámara en vivo")
    parser.add_argument("--imagen", help="Ruta a una imagen de prueba")
    parser.add_argument("--camara", type=int, default=0, help="Índice de cámara (0, 1, 2, ...)")
    args = parser.parse_args()

    if args.webcam:
        run_webcam(args.camara)
    elif args.imagen:
        run_imagen(args.imagen)
    else:
        parser.error("Debes indicar --webcam o --imagen")

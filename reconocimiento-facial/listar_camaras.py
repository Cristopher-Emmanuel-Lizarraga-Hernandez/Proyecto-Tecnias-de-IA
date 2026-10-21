"""
listar_camaras.py — Prueba índices de cámara del 0 al 5 y reporta cuáles abren.

Uso:
    python listar_camaras.py
"""
import cv2

print("Buscando cámaras disponibles...\n")
encontradas = []

for i in range(6):
    cap = cv2.VideoCapture(i, cv2.CAP_DSHOW)
    if cap.isOpened():
        ok, frame = cap.read()
        if ok and frame is not None:
            alto, ancho = frame.shape[:2]
            print(f"[OK] Índice {i} -> funciona ({ancho}x{alto})")
            encontradas.append(i)
        else:
            print(f"[--] Índice {i} -> abre pero no entrega imagen")
        cap.release()
    else:
        print(f"[--] Índice {i} -> no disponible")

print()
if encontradas:
    print(f"Cámaras utilizables: {encontradas}")
    print(f"Usa: python detectar_rostro.py --webcam --camara {encontradas[0]}")
else:
    print("No se detectó ninguna cámara. Revisa que DroidCam Client esté")
    print("corriendo y conectado (debe mostrar video ahí) antes de correr esto.")

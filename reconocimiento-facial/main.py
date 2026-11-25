"""Punto de recogida: vista de cámara y aviso confirmado por personal.
El aviso nunca autoriza una entrega. Ejecutar desde reconocimiento-facial.
"""
import argparse
import cv2
from database import Database
from notificar import avisar_llegada_confirmada


def confirmar_llegada(db):
    try:
        tutor_id = int(input('ID del tutor comprobado presencialmente (0 cancela): '))
    except ValueError:
        print('ID inválido.')
        return
    if tutor_id <= 0:
        return
    destino = db.obtener_destino(tutor_id)
    if not destino:
        print('Sin aula o profesor activo para este tutor.')
        return
    print(f"Tutor: {destino['tutor_nombre']} | Alumno: {destino['alumno_nombre']} | Aula: {destino['aula']}")
    if input('¿Comprobaste identidad y autorización de recogida presencialmente? [s/N]: ').strip().lower() != 's':
        print('Aviso cancelado.')
        return
    avisar_llegada_confirmada(db, tutor_id)


def punto_de_recogida(db, camara=0):
    cap = cv2.VideoCapture(camara, cv2.CAP_MSMF)
    if not cap.isOpened():
        cap.release()
        raise RuntimeError(f'No se pudo abrir la cámara {camara}')
    print('Presiona A en la ventana de cámara para avisar una llegada, Q para salir.')
    try:
        while True:
            ok, frame = cap.read()
            if not ok:
                print('No se recibió imagen de la cámara.')
                break
            cv2.putText(frame, 'A: aviso manual | Q: salir', (10, 30), cv2.FONT_HERSHEY_SIMPLEX, .65, (255,255,255), 2)
            cv2.imshow('Punto de recogida - aviso de llegada', frame)
            tecla = cv2.waitKey(1) & 0xFF
            if tecla in (ord('q'), ord('Q')):
                break
            if tecla in (ord('a'), ord('A')):
                try:
                    confirmar_llegada(db)
                except RuntimeError as exc:
                    print(f'Error de base de datos: {exc}')
    finally:
        cap.release()
        cv2.destroyAllWindows()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--verificar', action='store_true', help='Abre el punto de recogida con aviso manual')
    parser.add_argument('--camara', type=int, default=0)
    args = parser.parse_args()
    if not args.verificar:
        parser.error('Usa --verificar')
    punto_de_recogida(Database(), args.camara)

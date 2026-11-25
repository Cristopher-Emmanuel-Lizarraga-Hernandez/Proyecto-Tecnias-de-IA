"""
Punto de recogida - Pase Inteligente Escolar.

Detección facial mediante Haar Cascade.
Aviso de llegada confirmado por el personal escolar.
"""

import argparse
import time
import cv2

from database import Database
from notificar import avisar_llegada_confirmada


def confirmar_llegada(db):

    try:
        tutor_id = int(input(
            'ID del tutor comprobado presencialmente (0 cancela): '
        ))

    except ValueError:
        print('ID inválido.')
        return

    if tutor_id <= 0:
        return

    destino = db.obtener_destino(tutor_id)

    if not destino:
        print('Sin aula o profesor activo para este tutor.')
        return

    print(
        f"Tutor: {destino['tutor_nombre']} | "
        f"Alumno: {destino['alumno_nombre']} | "
        f"Aula: {destino['aula']}"
    )

    confirmacion = input(
        '¿Comprobaste identidad y autorización de recogida '
        'presencialmente? [s/N]: '
    ).strip().lower()

    if confirmacion != 's':
        print('Aviso cancelado.')
        return

    avisar_llegada_confirmada(db, tutor_id)


def punto_de_recogida(db, camara=0, detectar=False):

    detector = None

    # Configuración del detector facial ligero
    if detectar:

        cv2.setNumThreads(2)
        cv2.ocl.setUseOpenCL(False)

        detector = cv2.CascadeClassifier(
            cv2.data.haarcascades +
            "haarcascade_frontalface_default.xml"
        )

        if detector.empty():
            raise RuntimeError(
                "No se pudo cargar Haar Cascade"
            )

    ultimo = -1.0
    cantidad = 0
    rostros = []
    escala_x = 1.0
    escala_y = 1.0

    # Configuración compatible con DroidCam
    cap = cv2.VideoCapture(camara, cv2.CAP_MSMF)

    if not cap.isOpened():
        cap.release()

        raise RuntimeError(
            f'No se pudo abrir la cámara {camara}'
        )

    print('Presiona A para avisar una llegada.')
    print('Presiona Q para salir.')

    try:

        while True:

            ok, frame = cap.read()

            if not ok:
                print('No se recibió imagen de la cámara.')
                break

            # Detección facial
            if detector is not None:

                ahora = time.monotonic()

                # Máximo 5 detecciones por segundo
                if ahora - ultimo >= 0.2:

                    ultimo = ahora

                    alto, ancho = frame.shape[:2]

                    escala = min(
                        1.0,
                        320 / ancho,
                        240 / alto
                    )

                    ancho_reducido = max(
                        1, int(ancho * escala)
                    )

                    alto_reducido = max(
                        1, int(alto * escala)
                    )

                    reducido = cv2.resize(
                        frame,
                        (ancho_reducido, alto_reducido)
                    )

                    escala_x = ancho_reducido / ancho
                    escala_y = alto_reducido / alto

                    gris = cv2.cvtColor(
                        reducido,
                        cv2.COLOR_BGR2GRAY
                    )

                    rostros = detector.detectMultiScale(
                        gris,
                        scaleFactor=1.1,
                        minNeighbors=5,
                        minSize=(30, 30)
                    )

                    cantidad = len(rostros)

                # Dibujar recuadros verdes
                for (x, y, w, h) in rostros:

                    x1 = int(x / escala_x)
                    y1 = int(y / escala_y)

                    x2 = int((x + w) / escala_x)
                    y2 = int((y + h) / escala_y)

                    cv2.rectangle(
                        frame,
                        (x1, y1),
                        (x2, y2),
                        (0, 255, 0),
                        2
                    )

                    cv2.putText(
                        frame,
                        'Rostro detectado',
                        (x1, max(20, y1 - 10)),
                        cv2.FONT_HERSHEY_SIMPLEX,
                        0.5,
                        (0, 255, 0),
                        2
                    )

                # Contador de rostros
                cv2.putText(
                    frame,
                    f'Rostros detectados: {cantidad}',
                    (10, 58),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.60,
                    (0, 200, 255),
                    2
                )

            # Controles del programa
            cv2.putText(
                frame,
                'A: aviso manual | Q: salir',
                (10, 30),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.65,
                (255, 255, 255),
                2
            )

            cv2.imshow(
                'Punto de recogida - aviso de llegada',
                frame
            )

            tecla = cv2.waitKey(1) & 0xFF

            # Cerrar programa
            if tecla in (ord('q'), ord('Q')):
                break

            # Registrar aviso de llegada
            if tecla in (ord('a'), ord('A')):

                try:
                    confirmar_llegada(db)

                except RuntimeError as exc:
                    print(f'Error: {exc}')

    finally:

        cap.release()
        cv2.destroyAllWindows()


if __name__ == '__main__':

    parser = argparse.ArgumentParser()

    parser.add_argument(
        '--verificar',
        action='store_true',
        help='Abre el punto de recogida'
    )

    parser.add_argument(
        '--camara',
        type=int,
        default=0
    )

    parser.add_argument(
        '--detectar',
        action='store_true',
        help='Activa la detección facial Haar Cascade'
    )

    args = parser.parse_args()

    if not args.verificar:
        parser.error('Usa --verificar')

    punto_de_recogida(
        Database(),
        args.camara,
        args.detectar
    )
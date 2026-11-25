"""Prueba de integración SMTP/MySQL con un tutor comprobado por personal.
No reconoce rostros ni autoriza entregas. Ejecutar solo con datos de prueba.
"""
import argparse
import os
from database import Database
from correo import enviar_correo


def avisar_llegada(db, tutor_id):
    destino = db.obtener_destino(tutor_id)
    if not destino:
        print("Sin aula o profesor activo para este tutor.")
        return False
    print(f"Alumno: {destino['alumno_nombre']} | Aula: {destino['aula']}")
    if input("¿Verificaste presencialmente la identidad y autorización del tutor? [s/N]: ").strip().lower() != 's':
        print("Aviso cancelado.")
        return False
    if not os.getenv('SMTP_USER') or not os.getenv('SMTP_PASSWORD'):
        print("Configura SMTP_USER y SMTP_PASSWORD en esta terminal.")
        return False
    asunto = 'Aviso de llegada - Pase Inteligente Escolar'
    mensaje = (f"Ha llegado un tutor para recoger al alumno {destino['alumno_nombre']}, "
               f"del aula {destino['aula']}.\n\n"
               "La entrega requiere la comprobación final del personal escolar.")
    aviso_id = db.crear_aviso(destino, None, asunto, mensaje)
    try:
        enviar_correo(destino['correo'], asunto, mensaje)
    except Exception as exc:
        db.finalizar_aviso(aviso_id, False, type(exc).__name__)
        print(f"No se confirmó el envío: {type(exc).__name__}. Aviso {aviso_id}.")
        return False
    db.finalizar_aviso(aviso_id, True)
    print(f"Aviso {aviso_id} registrado como enviado.")
    return True


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--tutor-id', type=int, required=True)
    args = parser.parse_args()
    raise SystemExit(0 if avisar_llegada(Database(), args.tutor_id) else 1)

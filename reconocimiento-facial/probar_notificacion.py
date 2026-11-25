"""Prueba manual con los mismos controles que el punto de recogida."""
import argparse
from database import Database
from notificar import avisar_llegada_confirmada

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--tutor-id', type=int, required=True)
    args = parser.parse_args()
    db = Database()
    destino = db.obtener_destino(args.tutor_id)
    if not destino:
        raise SystemExit('Sin aula o profesor activo para este tutor.')
    print(f"Tutor: {destino['tutor_nombre']} | Alumno: {destino['alumno_nombre']} | Aula: {destino['aula']}")
    if input('¿Comprobaste identidad y autorización presencialmente? [s/N]: ').strip().lower() != 's':
        raise SystemExit('Aviso cancelado.')
    raise SystemExit(0 if avisar_llegada_confirmada(db, args.tutor_id) else 1)

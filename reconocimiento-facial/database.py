"""
database.py — Capa de acceso a datos (MySQL / phpMyAdmin) del sistema de
pase inteligente escolar.

Requiere que ya hayas importado schema.sql en phpMyAdmin (crea la base
'pase_inteligente_escolar' con sus tablas).

Configura tus credenciales en las variables DB_* de abajo, o mejor, en
variables de entorno (más seguro, evita subir contraseñas a GitHub).

Uso típico:
    from database import Database
    db = Database()
    aid = db.registrar_alumno("Ana Lopez", "3A")
    tid = db.registrar_tutor("Juan Perez", alumno_id=aid, embedding_label=0)
    db.registrar_recogida(tutor_id=tid, alumno_id=aid, confianza=42.3, autorizado=True)
"""
import os
import mysql.connector
from mysql.connector import Error
from contextlib import contextmanager

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_USER = os.getenv("DB_USER", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")  # en XAMPP/phpMyAdmin local suele ser vacío
DB_NAME = os.getenv("DB_NAME", "pase_inteligente_escolar")


class Database:
    def __init__(self):
        self.config = dict(host=DB_HOST, user=DB_USER, password=DB_PASSWORD, database=DB_NAME)
        # Verifica la conexión al iniciar, para fallar rápido con un mensaje claro
        with self._conn():
            pass

    @contextmanager
    def _conn(self):
        conn = None
        try:
            conn = mysql.connector.connect(**self.config)
            yield conn
            conn.commit()
        except Error as e:
            raise RuntimeError(
                f"No se pudo conectar a MySQL ({DB_HOST}/{DB_NAME}). "
                f"Verifica que XAMPP/MySQL esté corriendo y que hayas importado schema.sql. "
                f"Detalle: {e}"
            )
        finally:
            if conn is not None and conn.is_connected():
                conn.close()

    # --- Alumnos ---
    def registrar_alumno(self, nombre, grupo=None):
        with self._conn() as conn:
            cur = conn.cursor()
            cur.execute("INSERT INTO alumnos (nombre, grupo) VALUES (%s, %s)", (nombre, grupo))
            return cur.lastrowid

    def listar_alumnos(self):
        with self._conn() as conn:
            cur = conn.cursor(dictionary=True)
            cur.execute("SELECT * FROM alumnos")
            return cur.fetchall()

    # --- Tutores ---
    def registrar_tutor(self, nombre, alumno_id, embedding_label, parentesco="tutor"):
        with self._conn() as conn:
            cur = conn.cursor()
            cur.execute(
                "INSERT INTO tutores (nombre, alumno_id, embedding_label, parentesco) "
                "VALUES (%s, %s, %s, %s)",
                (nombre, alumno_id, embedding_label, parentesco),
            )
            return cur.lastrowid

    def buscar_tutor_por_label(self, embedding_label):
        with self._conn() as conn:
            cur = conn.cursor(dictionary=True)
            cur.execute(
                "SELECT t.*, a.nombre AS alumno_nombre "
                "FROM tutores t JOIN alumnos a ON t.alumno_id = a.id "
                "WHERE t.embedding_label = %s",
                (embedding_label,),
            )
            return cur.fetchone()

    def siguiente_embedding_label(self):
        """Consulta el siguiente label disponible directamente en la BD,
        en vez de contar carpetas en dataset/ (evita choques si la BD y el
        dataset local se desincronizan)."""
        with self._conn() as conn:
            cur = conn.cursor()
            cur.execute("SELECT COALESCE(MAX(embedding_label), -1) + 1 FROM tutores")
            return cur.fetchone()[0]

    # --- Bitácora ---
    def registrar_recogida(self, tutor_id, alumno_id, confianza, autorizado):
        with self._conn() as conn:
            cur = conn.cursor()
            cur.execute(
                "INSERT INTO bitacora_recogida (tutor_id, alumno_id, confianza, autorizado) "
                "VALUES (%s, %s, %s, %s)",
                (tutor_id, alumno_id, confianza, int(autorizado)),
            )

    def historial(self, limite=20):
        with self._conn() as conn:
            cur = conn.cursor(dictionary=True)
            cur.execute(
                """SELECT b.fecha_hora, t.nombre AS tutor, a.nombre AS alumno,
                          b.confianza, b.autorizado
                   FROM bitacora_recogida b
                   LEFT JOIN tutores t ON b.tutor_id = t.id
                   LEFT JOIN alumnos a ON b.alumno_id = a.id
                   ORDER BY b.fecha_hora DESC LIMIT %s""",
                (limite,),
            )
            return cur.fetchall()


if __name__ == "__main__":
    db = Database()
    print("Conexión exitosa. Alumnos registrados:", db.listar_alumnos())
    print("Historial:", db.historial())
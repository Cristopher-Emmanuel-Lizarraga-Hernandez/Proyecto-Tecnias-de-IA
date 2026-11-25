"""
notificar.py — Módulo de notificación.

En esta etapa de prototipo, las notificaciones se simulan por consola y en un
archivo de log (notificaciones.log), representando lo que en producción sería:
  - Una señal enviada a una pantalla/tablet en el salón del alumno.
  - Un mensaje por WhatsApp/correo al padre confirmando el registro.

Diseñado para que, en el proyecto final, solo se reemplace la función
`enviar()` por una llamada real a la API correspondiente, sin tocar el resto
del sistema (patrón de bajo acoplamiento).
"""
from datetime import datetime
import json

LOG_PATH = "notificaciones.log"
ESTADO_PATH = "estado_pantalla.json"


def enviar(destino, mensaje):
    """Punto único de salida de notificaciones. Sustituir aquí por la
    integración real (API de WhatsApp, websocket a la pantalla del salón, etc.)
    """
    linea = f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] -> {destino}: {mensaje}"
    print(linea)
    with open(LOG_PATH, "a", encoding="utf-8") as f:
        f.write(linea + "\n")


def _actualizar_estado(tipo, tutor=None, alumno=None, confianza=None):
    """Escribe el estado actual para que pantalla.py lo lea y lo muestre."""
    estado = {
        "tipo": tipo,  # "autorizado" | "no_autorizado"
        "tutor": tutor,
        "alumno": alumno,
        "confianza": confianza,
        "timestamp": datetime.now().isoformat(timespec="seconds"),
    }
    with open(ESTADO_PATH, "w", encoding="utf-8") as f:
        json.dump(estado, f, ensure_ascii=False)


def notificar_llegada_autorizada(nombre_tutor, nombre_alumno, confianza):
    enviar(
        destino=f"pantalla_salon_{nombre_alumno}",
        mensaje=f"{nombre_tutor} ha llegado por {nombre_alumno} (confianza: {confianza})",
    )
    enviar(
        destino="padres",
        mensaje=f"Confirmado: {nombre_tutor} registrado en la recogida de {nombre_alumno}",
    )
    _actualizar_estado("autorizado", tutor=nombre_tutor, alumno=nombre_alumno, confianza=confianza)


def notificar_intento_no_autorizado(confianza):
    enviar(
        destino="personal_seguridad",
        mensaje=f"ALERTA: persona no reconocida intentó recoger a un alumno (confianza: {confianza})",
    )
    _actualizar_estado("no_autorizado", confianza=confianza)


def avisar_llegada_confirmada(db, tutor_id):
    """Solo llamar después de la comprobación presencial por personal escolar."""
    import os
    from correo import enviar_correo
    if not os.getenv('SMTP_USER') or not os.getenv('SMTP_PASSWORD'):
        print('Configura SMTP_USER y SMTP_PASSWORD antes de enviar.')
        return False
    aviso, error = db.crear_aviso_controlado(tutor_id)
    if not aviso:
        print(error)
        return False
    aviso_id, correo, asunto, mensaje = aviso
    try:
        enviar_correo(correo, asunto, mensaje)
    except Exception as exc:
        db.finalizar_aviso(aviso_id, False, type(exc).__name__)
        print(f'No se confirmó el envío: {type(exc).__name__}. Aviso {aviso_id}.')
        return False
    db.finalizar_aviso(aviso_id, True)
    print(f'Aviso {aviso_id} registrado como enviado.')
    return True

import os
import ssl
import smtplib
from email.message import EmailMessage


def enviar_correo(destinatario, asunto, contenido):

    correo_emisor = os.environ["SMTP_USER"]
    contrasena = os.environ["SMTP_PASSWORD"]

    mensaje = EmailMessage()
    mensaje["From"] = correo_emisor
    mensaje["To"] = destinatario
    mensaje["Subject"] = asunto

    mensaje.set_content(contenido)

    contexto = ssl.create_default_context()

    with smtplib.SMTP(
        "smtp.gmail.com", 587, timeout=15
    ) as servidor:

        servidor.starttls(context=contexto)
        servidor.login(correo_emisor, contrasena)
        servidor.send_message(mensaje)

    print("Correo enviado correctamente.")


if __name__ == "__main__":

    enviar_correo(
        os.environ["SMTP_USER"],
        "Prueba - Pase Inteligente Escolar",
        "El sistema de notificaciones funciona correctamente."
    )
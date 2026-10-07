# Proyecto-Tecnias-de-IA
## Se reemplaza el detector Haar Cascade por un modelo DNN (SSD sobre
ResNet-10, preentrenado por OpenCV), que mejora notablemente la
detección en ángulos, poca luz y rostros parcialmente cubiertos.

Se crea detector.py como módulo compartido, usado ahora por
detectar_rostro.py, enroll.py, verify.py, main.py y registrar.py,
evitando duplicar la lógica de detección en cada script.

## corrige choque de embedding_label al registrar tutores en MySQL

registrar.py calculaba el siguiente embedding_label contando carpetas
locales en dataset/, lo que provocaba un error de llave duplicada en
MySQL cuando ese número ya existía en la base de datos (por ejemplo,
con el registro de ejemplo de schema.sql).

Se agrega Database.siguiente_embedding_label(), que consulta
directamente el máximo label en la tabla tutores, manteniendo
sincronizados el dataset local y la base de datos.

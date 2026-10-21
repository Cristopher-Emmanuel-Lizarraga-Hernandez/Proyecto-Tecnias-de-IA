# Sistema de Autenticación Biométrica Facial para la Gestión Segura de Recogida de Menores en Instituciones Educativas

## Planteamiento del problema

La recogida de menores depende del reconocimiento visual del personal, propenso a errores y sin garantizar que el niño sea entregado a alguien autorizado. Tampoco existe forma de que el alumno sepa cuándo llegó su tutor.

## Justificación

Se propone un sistema de reconocimiento facial que verifique automáticamente al tutor y notifique en tiempo real, reduciendo el error humano y dejando un registro auditable de cada entrega.

## Hipótesis

Si se implementa un sistema de pase inteligente escolar basado en reconocimiento facial que verifique automáticamente la identidad de los tutores autorizados y notifique en tiempo real su llegada, entonces se reducirá el riesgo de entrega de menores a personas no autorizadas y disminuirá la incertidumbre y el tiempo de espera de los alumnos durante el proceso de recogida escolar.

## Objetivo general

Desarrollar un sistema de pase inteligente escolar basado en reconocimiento facial que verifique automáticamente la identidad de los tutores autorizados para la recogida de menores y notifique en tiempo real al personal escolar y al alumno correspondiente.

## Objetivos específicos

- Diseñar una base de datos que almacene de forma segura los datos de alumnos y tutores autorizados.
- Implementar un módulo de captura y reconocimiento facial capaz de identificar al tutor en el punto de recogida.
- Desarrollar un mecanismo de notificación en tiempo real hacia el salón/personal y, opcionalmente, hacia los padres.
- Generar un registro (bitácora) auditable de cada entrega, con fecha, hora y tutor identificado.
- Establecer un protocolo de respaldo (validación manual o PIN) para los casos en que el reconocimiento facial no logre identificar al tutor.

## Metodología

Se seguirá una metodología ágil (Scrum), en etapas: análisis de requerimientos, diseño de la arquitectura y base de datos, desarrollo del backend y del módulo de reconocimiento facial, pruebas de precisión, y documentación final.

## Herramientas y tecnologías

- Backend: PHP (MVC) + MySQL
- Reconocimiento facial: OpenCV (detección DNN + clasificación LBPH)
- Captura: cámara web / DroidCam / ESP32-CAM
- Notificaciones: consulta a base de datos en tiempo real, con opción de WhatsApp/correo
- Control de versiones: Git y GitHub

## Experimentación

Se realizarán pruebas controladas del módulo de reconocimiento facial con un grupo de voluntarios que simularán tutores autorizados, evaluando el desempeño bajo distintas condiciones (iluminación, ángulos, uso de lentes o cubrebocas). Se medirá la tasa de reconocimiento correcto, falsos positivos, falsos negativos y el tiempo de respuesta desde la captura hasta la notificación, con el fin de validar la viabilidad del sistema para uso real.

## Conclusión

Se espera que el sistema reduzca el riesgo de entrega de menores a personas no autorizadas, sustituyendo el criterio subjetivo del personal por un mecanismo tecnológico objetivo y auditable. De confirmarse la hipótesis, se demostrará que es posible implementar reconocimiento facial accesible y de bajo costo en el contexto escolar, brindando seguridad y tranquilidad tanto a alumnos como a padres de familia.

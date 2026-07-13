# GestionPlataformaPisos
Sincronizador Inmobiliario Multiplataforma
El objetivo de este plugin es convertir el WordPress del cliente en el Single Source of Truth (Fuente única de verdad). El agente inmobiliario sube el inmueble una sola vez a su web y este se propaga automáticamente al resto de portales, reteniendo el 100% del valor SEO y la audiencia en la web local del cliente.

1. Arquitectura Técnica y Core en WordPress
Estructura de Datos: Creación de un CPT (Custom Post Type) llamado inmuebles enriquecido con campos personalizados avanzados (ACF Pro o nativos) para almacenar datos críticos normalizados: metros cuadrados, habitaciones, certificación energética, geolocalización precisa y galerías de imágenes optimizadas.

Motor de Sincronización: Basado en el Action Scheduler de WordPress o WP-Cron para detectar de forma asíncrona cualquier evento en el CPT (creación, edición, borrado o cambio a estado "vendido") y encolar las acciones de envío.

Capa de Abstracción de APIs: Un módulo "traductor" independiente para cada plataforma, encargado de parsear y formatear los datos según las especificaciones de cada portal (JSON o XML).

2. Flujo de Trabajo y Reglas de Negocio
Mapeo Universal de Atributos (Backend): Una pantalla de configuración donde el usuario asocia los campos de su WordPress con las etiquetas exigidas por los portales (ej: mapear el campo local habitaciones con el campo rooms o el ID numérico correspondiente de Idealista).

Control de Publicación Individual: Interruptores integrados en la barra lateral de edición del inmueble para decidir dinámicamente a qué portales enviar la información (ej: "Publicar en Idealista" pero "Excluir de Fotocasa").

El Ciclo de Vida Automático:

Alta/Modificación: Envío de datos e imágenes optimizadas (redimensionadas automáticamente para cumplir con las restricciones de peso de los portales).

Baja/Venta: Si un inmueble pasa a "Borrador" o "Vendido" en WP, el plugin envía de inmediato una orden de retirada al portal para evitar llamadas falsas y penalizaciones.

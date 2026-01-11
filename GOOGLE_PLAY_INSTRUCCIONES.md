# 📱 INGClean - Guía para subir a Google Play Store

## ✅ Archivos que necesitas subir a tu servidor PRIMERO:

```
public_html/
├── manifest.json          ← NUEVO
├── service-worker.js      ← NUEVO
├── .well-known/
│   └── assetlinks.json    ← NUEVO (actualizar después)
├── icons/
│   ├── icon-72x72.png     ← GENERAR
│   ├── icon-96x96.png     ← GENERAR
│   ├── icon-128x128.png   ← GENERAR
│   ├── icon-144x144.png   ← GENERAR
│   ├── icon-152x152.png   ← GENERAR
│   ├── icon-192x192.png   ← GENERAR
│   ├── icon-384x384.png   ← GENERAR
│   └── icon-512x512.png   ← GENERAR
└── includes/
    └── pwa-head.php       ← NUEVO
```

---

## 📋 PASO 1: Generar los íconos

### Opción A: Usar generador online (RECOMENDADO)

1. Ve a: https://www.pwabuilder.com/imageGenerator
2. Sube el archivo `icons/icon-base.svg` que te di
3. Descarga el ZIP con todos los tamaños
4. Sube los PNGs a la carpeta `/icons/` en tu servidor

### Opción B: Usar Canva o Photoshop

Crea un ícono de 512x512 px y redimensiona a:
- 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512

---

## 📋 PASO 2: Subir archivos al servidor

1. Sube `manifest.json` a la RAÍZ de tu sitio (public_html/)
2. Sube `service-worker.js` a la RAÍZ de tu sitio
3. Sube la carpeta `icons/` con todos los PNGs
4. Sube la carpeta `.well-known/` (la actualizaremos después)

---

## 📋 PASO 3: Agregar PWA a las páginas

Agrega esta línea en el <head> de tus páginas principales:

```php
<?php include 'includes/pwa-head.php'; ?>
```

O copia el contenido de pwa-head.php y pégalo en el <head>.

---

## 📋 PASO 4: Verificar que la PWA funciona

1. Ve a: https://demogeo.expressatech.net
2. Abre DevTools (F12) → Application → Manifest
3. Debe mostrar la info de tu app
4. En "Service Workers" debe aparecer registrado

---

## 📋 PASO 5: Generar el APK con PWA Builder

1. Ve a: https://www.pwabuilder.com
2. Ingresa tu URL: https://demogeo.expressatech.net
3. Click en "Start"
4. Espera el análisis
5. Click en "Package for stores"
6. Selecciona "Android"
7. Configura:
   - Package ID: com.ingclean.app
   - App name: INGClean
   - Version: 1.0.0
8. Click en "Generate"
9. Descarga el archivo .aab

---

## 📋 PASO 6: Actualizar assetlinks.json

PWA Builder te dará un SHA256 fingerprint. 

1. Abre el archivo `.well-known/assetlinks.json`
2. Reemplaza "AQUI_VA_EL_SHA256_DEL_CERTIFICADO" con el valor real
3. Sube el archivo actualizado al servidor

---

## 📋 PASO 7: Subir a Google Play Console

1. Ve a: https://play.google.com/console
2. Inicia sesión con la cuenta de tu socio
3. Click en "Crear app"
4. Llena la información:
   - Nombre: INGClean
   - Idioma: Español
   - Tipo: App
   - Gratis
5. Acepta las políticas

---

## 📋 PASO 8: Configurar la ficha de Play Store

### Información básica:
- **Nombre:** INGClean - Servicios de Limpieza
- **Descripción corta:** Conectamos clientes con profesionales de limpieza
- **Descripción completa:** 
  INGClean es la plataforma que conecta a clientes con profesionales de limpieza certificados. 
  
  ✅ Solicita servicios de limpieza en minutos
  ✅ Tracking en tiempo real de tu profesional
  ✅ Pagos seguros con Stripe
  ✅ Profesionales verificados

### Gráficos requeridos:
- Ícono: 512x512 (ya lo tienes)
- Feature graphic: 1024x500
- Screenshots: Mínimo 2 de teléfono

---

## 📋 PASO 9: Subir el AAB

1. Ve a "Producción" → "Crear nueva versión"
2. Sube el archivo .aab que descargaste de PWA Builder
3. Agrega notas de la versión: "Versión inicial"
4. Click en "Revisar versión"
5. Click en "Iniciar lanzamiento a producción"

---

## 📋 PASO 10: Enviar a revisión

1. Completa todas las secciones requeridas (tienen ✓)
2. Click en "Enviar para revisión"
3. Espera 1-3 días para aprobación

---

## ⏱️ Tiempo total estimado:

| Paso | Tiempo |
|------|--------|
| Generar íconos | 15 min |
| Subir archivos | 10 min |
| PWA Builder | 15 min |
| Google Play Console | 30 min |
| Revisión de Google | 1-3 días |

---

## 🆘 Si tienes problemas:

1. **PWA Builder dice que falta algo:** Verifica que manifest.json esté accesible en https://demogeo.expressatech.net/manifest.json

2. **No aparece el Service Worker:** Verifica que service-worker.js esté en la raíz

3. **Google rechaza la app:** Lee el motivo y corrígelo

---

## 📞 Soporte

Si necesitas ayuda, escríbeme y te guío paso a paso.

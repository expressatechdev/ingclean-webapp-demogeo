/**
 * INGClean - Capacitor GPS Bridge
 * Este archivo permite usar GPS nativo cuando la app corre en Capacitor
 * y GPS web cuando corre en el navegador
 */

// Detectar si estamos en Capacitor
const isCapacitor = typeof Capacitor !== 'undefined' && Capacitor.isNativePlatform();

// Variables globales para GPS
let nativeWatchId = null;
let foregroundServiceRunning = false;

/**
 * Inicializar GPS (nativo o web)
 */
async function initGPS(onSuccess, onError) {
    if (isCapacitor) {
        console.log('📱 Usando GPS NATIVO (Capacitor)');
        await initNativeGPS(onSuccess, onError);
    } else {
        console.log('🌐 Usando GPS WEB (navegador)');
        initWebGPS(onSuccess, onError);
    }
}

/**
 * GPS Nativo con Capacitor (funciona en segundo plano)
 */
async function initNativeGPS(onSuccess, onError) {
    try {
        const { Geolocation } = Capacitor.Plugins;
        const { ForegroundService } = Capacitor.Plugins;
        
        // Solicitar permisos
        const permission = await Geolocation.requestPermissions();
        console.log('Permiso GPS:', permission);
        
        if (permission.location !== 'granted') {
            onError({ code: 1, message: 'Permiso de ubicación denegado' });
            return;
        }
        
        // Iniciar servicio en primer plano (permite GPS en segundo plano)
        await startForegroundService();
        
        // Obtener ubicación actual primero
        const position = await Geolocation.getCurrentPosition({
            enableHighAccuracy: true,
            timeout: 30000
        });
        
        console.log('✅ Ubicación inicial:', position.coords);
        onSuccess({
            coords: {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            }
        });
        
        // Iniciar seguimiento continuo
        nativeWatchId = await Geolocation.watchPosition(
            {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 5000
            },
            (position, err) => {
                if (err) {
                    console.log('Error watch nativo:', err);
                    return;
                }
                
                if (position) {
                    console.log('📍 Ubicación actualizada:', position.coords.latitude, position.coords.longitude);
                    onSuccess({
                        coords: {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        }
                    });
                }
            }
        );
        
        console.log('🔄 Watch GPS nativo iniciado, ID:', nativeWatchId);
        
    } catch (error) {
        console.error('Error GPS nativo:', error);
        onError({ code: 2, message: error.message || 'Error obteniendo ubicación' });
    }
}

/**
 * Iniciar servicio en primer plano para GPS en segundo plano
 */
async function startForegroundService() {
    if (foregroundServiceRunning) return;
    
    try {
        const { ForegroundService } = Capacitor.Plugins;
        
        if (ForegroundService) {
            await ForegroundService.startForegroundService({
                id: 1001,
                title: 'INGClean - Servicio Activo',
                body: 'Rastreando ubicación para el cliente',
                icon: 'ic_launcher',
                importance: 3, // IMPORTANCE_DEFAULT
                vibration: false,
                serviceType: 'location'
            });
            
            foregroundServiceRunning = true;
            console.log('✅ Servicio en primer plano iniciado');
        }
    } catch (error) {
        console.log('⚠️ No se pudo iniciar servicio en primer plano:', error);
        // Continuar sin servicio - el GPS normal seguirá funcionando
    }
}

/**
 * Detener servicio en primer plano
 */
async function stopForegroundService() {
    if (!foregroundServiceRunning) return;
    
    try {
        const { ForegroundService } = Capacitor.Plugins;
        
        if (ForegroundService) {
            await ForegroundService.stopForegroundService();
            foregroundServiceRunning = false;
            console.log('✅ Servicio en primer plano detenido');
        }
    } catch (error) {
        console.log('Error deteniendo servicio:', error);
    }
}

/**
 * GPS Web tradicional (fallback)
 */
function initWebGPS(onSuccess, onError) {
    if (!navigator.geolocation) {
        onError({ code: 2, message: 'GPS no soportado en este navegador' });
        return;
    }
    
    const options = {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 5000
    };
    
    navigator.geolocation.getCurrentPosition(onSuccess, onError, options);
}

/**
 * Iniciar seguimiento continuo (web)
 */
function startWebWatchPosition(onSuccess, onError) {
    if (!navigator.geolocation) return null;
    
    return navigator.geolocation.watchPosition(
        onSuccess,
        onError,
        {
            enableHighAccuracy: true,
            timeout: 30000,
            maximumAge: 5000
        }
    );
}

/**
 * Detener seguimiento GPS
 */
async function stopGPSTracking(watchId) {
    if (isCapacitor) {
        try {
            const { Geolocation } = Capacitor.Plugins;
            
            if (nativeWatchId) {
                await Geolocation.clearWatch({ id: nativeWatchId });
                nativeWatchId = null;
            }
            
            await stopForegroundService();
            console.log('✅ GPS nativo detenido');
        } catch (error) {
            console.log('Error deteniendo GPS nativo:', error);
        }
    } else {
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            console.log('✅ GPS web detenido');
        }
    }
}

/**
 * Verificar si GPS está activo
 */
function isGPSActive() {
    if (isCapacitor) {
        return nativeWatchId !== null;
    }
    return false;
}

/**
 * Obtener estado del servicio en segundo plano
 */
function isBackgroundServiceRunning() {
    return foregroundServiceRunning;
}

// Exportar funciones para uso global
window.INGCleanGPS = {
    isCapacitor,
    init: initGPS,
    stop: stopGPSTracking,
    isActive: isGPSActive,
    isBackgroundRunning: isBackgroundServiceRunning,
    startForegroundService,
    stopForegroundService
};

console.log('🚀 INGClean GPS Bridge cargado. Capacitor:', isCapacitor);
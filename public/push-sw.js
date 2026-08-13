// Escuchar el evento push del servidor
self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (e.data) {
        var msg = e.data.json();
        
        e.waitUntil(
            self.registration.showNotification(msg.title, {
                body: msg.body,
                icon: msg.icon || '/icon-192x192.png',
                actions: msg.actions || [],
                data: msg.data || {},
                vibrate: msg.options?.vibrate || [200, 100, 200, 100, 200, 100, 200],
                requireInteraction: true
            }).then(() => {
                // Intentar reproducir sonido de alarma si es posible en el SW
                // Nota: la mayoría de navegadores móviles limitan el AudioContext en background, 
                // pero la vibración y la notificación nativa aseguran que el usuario se entere.
                return playAlarmSound();
            }).catch(err => console.log('Error showing notification', err))
        );
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    // Clicked on the notification
    event.waitUntil(
        clients.matchAll({
            type: "window"
        })
        .then(function(clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url == '/' && 'focus' in client)
                    return client.focus();
            }
            if (clients.openWindow) {
                return clients.openWindow('/');
            }
        })
    );
});

async function playAlarmSound() {
    // Si bien no siempre es soportado, intentamos usar cache o fetch para reproducir el audio.
    // Usualmente no se requiere porque el OS hará un sonido nativo para la notificación push.
}

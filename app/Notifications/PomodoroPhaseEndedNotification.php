<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PomodoroPhaseEndedNotification extends Notification
{
    use Queueable;

    public $phase;

    /**
     * Create a new notification instance.
     *
     * @param string $phase 'focus', 'short_break', or 'long_break'
     */
    public function __construct(string $phase)
    {
        $this->phase = $phase;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return \Illuminate\Notifications\Messages\WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        $title = '¡Tiempo terminado!';
        $body = 'Revisa tu aplicación.';

        if ($this->phase === 'focus') {
            $title = '¡Pomodoro completado! 🍅';
            $body = 'Es hora de tomar un descanso. ¡Buen trabajo!';
        } elseif ($this->phase === 'short_break' || $this->phase === 'long_break') {
            $title = '¡Break terminado! ⚡';
            $body = 'Es hora de volver a enfocarte. ¡Tú puedes!';
        }

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icon-192x192.png')
            ->body($body)
            ->action('Abrir SecondBrain', 'open_app')
            ->options([
                'vibrate' => [200, 100, 200, 100, 200, 100, 200],
            ]);
    }
}

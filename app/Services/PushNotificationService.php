<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Enviar notificación a un dispositivo
     */
    public function enviarADispositivo($token, $titulo, $mensaje, $data = [])
    {
        return $this->enviar([
            'to' => $token,
            'notification' => [
                'title' => $titulo,
                'body' => $mensaje,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Enviar notificación a múltiples dispositivos
     */
    public function enviarAMultiples($tokens, $titulo, $mensaje, $data = [])
    {
        return $this->enviar([
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $titulo,
                'body' => $mensaje,
                'sound' => 'default',
            ],
            'data' => $data,
        ]);
    }

    /**
     * Enviar notificación a un tema
     */
    public function enviarATema($tema, $titulo, $mensaje, $data = [])
    {
        return $this->enviar([
            'to' => '/topics/' . $tema,
            'notification' => [
                'title' => $titulo,
                'body' => $mensaje,
                'sound' => 'default',
            ],
            'data' => $data,
        ]);
    }

    protected function enviar($payload)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            Log::info('FCM Response', ['response' => $response->json()]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Notificaciones predefinidas
     */
    public function notificarNuevoPedido($restaurante, $pedido)
    {
        // Notificar al restaurante
        if ($restaurante->fcm_token) {
            $this->enviarADispositivo(
                $restaurante->fcm_token,
                '¡Nuevo Pedido!',
                "Pedido {$pedido->codigo} - RD$ " . number_format($pedido->total, 0),
                ['pedido_id' => $pedido->id, 'tipo' => 'nuevo_pedido']
            );
        }
    }

    public function notificarPedidoListo($repartidores)
    {
        // Notificar a repartidores disponibles
        $tokens = $repartidores->pluck('fcm_token')->filter()->toArray();

        if (!empty($tokens)) {
            $this->enviarAMultiples(
                $tokens,
                'Pedido Disponible',
                'Hay un nuevo pedido listo para recoger',
                ['tipo' => 'pedido_disponible']
            );
        }
    }

    public function notificarCliente($usuario, $titulo, $mensaje, $data = [])
    {
        if ($usuario->fcm_token) {
            $this->enviarADispositivo($usuario->fcm_token, $titulo, $mensaje, $data);
        }
    }
}
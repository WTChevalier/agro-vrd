<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
    }

    /**
     * Enviar SMS
     */
    public function enviar($telefono, $mensaje)
    {
        // Formatear número dominicano
        $telefono = $this->formatearTelefono($telefono);

        switch ($this->provider) {
            case 'twilio':
                return $this->enviarTwilio($telefono, $mensaje);
            case 'claro':
                return $this->enviarClaro($telefono, $mensaje);
            default:
                Log::warning('Proveedor SMS no configurado');
                return false;
        }
    }

    protected function enviarTwilio($telefono, $mensaje)
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $telefono,
                    'Body' => $mensaje,
                ]);

            Log::info('SMS Twilio', ['to' => $telefono, 'response' => $response->json()]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS Twilio Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function enviarClaro($telefono, $mensaje)
    {
        // Implementación para API de Claro RD
        $url = config('services.claro.url');
        $apiKey = config('services.claro.api_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post($url . '/sms/send', [
                'phone' => $telefono,
                'message' => $mensaje,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS Claro Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function formatearTelefono($telefono)
    {
        // Remover caracteres no numéricos
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        // Si empieza con 1 (código país RD), agregar +
        if (strlen($telefono) === 11 && substr($telefono, 0, 1) === '1') {
            return '+' . $telefono;
        }

        // Si es número local de 10 dígitos, agregar +1
        if (strlen($telefono) === 10) {
            return '+1' . $telefono;
        }

        return '+' . $telefono;
    }

    /**
     * Notificaciones predefinidas
     */
    public function notificarPedidoConfirmado($pedido)
    {
        $mensaje = "SazónRD: Tu pedido {$pedido->codigo} ha sido confirmado. " .
                   "Tiempo estimado: {$pedido->restaurante->tiempo_entrega_estimado} min.";

        return $this->enviar($pedido->usuario->telefono, $mensaje);
    }

    public function notificarPedidoEnCamino($pedido)
    {
        $mensaje = "SazónRD: Tu pedido {$pedido->codigo} está en camino. " .
                   "Repartidor: {$pedido->repartidor->nombre}. " .
                   "Tel: {$pedido->repartidor->telefono}";

        return $this->enviar($pedido->usuario->telefono, $mensaje);
    }

    public function notificarPedidoEntregado($pedido)
    {
        $mensaje = "SazónRD: Tu pedido {$pedido->codigo} ha sido entregado. " .
                   "¡Buen provecho! Califica tu experiencia en la app.";

        return $this->enviar($pedido->usuario->telefono, $mensaje);
    }
}
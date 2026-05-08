<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagoService
{
    /**
     * Procesar pago con CardNet
     */
    public function procesarCardNet($pedido, $tarjeta)
    {
        $url = config('services.cardnet.url');
        $merchantId = config('services.cardnet.merchant_id');
        $terminalId = config('services.cardnet.terminal_id');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(config('services.cardnet.api_key')),
            ])->post($url . '/api/payment', [
                'MerchantId' => $merchantId,
                'TerminalId' => $terminalId,
                'Amount' => $pedido->total * 100, // En centavos
                'Currency' => 'DOP',
                'OrderId' => $pedido->codigo,
                'CardNumber' => $tarjeta['numero'],
                'ExpirationDate' => $tarjeta['expiracion'],
                'CVV' => $tarjeta['cvv'],
                'CardHolderName' => $tarjeta['nombre'],
            ]);

            $data = $response->json();

            Log::info('CardNet Response', $data);

            return [
                'success' => $data['ResponseCode'] === '00',
                'transactionId' => $data['TransactionId'] ?? null,
                'message' => $data['ResponseMessage'] ?? 'Error desconocido',
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('CardNet Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al procesar el pago'
            ];
        }
    }

    /**
     * Procesar pago con AZUL
     */
    public function procesarAzul($pedido, $tarjeta)
    {
        $url = config('services.azul.url');

        try {
            $data = [
                'Channel' => 'EC',
                'Store' => config('services.azul.merchant_id'),
                'CardNumber' => $tarjeta['numero'],
                'Expiration' => $tarjeta['expiracion'],
                'CVC' => $tarjeta['cvv'],
                'PosInputMode' => 'E-Commerce',
                'Amount' => number_format($pedido->total, 2, '', ''),
                'Itbis' => number_format($pedido->itbis, 2, '', ''),
                'CustomOrderId' => $pedido->codigo,
            ];

            // Generar AuthHash
            $authHash = $this->generarAzulHash($data);
            $data['AuthHash'] = $authHash;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Auth1' => config('services.azul.auth1'),
                'Auth2' => config('services.azul.auth2'),
            ])->post($url . '/webservices/JSON/Default.aspx', $data);

            $result = $response->json();

            Log::info('AZUL Response', $result);

            return [
                'success' => $result['ResponseCode'] === '00',
                'transactionId' => $result['AzulOrderId'] ?? null,
                'message' => $result['ResponseMessage'] ?? 'Error',
                'data' => $result
            ];
        } catch (\Exception $e) {
            Log::error('AZUL Error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al procesar el pago'
            ];
        }
    }

    private function generarAzulHash($data)
    {
        $key = config('services.azul.secret_key');
        $string = implode('', $data);
        return hash_hmac('sha512', $string, $key);
    }
}
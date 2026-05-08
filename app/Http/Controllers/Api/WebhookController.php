<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Webhook de CardNet
     */
    public function cardnet(Request $request)
    {
        Log::info('CardNet Webhook', $request->all());

        $transactionId = $request->input('TransactionId');
        $status = $request->input('Status');
        $orderId = $request->input('OrderId');

        $pedido = Pedido::where('codigo', $orderId)->first();

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        if ($status === 'approved') {
            Pago::create([
                'pedido_id' => $pedido->id,
                'referencia_externa' => $transactionId,
                'monto' => $pedido->total,
                'estado' => 'completado',
                'proveedor' => 'cardnet',
                'metodo' => 'tarjeta',
                'datos_respuesta' => json_encode($request->all()),
            ]);

            $pedido->update(['estado' => 'confirmado']);
        } else {
            Pago::create([
                'pedido_id' => $pedido->id,
                'referencia_externa' => $transactionId,
                'monto' => $pedido->total,
                'estado' => 'fallido',
                'proveedor' => 'cardnet',
                'metodo' => 'tarjeta',
                'datos_respuesta' => json_encode($request->all()),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Webhook de AZUL
     */
    public function azul(Request $request)
    {
        Log::info('AZUL Webhook', $request->all());

        // Verificar firma HMAC
        $signature = $request->header('X-Azul-Signature');
        // Implementar verificación de firma

        $pedido = Pedido::where('codigo', $request->input('CustomOrderId'))->first();

        if (!$pedido) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        if ($request->input('ResponseCode') === '00') {
            Pago::create([
                'pedido_id' => $pedido->id,
                'referencia_externa' => $request->input('AzulOrderId'),
                'monto' => $pedido->total,
                'estado' => 'completado',
                'proveedor' => 'azul',
                'metodo' => 'tarjeta',
                'datos_respuesta' => json_encode($request->all()),
            ]);

            $pedido->update(['estado' => 'confirmado']);
        }

        return response()->json(['success' => true]);
    }
}
<?php

namespace App\Services;

use App\Models\Restaurante;
use App\Models\QrRestaurante;
use App\Models\EscaneoQr;
use App\Models\PlacaCertificacion;
use App\Models\VerificacionPlaca;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de Generación de Códigos QR
 *
 * Genera y gestiona códigos QR para restaurantes y placas de certificación
 */
class GeneradorQrService
{
    /**
     * URL base para códigos QR
     */
    protected string $urlBase;

    /**
     * Directorio de almacenamiento de QRs
     */
    protected string $directorio = 'qr-codes';

    public function __construct()
    {
        $this->urlBase = config('app.url', 'https://sazonrd.com');
    }

    /**
     * Generar código QR para un restaurante
     */
    public function generarParaRestaurante(Restaurante $restaurante): QrRestaurante
    {
        // Verificar si ya tiene QR
        $qrExistente = $restaurante->qrCode;

        if ($qrExistente && $qrExistente->activo) {
            return $qrExistente;
        }

        // Generar código único
        $codigoUnico = $this->generarCodigoUnico('REST');

        // Crear URL del restaurante
        $url = "{$this->urlBase}/r/{$restaurante->slug}?ref={$codigoUnico}";

        // Generar imagen QR
        $nombreArchivo = "restaurante_{$restaurante->id}_{$codigoUnico}.png";
        $rutaArchivo = $this->generarImagenQr($url, $nombreArchivo);

        // Crear o actualizar registro
        $qr = QrRestaurante::updateOrCreate(
            ['restaurante_id' => $restaurante->id],
            [
                'codigo_unico' => $codigoUnico,
                'url_destino' => $url,
                'url_corta' => "{$this->urlBase}/qr/{$codigoUnico}",
                'archivo_qr' => $rutaArchivo,
                'tipo' => 'restaurante',
                'activo' => true,
                'generado_at' => now(),
            ]
        );

        return $qr;
    }

    /**
     * Generar código QR para placa de certificación
     */
    public function generarParaPlaca(PlacaCertificacion $placa): string
    {
        $codigoVerificacion = $placa->codigo_verificacion ?? $this->generarCodigoUnico('PLACA');

        // URL de verificación
        $url = "{$this->urlBase}/verificar-placa/{$codigoVerificacion}";

        // Generar imagen
        $nombreArchivo = "placa_{$placa->id}_{$codigoVerificacion}.png";
        $rutaArchivo = $this->generarImagenQr($url, $nombreArchivo, [
            'size' => 200,
            'margin' => 2,
            'color' => $this->obtenerColorCertificacion($placa->certificacion),
        ]);

        // Actualizar placa
        $placa->update([
            'codigo_verificacion' => $codigoVerificacion,
            'archivo_qr' => $rutaArchivo,
        ]);

        return $rutaArchivo;
    }

    /**
     * Registrar escaneo de QR
     */
    public function registrarEscaneo(
        string $codigoQr,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $ubicacion = null
    ): ?EscaneoQr {
        $qr = QrRestaurante::where('codigo_unico', $codigoQr)->first();

        if (!$qr) {
            return null;
        }

        // Incrementar contador
        $qr->increment('total_escaneos');

        // Registrar escaneo detallado
        return EscaneoQr::create([
            'qr_restaurante_id' => $qr->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'latitud' => $ubicacion['lat'] ?? null,
            'longitud' => $ubicacion['lng'] ?? null,
            'ciudad' => $ubicacion['ciudad'] ?? null,
            'pais' => $ubicacion['pais'] ?? null,
            'referrer' => request()->headers->get('referer'),
            'escaneado_at' => now(),
        ]);
    }

    /**
     * Verificar placa de certificación
     */
    public function verificarPlaca(string $codigoVerificacion): array
    {
        $placa = PlacaCertificacion::where('codigo_verificacion', $codigoVerificacion)
            ->with(['restaurante', 'certificacion'])
            ->first();

        if (!$placa) {
            return [
                'valido' => false,
                'mensaje' => 'Código de verificación no encontrado',
            ];
        }

        // Registrar verificación
        VerificacionPlaca::create([
            'placa_id' => $placa->id,
            'ip_address' => request()->ip(),
            'verificado_at' => now(),
        ]);

        // Verificar estado
        if ($placa->estado === 'revocada') {
            return [
                'valido' => false,
                'mensaje' => 'Esta certificación ha sido revocada',
                'placa' => $placa,
            ];
        }

        if ($placa->fecha_vencimiento && $placa->fecha_vencimiento->isPast()) {
            return [
                'valido' => false,
                'mensaje' => 'Esta certificación ha vencido',
                'placa' => $placa,
                'fecha_vencimiento' => $placa->fecha_vencimiento,
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Certificación válida y vigente',
            'placa' => $placa,
            'restaurante' => [
                'nombre' => $placa->restaurante->nombre,
                'direccion' => $placa->restaurante->direccion,
            ],
            'certificacion' => [
                'codigo' => $placa->certificacion->codigo,
                'nombre' => $placa->certificacion->nombre,
                'descripcion_publica' => $placa->certificacion->descripcion_publica,
            ],
            'fecha_emision' => $placa->fecha_emision,
            'fecha_vencimiento' => $placa->fecha_vencimiento,
        ];
    }

    /**
     * Regenerar QR de un restaurante
     */
    public function regenerar(Restaurante $restaurante): QrRestaurante
    {
        // Desactivar QR anterior
        if ($restaurante->qrCode) {
            $restaurante->qrCode->update(['activo' => false]);

            // Eliminar archivo anterior
            if ($restaurante->qrCode->archivo_qr) {
                Storage::disk('public')->delete($restaurante->qrCode->archivo_qr);
            }
        }

        // Generar nuevo
        return $this->generarParaRestaurante($restaurante);
    }

    /**
     * Obtener estadísticas de escaneos
     */
    public function obtenerEstadisticas(Restaurante $restaurante, int $dias = 30): array
    {
        $qr = $restaurante->qrCode;

        if (!$qr) {
            return [
                'total_escaneos' => 0,
                'escaneos_periodo' => 0,
                'por_dia' => [],
            ];
        }

        $escaneosPerido = EscaneoQr::where('qr_restaurante_id', $qr->id)
            ->where('escaneado_at', '>=', now()->subDays($dias))
            ->get();

        // Agrupar por día
        $porDia = $escaneosPerido->groupBy(function ($escaneo) {
            return $escaneo->escaneado_at->format('Y-m-d');
        })->map->count();

        // Agrupar por ciudad
        $porCiudad = $escaneosPerido->groupBy('ciudad')
            ->map->count()
            ->sortDesc()
            ->take(5);

        return [
            'total_escaneos' => $qr->total_escaneos,
            'escaneos_periodo' => $escaneosPerido->count(),
            'por_dia' => $porDia->toArray(),
            'por_ciudad' => $porCiudad->toArray(),
            'promedio_diario' => round($escaneosPerido->count() / $dias, 1),
        ];
    }

    /**
     * Generar QRs en lote para múltiples restaurantes
     */
    public function generarEnLote(array $restauranteIds): array
    {
        $resultados = [
            'generados' => 0,
            'errores' => [],
        ];

        foreach ($restauranteIds as $id) {
            try {
                $restaurante = Restaurante::find($id);
                if ($restaurante) {
                    $this->generarParaRestaurante($restaurante);
                    $resultados['generados']++;
                }
            } catch (\Exception $e) {
                $resultados['errores'][] = [
                    'restaurante_id' => $id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Generar código único
     */
    protected function generarCodigoUnico(string $prefijo = ''): string
    {
        $codigo = $prefijo . strtoupper(Str::random(8));

        // Verificar unicidad
        while (
            QrRestaurante::where('codigo_unico', $codigo)->exists() ||
            PlacaCertificacion::where('codigo_verificacion', $codigo)->exists()
        ) {
            $codigo = $prefijo . strtoupper(Str::random(8));
        }

        return $codigo;
    }

    /**
     * Generar imagen QR
     */
    protected function generarImagenQr(string $contenido, string $nombreArchivo, array $opciones = []): string
    {
        $size = $opciones['size'] ?? 300;
        $margin = $opciones['margin'] ?? 1;
        $color = $opciones['color'] ?? [234, 88, 12]; // Naranja SazónRD

        // Generar QR
        $qrCode = QrCode::format('png')
            ->size($size)
            ->margin($margin)
            ->color($color[0], $color[1], $color[2])
            ->errorCorrection('H')
            ->generate($contenido);

        // Guardar archivo
        $ruta = "{$this->directorio}/{$nombreArchivo}";
        Storage::disk('public')->put($ruta, $qrCode);

        return $ruta;
    }

    /**
     * Obtener color según certificación
     */
    protected function obtenerColorCertificacion($certificacion): array
    {
        if (!$certificacion) {
            return [107, 114, 128]; // Gris
        }

        return match ($certificacion->codigo) {
            'A' => [16, 185, 129],    // Verde
            'B' => [59, 130, 246],    // Azul
            'C' => [245, 158, 11],    // Amarillo
            'D' => [249, 115, 22],    // Naranja
            'E' => [220, 38, 38],     // Rojo
            default => [107, 114, 128],
        };
    }
}

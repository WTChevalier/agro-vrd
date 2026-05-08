<?php

namespace App\Services;

use App\Models\ModuloSistema;
use App\Models\FuncionSistema;
use App\Models\Restaurante;
use App\Models\ConfiguracionZona;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de Verificación de Funciones
 *
 * Determina si una función está habilitada en un contexto específico
 * siguiendo la jerarquía: Global > Zona > Plan > Restaurante Override
 */
class VerificadorFuncionesService
{
    /**
     * Tiempo de caché en segundos
     */
    protected int $cacheTtl = 300;

    /**
     * Verificar si un módulo está activo globalmente
     */
    public function moduloActivo(string $codigoModulo): bool
    {
        return Cache::remember(
            "modulo_activo_{$codigoModulo}",
            $this->cacheTtl,
            fn() => ModuloSistema::where('codigo', $codigoModulo)
                ->where('activo_global', true)
                ->exists()
        );
    }

    /**
     * Verificar si un módulo está activo en una zona específica
     */
    public function moduloActivoEnZona(string $codigoModulo, int $zonaId): bool
    {
        // Primero verificar si está activo globalmente
        if (!$this->moduloActivo($codigoModulo)) {
            return false;
        }

        $cacheKey = "modulo_{$codigoModulo}_zona_{$zonaId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($codigoModulo, $zonaId) {
            $modulo = ModuloSistema::where('codigo', $codigoModulo)->first();

            if (!$modulo) {
                return false;
            }

            $configZona = ConfiguracionZona::where('modulo_id', $modulo->id)
                ->where('zona_id', $zonaId)
                ->first();

            // Si no hay configuración específica, usar valor global
            return $configZona ? $configZona->activo : true;
        });
    }

    /**
     * Verificar si una función está habilitada para un restaurante
     *
     * Prioridad: Override Restaurante > Plan > Zona > Global
     */
    public function funcionHabilitada(
        string $codigoFuncion,
        ?Restaurante $restaurante = null,
        ?int $zonaId = null
    ): bool {
        // Obtener la función
        $funcion = $this->obtenerFuncion($codigoFuncion);

        if (!$funcion) {
            return false;
        }

        // Verificar si el módulo padre está activo
        if (!$this->moduloActivo($funcion->modulo->codigo)) {
            return false;
        }

        // 1. Verificar override del restaurante
        if ($restaurante && $funcion->configurable_restaurante) {
            $valorOverride = $this->obtenerOverrideRestaurante($restaurante, $codigoFuncion);
            if ($valorOverride !== null) {
                return (bool) $valorOverride;
            }
        }

        // 2. Verificar función en el plan del restaurante
        if ($restaurante && $restaurante->plan) {
            if ($restaurante->plan->tieneFuncion($codigoFuncion)) {
                return true;
            }
        }

        // 3. Verificar configuración de zona
        $zonaAVerificar = $zonaId ?? ($restaurante ? $restaurante->municipio_id : null);
        if ($zonaAVerificar && $funcion->configurable_zona) {
            $valorZona = $this->obtenerValorZona($funcion, $zonaAVerificar);
            if ($valorZona !== null) {
                return (bool) $valorZona;
            }
        }

        // 4. Usar valor por defecto de la función
        return $funcion->convertirValor($funcion->valor_defecto);
    }

    /**
     * Obtener el valor de una función para un contexto
     */
    public function obtenerValorFuncion(
        string $codigoFuncion,
        ?Restaurante $restaurante = null,
        ?int $zonaId = null,
        mixed $defecto = null
    ): mixed {
        $funcion = $this->obtenerFuncion($codigoFuncion);

        if (!$funcion) {
            return $defecto;
        }

        // 1. Override del restaurante
        if ($restaurante && $funcion->configurable_restaurante) {
            $valorOverride = $this->obtenerOverrideRestaurante($restaurante, $codigoFuncion);
            if ($valorOverride !== null) {
                return $funcion->convertirValor($valorOverride);
            }
        }

        // 2. Valor del plan
        if ($restaurante && $restaurante->plan) {
            $valorPlan = $restaurante->plan->obtenerValorFuncion($codigoFuncion);
            if ($valorPlan !== null) {
                return $funcion->convertirValor($valorPlan);
            }
        }

        // 3. Valor de zona
        $zonaAVerificar = $zonaId ?? ($restaurante ? $restaurante->municipio_id : null);
        if ($zonaAVerificar && $funcion->configurable_zona) {
            $valorZona = $this->obtenerValorZona($funcion, $zonaAVerificar);
            if ($valorZona !== null) {
                return $funcion->convertirValor($valorZona);
            }
        }

        // 4. Valor por defecto
        return $funcion->convertirValor($funcion->valor_defecto) ?? $defecto;
    }

    /**
     * Obtener límite para un restaurante
     */
    public function obtenerLimite(
        string $codigoLimite,
        ?Restaurante $restaurante = null,
        int $defecto = 0
    ): int {
        // 1. Override del restaurante
        if ($restaurante && $restaurante->limites_override) {
            if (isset($restaurante->limites_override[$codigoLimite])) {
                return (int) $restaurante->limites_override[$codigoLimite];
            }
        }

        // 2. Límite del plan
        if ($restaurante && $restaurante->plan) {
            return $restaurante->plan->obtenerLimite($codigoLimite, $defecto);
        }

        return $defecto;
    }

    /**
     * Verificar si un límite es ilimitado
     */
    public function esIlimitado(string $codigoLimite, ?Restaurante $restaurante = null): bool
    {
        return $this->obtenerLimite($codigoLimite, $restaurante) === -1;
    }

    /**
     * Verificar si el restaurante puede realizar una acción
     */
    public function restaurantePuedeRealizar(Restaurante $restaurante, string $accion): bool
    {
        // Verificar estado de la suscripción
        if (!$restaurante->suscripcion_activa_esta_vigente) {
            // Solo permitir acciones básicas si no tiene suscripción activa
            $accionesBasicas = ['ver_perfil', 'editar_info_basica'];
            if (!in_array($accion, $accionesBasicas)) {
                return false;
            }
        }

        // Verificar nivel de confianza
        if ($restaurante->nivelConfianza) {
            if (!$restaurante->nivelConfianza->permiteAccion($accion)) {
                return false;
            }
        }

        // Verificar si la función está habilitada
        return $this->funcionHabilitada($accion, $restaurante);
    }

    /**
     * Obtener todas las funciones habilitadas para un restaurante
     */
    public function obtenerFuncionesHabilitadas(Restaurante $restaurante): array
    {
        $funcionesHabilitadas = [];

        $funciones = FuncionSistema::with('modulo')
            ->where('activo', true)
            ->get();

        foreach ($funciones as $funcion) {
            if ($this->funcionHabilitada($funcion->codigo, $restaurante)) {
                $funcionesHabilitadas[$funcion->codigo] = [
                    'nombre' => $funcion->nombre,
                    'valor' => $this->obtenerValorFuncion($funcion->codigo, $restaurante),
                    'modulo' => $funcion->modulo->nombre,
                ];
            }
        }

        return $funcionesHabilitadas;
    }

    /**
     * Limpiar caché de funciones
     */
    public function limpiarCache(?Restaurante $restaurante = null, ?int $zonaId = null): void
    {
        // Limpiar caché de módulos
        $modulos = ModuloSistema::all();
        foreach ($modulos as $modulo) {
            Cache::forget("modulo_activo_{$modulo->codigo}");

            if ($zonaId) {
                Cache::forget("modulo_{$modulo->codigo}_zona_{$zonaId}");
            }
        }

        // Limpiar caché de funciones
        $funciones = FuncionSistema::all();
        foreach ($funciones as $funcion) {
            Cache::forget("funcion_{$funcion->codigo}");

            if ($zonaId) {
                Cache::forget("funcion_{$funcion->codigo}_zona_{$zonaId}");
            }
        }
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Obtener función desde caché o base de datos
     */
    protected function obtenerFuncion(string $codigo): ?FuncionSistema
    {
        return Cache::remember(
            "funcion_{$codigo}",
            $this->cacheTtl,
            fn() => FuncionSistema::with('modulo')
                ->where('codigo', $codigo)
                ->where('activo', true)
                ->first()
        );
    }

    /**
     * Obtener override de restaurante
     */
    protected function obtenerOverrideRestaurante(Restaurante $restaurante, string $codigoFuncion): mixed
    {
        if (!$restaurante->funciones_habilitadas) {
            return null;
        }

        return $restaurante->funciones_habilitadas[$codigoFuncion] ?? null;
    }

    /**
     * Obtener valor de configuración de zona
     */
    protected function obtenerValorZona(FuncionSistema $funcion, int $zonaId): mixed
    {
        $cacheKey = "funcion_{$funcion->codigo}_zona_{$zonaId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($funcion, $zonaId) {
            $config = ConfiguracionZona::where('zona_id', $zonaId)
                ->where('modulo_id', $funcion->modulo_id)
                ->first();

            if (!$config || !$config->configuracion) {
                return null;
            }

            return $config->configuracion[$funcion->codigo] ?? null;
        });
    }
}

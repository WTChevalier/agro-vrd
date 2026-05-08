<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\VisitRDSSOService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controlador SSO con visitRD
 *
 * Maneja la autenticación Single Sign-On con visitrepublicadominicana.com
 * usando OAuth 2.0 como servidor de autenticación.
 */
class SSOController extends Controller
{
    /**
     * El nombre del proveedor SSO para VisitRD.
     */
    private const PROVEEDOR = 'visitrd';

    public function __construct(
        protected VisitRDSSOService $servicioSSO
    ) {}

    /**
     * Redirigir al usuario a la página de autenticación de VisitRD.
     */
    public function redirigir(Request $request): RedirectResponse
    {
        // Guardar la URL de retorno si existe
        if ($request->has('redirigir_a')) {
            session(['sso_redirigir_a' => $request->input('redirigir_a')]);
        }

        // Generar state para prevenir CSRF
        $state = Str::random(40);
        session(['sso.state' => $state]);

        // Construir URL de autorización de visitRD
        $parametros = [
            'client_id' => config('services.visitrd.client_id'),
            'redirect_uri' => route('sso.callback'),
            'response_type' => 'code',
            'scope' => 'perfil correo telefono',
            'state' => $state,
        ];

        $urlAutorizacion = config('services.visitrd.url') . '/oauth/authorize?' . http_build_query($parametros);

        return redirect($urlAutorizacion);
    }

    /**
     * Manejar el callback de VisitRD después de la autenticación.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // Verificar si hay error en el callback
            if ($request->has('error')) {
                return $this->manejarError(
                    $request->input('error'),
                    $request->input('error_description', 'Error de autenticación')
                );
            }

            // Verificar state para prevenir CSRF
            if ($request->input('state') !== session('sso.state')) {
                Log::warning('SSO state mismatch', [
                    'esperado' => session('sso.state'),
                    'recibido' => $request->input('state'),
                ]);

                return redirect()->route('inicio')
                    ->with('error', 'Error de seguridad. Por favor, intenta de nuevo.');
            }

            // Verificar que tenemos el código de autorización
            if (!$request->has('code')) {
                return redirect()->route('inicio')
                    ->with('error', 'Código de autorización no recibido.');
            }

            // Intercambiar código por tokens
            $tokens = $this->servicioSSO->intercambiarCodigo($request->input('code'));

            if (!$tokens || !isset($tokens['access_token'])) {
                throw new \Exception('No se recibieron tokens válidos');
            }

            // Obtener usuario de VisitRD
            $datosUsuarioVisitRD = $this->servicioSSO->obtenerUsuario($tokens['access_token']);

            if (!$datosUsuarioVisitRD || !isset($datosUsuarioVisitRD['id'])) {
                throw new \Exception('No se pudieron obtener los datos del usuario');
            }

            // Buscar o crear usuario
            $usuario = $this->buscarOCrearUsuario($datosUsuarioVisitRD, $tokens);

            // Verificar que el usuario está activo
            if (!$usuario->activo) {
                return redirect()->route('inicio')
                    ->with('error', 'Tu cuenta está desactivada. Contacta soporte para más información.');
            }

            // Iniciar sesión
            Auth::login($usuario, remember: true);

            // Regenerar sesión por seguridad
            $request->session()->regenerate();

            // Actualizar última conexión
            $usuario->update([
                'ultimo_login_at' => now(),
                'ultimo_login_ip' => $request->ip(),
            ]);

            // Obtener URL de redirección
            $redirigirA = session()->pull('sso_redirigir_a', route('inicio'));

            // Limpiar sesión SSO
            session()->forget(['sso.state']);

            return redirect()->intended($redirigirA)
                ->with('exito', "¡Bienvenido, {$usuario->nombre}!");

        } catch (\Exception $e) {
            Log::error('Error en callback SSO', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('inicio')
                ->with('error', 'Error al conectar con VisitRD. Por favor, intenta de nuevo.');
        }
    }

    /**
     * Cerrar sesión del usuario.
     */
    public function cerrarSesion(Request $request): RedirectResponse
    {
        $usuario = auth()->user();

        // Revocar tokens en visitRD si es posible
        if ($usuario && $usuario->visitrd_token) {
            try {
                $this->servicioSSO->revocarToken($usuario->visitrd_token);
            } catch (\Exception $e) {
                Log::warning('No se pudo revocar token en visitRD', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cerrar sesión local
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Opcionalmente redirigir al logout de visitRD
        if (config('services.visitrd.logout_redirect', false)) {
            $urlLogout = config('services.visitrd.url') . '/logout?' . http_build_query([
                'redirect_uri' => route('inicio'),
            ]);

            return redirect($urlLogout);
        }

        return redirect()->route('inicio')
            ->with('exito', 'Has cerrado sesión correctamente');
    }

    /**
     * Buscar un usuario existente o crear uno nuevo.
     */
    private function buscarOCrearUsuario(array $datosVisitRD, array $tokens): Usuario
    {
        // Buscar por ID de VisitRD
        $usuario = Usuario::where('visitrd_id', $datosVisitRD['id'])->first();

        if ($usuario) {
            // Actualizar datos del usuario si han cambiado
            $this->actualizarUsuarioDesdeVisitRD($usuario, $datosVisitRD, $tokens);
            return $usuario;
        }

        // Buscar por email (puede ser un usuario existente que nunca usó SSO)
        $correo = $datosVisitRD['email'] ?? $datosVisitRD['correo'] ?? null;
        if ($correo) {
            $usuario = Usuario::where('correo', $correo)->first();

            if ($usuario) {
                // Vincular cuenta existente con VisitRD
                $usuario->update([
                    'visitrd_id' => $datosVisitRD['id'],
                    'visitrd_token' => $tokens['access_token'],
                    'visitrd_refresh_token' => $tokens['refresh_token'] ?? null,
                    'visitrd_token_expira_at' => isset($tokens['expires_in'])
                        ? now()->addSeconds($tokens['expires_in'])
                        : null,
                ]);
                $this->actualizarUsuarioDesdeVisitRD($usuario, $datosVisitRD, $tokens);
                return $usuario;
            }
        }

        // Crear nuevo usuario
        return Usuario::create([
            'visitrd_id' => $datosVisitRD['id'],
            'nombre' => $datosVisitRD['nombre'] ?? $datosVisitRD['name'] ?? 'Usuario',
            'apellido' => $datosVisitRD['apellido'] ?? '',
            'correo' => $correo,
            'telefono' => $datosVisitRD['telefono'] ?? $datosVisitRD['phone'] ?? null,
            'avatar' => $datosVisitRD['avatar'] ?? null,
            'password' => Hash::make(Str::random(32)), // Password aleatorio ya que usa SSO
            'rol' => 'cliente',
            'activo' => true,
            'correo_verificado_at' => now(), // Verificado por VisitRD
            'saldo_billetera' => 0,
            'puntos_lealtad' => 0,
            'visitrd_token' => $tokens['access_token'],
            'visitrd_refresh_token' => $tokens['refresh_token'] ?? null,
            'visitrd_token_expira_at' => isset($tokens['expires_in'])
                ? now()->addSeconds($tokens['expires_in'])
                : null,
        ]);
    }

    /**
     * Actualizar los datos del usuario desde VisitRD.
     */
    private function actualizarUsuarioDesdeVisitRD(Usuario $usuario, array $datosVisitRD, array $tokens): void
    {
        $actualizaciones = [
            'visitrd_token' => $tokens['access_token'],
            'visitrd_refresh_token' => $tokens['refresh_token'] ?? $usuario->visitrd_refresh_token,
            'visitrd_token_expira_at' => isset($tokens['expires_in'])
                ? now()->addSeconds($tokens['expires_in'])
                : $usuario->visitrd_token_expira_at,
        ];

        // Solo actualizar si el usuario no ha personalizado estos campos
        if (!$usuario->avatar && ($datosVisitRD['avatar'] ?? null)) {
            $actualizaciones['avatar'] = $datosVisitRD['avatar'];
        }

        // Actualizar teléfono si viene de VisitRD y el usuario no tiene uno
        $telefonoVisitRD = $datosVisitRD['telefono'] ?? $datosVisitRD['phone'] ?? null;
        if (!$usuario->telefono && $telefonoVisitRD) {
            $actualizaciones['telefono'] = $telefonoVisitRD;
        }

        // Actualizar nombre si está vacío
        $nombreVisitRD = $datosVisitRD['nombre'] ?? $datosVisitRD['name'] ?? null;
        if (empty($usuario->nombre) && $nombreVisitRD) {
            $actualizaciones['nombre'] = $nombreVisitRD;
        }

        if (!empty($actualizaciones)) {
            $usuario->update($actualizaciones);
        }
    }

    /**
     * Manejar errores del callback de OAuth.
     */
    private function manejarError(string $error, string $descripcion): RedirectResponse
    {
        Log::warning('Error SSO visitRD', [
            'error' => $error,
            'descripcion' => $descripcion,
        ]);

        $mensaje = match ($error) {
            'access_denied' => 'Has cancelado la autenticación.',
            'invalid_request' => 'Solicitud inválida. Por favor, intenta de nuevo.',
            'unauthorized_client' => 'Cliente no autorizado.',
            'unsupported_response_type' => 'Tipo de respuesta no soportado.',
            'invalid_scope' => 'Permisos solicitados no válidos.',
            'server_error' => 'Error del servidor de VisitRD. Por favor, intenta más tarde.',
            'temporarily_unavailable' => 'VisitRD no está disponible temporalmente. Por favor, intenta más tarde.',
            default => $descripcion,
        };

        return redirect()->route('inicio')
            ->with('error', $mensaje);
    }

    /**
     * Desvincular la cuenta de VisitRD.
     */
    public function desvincular(Request $request): RedirectResponse
    {
        $usuario = Auth::user();

        // Verificar que el usuario tiene password (puede iniciar sesión sin SSO)
        if (!$usuario->password || Hash::check('', $usuario->password)) {
            return back()->with('error', 'Debes establecer una contraseña antes de desvincular tu cuenta de VisitRD.');
        }

        $usuario->update([
            'visitrd_id' => null,
            'visitrd_token' => null,
            'visitrd_refresh_token' => null,
            'visitrd_token_expira_at' => null,
        ]);

        return back()->with('exito', 'Tu cuenta de VisitRD ha sido desvinculada.');
    }
}

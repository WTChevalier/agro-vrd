# gurztac/auth-client

Cliente Laravel para validar JWT contra el IdP **Cuenta Gurztac** (Hub Corporativo).

## Instalación (dentro del monorepo del ecosistema)

```bash
composer config repositories.gurztac-auth-client path packages/gurztac/auth-client
composer require gurztac/auth-client:@dev
```

## Publicar config

```bash
php artisan vendor:publish --tag=gurztac-auth-config
```

## .env requerido en la app tenant

```
GURZTAC_HUB_URL=https://corporativo.gurztacproductions.com
GURZTAC_JWKS_URL=https://corporativo.gurztacproductions.com/api/idp/auth/jwks
GURZTAC_AUD=<slug de plataforma en Hub>
GURZTAC_ISS=cuenta.gurztacproductions.com
GURZTAC_AFTER_LOGIN=/dashboard
GURZTAC_AFTER_LOGOUT=/
```

## Middlewares

- `gurztac.jwt` → APIs (return 401 si no válido)
- `gurztac.auth` → web (redirect a SSO login si no válido)

```php
Route::middleware('gurztac.auth')->get('/dashboard', DashboardController::class);
Route::middleware('gurztac.jwt')->prefix('api')->group(function () {
    // endpoints API
});
```

## Acceso a claims

```php
use Gurztac\AuthClient\Traits\HasGurztacAuth;

class DashboardController extends Controller {
    use HasGurztacAuth;

    public function __invoke(Request $request) {
        $userId = $this->gurztacUserId($request);
        $email = $this->gurztacEmail($request);
        $claims = $this->gurztacClaims($request);
        // ...
    }
}
```

## SSO flow

Las rutas siguientes vienen autoregistradas:

- `GET /auth/sso/login` — redirige al Hub
- `GET /auth/sso/callback` — recibe code, valida JWT, guarda en session
- `POST /auth/sso/logout` — revoca en Hub y limpia session

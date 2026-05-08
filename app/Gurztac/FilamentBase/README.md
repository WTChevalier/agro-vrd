# Gurztac FilamentBase — Capa común del ecosistema

**Sprint 139AA (2026-04-28)** — Paquete base que homologa la apariencia y estructura
de todos los paneles `/admin` del ecosistema Gurztac. Cada app marca instala
estos archivos y extiende las clases base para tener el polish visual unificado
sin duplicar código.

## Compatibilidad

- **Filament 3.x** ✅ (10/12 apps del ecosistema)
- **Filament 5.x** ❌ (GurzTicket — usa paquete separado v5)
- **Laravel 11/12** ✅
- **PHP 8.2+** ✅

## Estructura

```
app/Gurztac/FilamentBase/
├── Providers/
│   └── BaseAdminPanelProvider.php    ← abstract con todo el polish
├── Pages/
│   └── BasePanelAdministracion.php   ← Dashboard "Panel de Administración"
├── Widgets/
│   └── KpisGenericoWidget.php        ← KPIs animados con sparklines
└── README.md
```

Adicionalmente cada app debe tener:
```
resources/views/components/hub-theme-overrides.blade.php
public/logos/{codigo-marca}.svg
```

## Instalación en una app marca

### 1. Copiar los archivos del paquete
```bash
cp -r FilamentBase/ {app-marca}/app/Gurztac/FilamentBase/
cp hub-theme-overrides.blade.php {app-marca}/resources/views/components/
cp {codigo}.svg {app-marca}/public/logos/
```

### 2. Modificar `app/Providers/Filament/AdminPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use App\Gurztac\FilamentBase\Providers\BaseAdminPanelProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;

class AdminPanelProvider extends BaseAdminPanelProvider
{
    protected function codigoMarca(): string
    {
        return 'sazonrd'; // se mapea a /public/logos/sazonrd.svg
    }

    protected function nombreMarca(): string
    {
        return 'SazónRD';
    }

    protected function color(): array
    {
        return Color::Red; // identidad de marca
    }

    // Solo agregar overrides específicos de esta marca:
    public function panel(Panel $panel): Panel
    {
        $panel = parent::panel($panel);

        return $panel
            ->widgets([
                \App\Filament\Widgets\PedidosChartWidget::class,
                \App\Gurztac\FilamentBase\Widgets\KpisGenericoWidget::class,
                // ... otros widgets propios de la marca
            ])
            ->navigationItems([
                // ... links custom de la marca
            ]);
    }
}
```

### 3. Modificar `app/Filament/Pages/Dashboard.php`

```php
<?php

namespace App\Filament\Pages;

use App\Gurztac\FilamentBase\Pages\BasePanelAdministracion;

class Dashboard extends BasePanelAdministracion
{
    public function getWidgets(): array
    {
        return [
            \App\Gurztac\FilamentBase\Widgets\KpisGenericoWidget::class,
            \App\Filament\Widgets\PedidosChartWidget::class,
            // ... widgets propios
        ];
    }
}
```

### 4. Limpiar caches

```bash
php artisan view:clear && php artisan cache:clear && php artisan config:clear
```

## Personalizar KPIs por marca

Cada marca puede heredar `KpisGenericoWidget` y sobrescribir `tablasMonitoreadas()`
para apuntar a sus tablas específicas:

```php
class KpisSazonRDWidget extends \App\Gurztac\FilamentBase\Widgets\KpisGenericoWidget
{
    protected function tablasMonitoreadas(): array
    {
        return [
            ['titulo' => 'Pedidos hoy 🍽️', 'tabla' => 'pedidos', 'descripcion' => 'Órdenes del día', 'color' => 'primary', 'icono' => 'heroicon-o-shopping-bag', 'condicion' => fn($q) => $q->whereDate('created_at', today())],
            ['titulo' => 'Restaurantes activos 🏪', 'tabla' => 'restaurantes', 'descripcion' => 'Operando ahora', 'color' => 'success', 'icono' => 'heroicon-o-building-storefront'],
            ['titulo' => 'Repartidores online 🛵', 'tabla' => 'repartidores', 'descripcion' => 'Disponibles', 'color' => 'warning', 'icono' => 'heroicon-o-truck'],
            ['titulo' => 'Ventas mes 💰', 'tabla' => 'pedidos', 'descripcion' => 'Acumulado mes', 'color' => 'info', 'icono' => 'heroicon-o-currency-dollar', 'condicion' => fn($q) => $q->whereMonth('created_at', now()->month)],
        ];
    }
}
```

## Lo que aplica automáticamente al extender BaseAdminPanelProvider

✅ `darkMode(true)` — toggle ☀️/🌙/💻 en avatar
✅ `brandLogo` apuntando a `/public/logos/{codigo}.svg`
✅ `brandLogoHeight('2.5rem')` — tamaño consistente
✅ Paleta expandida: primary marca + info/success/warning/danger/gray vivos
✅ `renderHook` con CSS theme overrides (contraste, headings, badges, hover)
✅ `userMenuItems` con "Ir a la Web"
✅ `sidebarCollapsibleOnDesktop`
✅ Middleware estándar
✅ Auto-discovery de Resources/Pages/Widgets

## Mantenimiento futuro

Cuando se quiera agregar una mejora visual a TODAS las marcas:
1. Editar el archivo correspondiente en este paquete
2. Sincronizar a las 10 apps marca (script de deploy)
3. `php artisan view:clear` en cada app

Sin necesidad de tocar el AdminPanelProvider de cada marca individualmente.

---

**Apps con el paquete desplegado (12 — Sprint 139AA, 2026-04-28):**
- Hub Corporativo (`gurztacproductions.com`)
- SazónRD (`sazonrd.com`)
- Visit RD (`visitrepublicadominicana.com`)
- Chevalier Studios (`chevalier-studios.com`)
- WT Chevalier (`wtchevalier.com`)
- GurzMed (`gurzmed.com`)
- ExerFitness IN/NY/OH/RD (4 sedes)
- RMA US/DR (2 sedes)

Cada una tiene `app/Gurztac/FilamentBase/{Providers,Pages,Widgets,README.md}`
con permisos `644` y owner del propio dominio (sazon5557, visit5221, etc.).

El namespace `App\Gurztac\FilamentBase\*` resuelve automáticamente vía PSR-4
(`"App\\": "app/"` en `composer.json` de cada app — verificado).

**Caso especial (Filament 5):**
- GurzTicket → paquete propio en `app/Gurztac/FilamentBaseV5/` (pendiente)

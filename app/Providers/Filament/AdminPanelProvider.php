<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString; // 💡 TAMBAHKAN IMPORT INI
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->assets([
                Css::make('custom-mc-theme', asset('css/custom-filament.css')),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(true)
            ->brandName('Material Control')
            // 💡 Brand Logo (Gambar + Teks Material Control)
            ->brandLogo(fn () => new HtmlString('
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="' . asset('images/logo-stp.png') . '" alt="Logo STP" style="height: 2.5rem;" />
                    <span style="font-size: 1.15rem; font-weight: 800; color: #1D3557; letter-spacing: -0.02em;">Material Control</span>
                </div>
            '))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo-stp.png'))
            ->navigationGroups([
                NavigationGroup::make('Stok Material'),
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Monitoring Alat'),
                NavigationGroup::make('Laporan'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
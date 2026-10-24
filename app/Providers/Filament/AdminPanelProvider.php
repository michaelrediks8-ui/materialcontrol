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
use Filament\Support\Enums\MaxWidth;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Versi CSS = waktu terakhir file diubah, supaya browser selalu memuat yang terbaru
        $cssPath = public_path('css/custom-filament.css');
        $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->assets([
                Css::make('custom-mc-theme', asset('css/custom-filament.css') . '?v=' . $cssVersion),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(true)
            ->maxContentWidth(MaxWidth::Full)
            ->brandName('Material Control')
            ->breadcrumbs(false)
            ->brandLogo(fn () => new HtmlString('
    <div style="display:flex;align-items:center;gap:12px;">
        <img src="' . asset('images/logo-stp.png') . '" alt="Logo STP" style="height:2.5rem;border-radius:0.5rem;" />
        <span class="mc-brand-text">Material Control</span>
    </div>
'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo-stp.png'))
            // Urutan grup menu di sidebar (dari atas ke bawah)
            ->navigationGroups([
                NavigationGroup::make('Stok Material'),
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Monitoring Alat'),
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Pengaturan Sistem'),
            ])
            // CSS global: filter tanggal di kiri, search di kanan (sejajar), Reset tetap di pojok kanan atas
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <style>
                        .fi-ta-header-ctn {
                            display: grid;
                            grid-template-columns: 1fr;
                            align-items: end;
                        }
                        .fi-ta-header-ctn > * {
                            grid-column: 1 / -1;
                        }
                        /* Wadah filter selebar penuh, jadi tombol Reset tetap di kanan */
                        .fi-ta-header-ctn > .fi-ta-filters-above-content-ctn {
                            grid-column: 1 / -1;
                            grid-row: 1;
                            border-bottom: 0;
                        }
                        /* Search ditumpuk di kanan bawah baris filter, sejajar dengan input tanggal */
                        .fi-ta-header-ctn > .fi-ta-header-toolbar {
                            grid-column: 1 / -1;
                            grid-row: 1;
                            justify-self: end;
                            align-self: end;
                            border-top: 0;
                            position: relative;
                            z-index: 1;
                        }
                    </style>
                ')
            )
            // CSS repeater rincian barang (rapat, 1 item = 1 baris di tablet ke atas).
            // Diarahkan ke id section "Rincian Barang yang Diterima", jadi tidak bergantung pada file CSS atau class tambahan.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <style>
                        [id="data.rincian-barang-yang-diterima"] .fi-section-header {
                            padding: 0.6rem 1rem !important;
                        }
                        [id="data.rincian-barang-yang-diterima"] .fi-section-content {
                            padding: 0.75rem 1rem !important;
                        }
                        [id="data.rincian-barang-yang-diterima"] ul:has(> .fi-fo-repeater-item) {
                            gap: 0.4rem !important;
                        }
                        [id="data.rincian-barang-yang-diterima"] .fi-fo-repeater-item {
                            position: relative !important;
                            border-radius: 0.75rem !important;
                        }
                        [id="data.rincian-barang-yang-diterima"] .fi-fo-repeater-item-content {
                            padding: 0.5rem 2.75rem 0.6rem 0.75rem !important;
                            border-top: 0 !important;
                        }
                        /* header item (tombol hapus) melayang di kanan, tidak memakan tinggi */
                        [id="data.rincian-barang-yang-diterima"] .fi-fo-repeater-item-header {
                            position: absolute !important;
                            top: 0.4rem !important;
                            right: 0.5rem !important;
                            z-index: 5 !important;
                            padding: 0 !important;
                            min-height: 0 !important;
                            height: auto !important;
                            border: 0 !important;
                            background: transparent !important;
                        }
                        /* HP: header kembali normal di atas item, tetap rapat */
                        @media (max-width: 767px) {
                            [id="data.rincian-barang-yang-diterima"] .fi-fo-repeater-item-header {
                                position: static !important;
                                padding: 0.25rem 0.5rem !important;
                                text-align: right;
                            }
                            [id="data.rincian-barang-yang-diterima"] .fi-fo-repeater-item-content {
                                padding: 0.4rem 0.75rem 0.75rem !important;
                            }
                        }
                    </style>
                ')
            )
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
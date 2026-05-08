<?php

namespace App\Filament\Restaurant\Pages;

use Filament\Pages\Page;

class RegisterRestaurant extends Page
{
      protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
      protected static string $view = 'filament.restaurant.pages.register-restaurant';
      protected static bool $shouldRegisterNavigation = false;
      
      public static function getSlug(?\Filament\Panel $panel = null): string
      {
                return 'registro';
      }
}
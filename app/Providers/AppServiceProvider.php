<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
class AppServiceProvider extends ServiceProvider {
 public function register(): void {}
 public function boot(): void {
   Gate::before(fn (User $user, string $ability) => $user->hasRole('admin') ? true : null);
   Gate::define('manage-reservations', fn(User $u)=> $u->hasPermission('manage-reservations'));
   Gate::define('manage-finance', fn(User $u)=> $u->hasPermission('manage-finance'));
   Gate::define('manage-housekeeping', fn(User $u)=> $u->hasPermission('manage-housekeeping'));
   Gate::define('manage-rooms', fn(User $u)=> $u->hasPermission('manage-rooms'));
   Gate::define('manage-settings', fn(User $u)=> $u->hasRole('admin'));
 }
}

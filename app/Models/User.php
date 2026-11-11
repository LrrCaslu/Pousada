<?php
namespace App\Models;
use Illuminate\Notifications\Notifiable;
class User extends \Illuminate\Foundation\Auth\User {
 protected $table='users';
 protected $fillable=["role_id", "name", "email", "password", "active"];
 protected function casts(): array {return ['active'=>'boolean','email_verified_at'=>'datetime'];}
 
 use Notifiable;
 protected $hidden=['password','remember_token'];
 public function role(){return $this->belongsTo(Role::class);}
 public function guest(){return $this->hasOne(Guest::class);}
 public function hasRole(string $slug): bool {return $this->role?->slug === $slug;}
 public function hasAnyRole(array $slugs): bool {return in_array($this->role?->slug,$slugs,true);}
 public function hasPermission(string $ability): bool {return $this->hasRole('admin') || (bool) $this->role?->permissions()->where('slug',$ability)->exists();}
 public function getAuthPasswordName(){return 'password';}

}

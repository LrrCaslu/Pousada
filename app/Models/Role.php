<?php
namespace App\Models;
class Role extends \Illuminate\Database\Eloquent\Model {
 protected $table='roles';
 protected $fillable=["slug", "name"];
 protected function casts(): array {return [];}
 
 public function users(){return $this->hasMany(User::class);}
 public function permissions(){return $this->belongsToMany(Permission::class,'permission_role');}

}

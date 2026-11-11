<?php
namespace App\Models;
class Guest extends \Illuminate\Database\Eloquent\Model {
 protected $table='guests';
 protected $fillable=["user_id", "name", "email", "phone", "document", "notes"];
 protected function casts(): array {return [];}
 
 public function user(){return $this->belongsTo(User::class);}
 public function reservations(){return $this->hasMany(Reservation::class);}

}

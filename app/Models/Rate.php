<?php
namespace App\Models;
class Rate extends \Illuminate\Database\Eloquent\Model {
 protected $table='rates';
 protected $fillable=["room_type_id", "date", "price"];
 protected function casts(): array {return [];}
 
}

<?php
namespace App\Models;
class Room extends \Illuminate\Database\Eloquent\Model {
 protected $table='rooms';
 protected $fillable=["room_type_id", "number", "floor", "housekeeping_status", "active", "notes"];
 protected function casts(): array {return ['active'=>'boolean'];}
 
 public function type(){return $this->belongsTo(RoomType::class,'room_type_id');}
 public function blocks(){return $this->hasMany(RoomBlock::class);}

}

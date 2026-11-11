<?php
namespace App\Models;
class RoomBlock extends \Illuminate\Database\Eloquent\Model {
 protected $table='room_blocks';
 protected $fillable=["room_id", "start_date", "end_date", "reason", "notes"];
 protected function casts(): array {return ['start_date'=>'date','end_date'=>'date'];}
 
 public function room(){return $this->belongsTo(Room::class);}

}

<?php
namespace App\Models;
class HousekeepingTask extends \Illuminate\Database\Eloquent\Model {
 protected $table='housekeeping_tasks';
 protected $fillable=["room_id", "assigned_to", "status", "notes", "completed_at"];
 protected function casts(): array {return ['completed_at'=>'datetime'];}
 
 public function room(){return $this->belongsTo(Room::class);}

}

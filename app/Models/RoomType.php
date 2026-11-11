<?php
namespace App\Models;
class RoomType extends \Illuminate\Database\Eloquent\Model {
 protected $table='room_types';
 protected $fillable=["name", "slug", "description", "capacity", "beds", "bathrooms", "base_rate", "cover_image", "view", "active"];
 protected function casts(): array {return ['active'=>'boolean','base_rate'=>'decimal:2'];}
 
 public function rooms(){return $this->hasMany(Room::class);}
 public function amenities(){return $this->belongsToMany(Amenity::class,'room_amenities');}
 public function images(){return $this->hasMany(RoomImage::class);}

}

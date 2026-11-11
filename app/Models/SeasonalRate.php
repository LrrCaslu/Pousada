<?php
namespace App\Models;
class SeasonalRate extends \Illuminate\Database\Eloquent\Model {
 protected $table='seasonal_rates';
 protected $fillable=["room_type_id", "name", "start_date", "end_date", "price", "priority"];
 protected function casts(): array {return [];}
 
}

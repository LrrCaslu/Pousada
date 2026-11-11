<?php
namespace App\Services;
use App\Models\{Room,Rate,SeasonalRate,Coupon};
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
class PricingService {
 public static function cents(string|int|float $money):int {
   $value=number_format((float)$money,2,'.','');
   [$reais,$centavos]=explode('.',$value);
   return ((int)$reais)*100+(int)$centavos;
 }
 public static function money(int $cents):string {return number_format($cents/100,2,'.','');}
 public function quote(Room $room,string $start,string $end,?string $couponCode=null):array {
   $from=CarbonImmutable::parse($start)->startOfDay();$to=CarbonImmutable::parse($end)->startOfDay();
   if($to->lessThanOrEqualTo($from)||$from->lessThan(today())) throw ValidationException::withMessages(['check_in'=>'Informe datas futuras válidas.']);
   if($from->diffInDays($to)>90) throw ValidationException::withMessages(['check_out'=>'Máximo de 90 diárias por reserva.']);
   $daily=[];$base=0;$type=$room->type;
   for($day=$from;$day->lessThan($to);$day=$day->addDay()) {
      $date=$day->toDateString();
      $rate=Rate::where('room_type_id',$type->id)->whereDate('date',$date)->first();
      $season=$rate?null:SeasonalRate::where('room_type_id',$type->id)->whereDate('start_date','<=',$date)->whereDate('end_date','>=',$date)->orderByDesc('priority')->orderByDesc('id')->first();
      $price=(string)($rate?->price??$season?->price??$type->base_rate);
      $cents=self::cents($price);$base+=$cents;
      $daily[]=['date'=>$date,'rate'=>self::money($cents),'rule'=>$rate?'data':($season?'temporada':'padrao')];
   }
   $discount=0;$coupon=null;
   if($couponCode){
      $coupon=Coupon::where('code',strtoupper(trim($couponCode)))->where('active',true)->first();
      if(!$coupon||($coupon->starts_at&&$coupon->starts_at>$from->toDateString())||($coupon->ends_at&&$coupon->ends_at<$from->toDateString())) throw ValidationException::withMessages(['coupon_code'=>'Cupom inválido ou fora do prazo.']);
      $discount=$coupon->type==='percent'?intdiv($base*min(100,(int)$coupon->value),100):self::cents($coupon->value);
      $discount=min($base,$discount);
   }
   return ['base_total'=>self::money($base),'discount_total'=>self::money($discount),'total'=>self::money($base-$discount),'nights'=>count($daily),'daily'=>$daily,'coupon_code'=>$coupon?->code];
 }
}

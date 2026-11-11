<?php
namespace App\Services;
use App\Models\{Room,Reservation,Guest,Payment};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;
class ReservationService {
 public function __construct(private AvailabilityService $availability,private PricingService $pricing){}
 public function create(array $input,?int $userId=null):Reservation {
   $from=$input['check_in'];$to=$input['check_out'];
   $checkin=CarbonImmutable::parse($from)->startOfDay();$checkout=CarbonImmutable::parse($to)->startOfDay();
   if($checkin->lessThan(today())||$checkout->lessThanOrEqualTo($checkin))throw ValidationException::withMessages(['check_out'=>'Datas inválidas.']);
   $persons=(int)$input['adults']+(int)($input['children']??0);
   $reservation=DB::transaction(function()use($input,$from,$to,$persons,$userId){
      // A row lock on the physical room serializes competing reservations for that room.
      $room=Room::with('type')->whereKey($input['room_id'])->lockForUpdate()->firstOrFail();
      if($room->type->capacity<$persons || !$this->availability->isAvailableLocked($room,$from,$to))
        throw ValidationException::withMessages(['room_id'=>'A acomodação está indisponível ou não comporta os hóspedes.']);
      $quote=$this->pricing->quote($room,$from,$to,$input['coupon_code']??null);
      $guest=$userId?Guest::firstOrCreate(['user_id'=>$userId],['email'=>$input['email'],'name'=>$input['name'],'phone'=>$input['phone']??null]):Guest::create(['email'=>$input['email'],'name'=>$input['name'],'phone'=>$input['phone']??null]);
      if($userId && !$guest->user_id)$guest->update(['user_id'=>$userId]);
      $reservation=Reservation::create(['code'=>'CS-'.strtoupper(Str::random(9)),'guest_id'=>$guest->id,
         'check_in'=>$from,'check_out'=>$to,'adults'=>$input['adults'],'children'=>$input['children']??0,
         'status'=>'aguardando_pagamento','expires_at'=>now()->addMinutes(30),
         'payment_method'=>$input['payment_method']??'presencial','base_total'=>$quote['base_total'],
         'discount_total'=>$quote['discount_total'],'total'=>$quote['total'],'price_breakdown'=>$quote,
         'coupon_code'=>$quote['coupon_code'],'notes'=>$input['notes']??null]);
      $reservation->rooms()->attach($room->id,['total'=>$quote['total']]);
      DB::table('reservation_guests')->insert(['reservation_id'=>$reservation->id,'guest_id'=>$guest->id]);
      DB::table('reservation_status_history')->insert(['reservation_id'=>$reservation->id,'user_id'=>auth()->id(),'to_status'=>'aguardando_pagamento','created_at'=>now(),'updated_at'=>now()]);
      Payment::create(['reservation_id'=>$reservation->id,'method'=>$input['payment_method']??'presencial','provider'=>'simulado','status'=>'pendente','amount'=>$quote['total'],'reference'=>'SIM-'.strtoupper(Str::random(12))]);
      AuditService::record('reserva.criada','reservation',$reservation->id);
      return $reservation;
   },3);
   try {Mail::raw('Reserva '.$reservation->code.' registrada para '.$from.' a '.$to.'. Pagamento local simulado.',function($m)use($input){$m->to($input['email'])->subject('Reserva '.$input['name']);});}catch(\Throwable $e){report($e);}
   return $reservation->load('rooms.type','payments','guest');
 }
 private const TRANSITIONS=[
  'pendente'=>['aguardando_pagamento','confirmada','cancelada'],
  'aguardando_pagamento'=>['confirmada','cancelada'],
  'confirmada'=>['checkin','cancelada','nao_compareceu'],
  'checkin'=>['checkout'], 'checkout'=>[], 'cancelada'=>[], 'nao_compareceu'=>[],
 ];
 public function transition(Reservation $reservation,string $to,?string $note=null):Reservation {
  return DB::transaction(function()use($reservation,$to,$note){
    $r=Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
    if(!in_array($to,self::TRANSITIONS[$r->status]??[],true))throw ValidationException::withMessages(['status'=>'Transição de estado não permitida.']);
    if($to==='confirmada'){
      if($r->expires_at&&$r->expires_at->isPast()&&in_array($r->status,['pendente','aguardando_pagamento']))throw ValidationException::withMessages(['status'=>'Reserva expirada.']);
      foreach($r->rooms()->orderBy('rooms.id')->get() as $reservedRoom){
        $lock=Room::with('type')->whereKey($reservedRoom->id)->lockForUpdate()->firstOrFail();
        if(!$this->availability->isAvailableLocked($lock,$r->check_in->toDateString(),$r->check_out->toDateString(),$r->id)) throw ValidationException::withMessages(['status'=>'Quarto já não está disponível.']);
      }
    }
    $old=$r->status;$r->update(['status'=>$to]);
    DB::table('reservation_status_history')->insert(['reservation_id'=>$r->id,'user_id'=>auth()->id(),'from_status'=>$old,'to_status'=>$to,'comment'=>$note,'created_at'=>now(),'updated_at'=>now()]);
    AuditService::record('reserva.status','reservation',$r->id,['from'=>$old,'to'=>$to]);
    return $r;
  },3);
 }
}

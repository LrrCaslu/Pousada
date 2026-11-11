<?php
namespace App\Http\Controllers;
use App\Models\{RoomType,Room,Page,Setting};
use App\Services\{AvailabilityService,PricingService,ReservationService};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class SiteController extends Controller {
 public function home(){return view('home',['types'=>RoomType::where('active',true)->withCount('rooms')->take(5)->get()]);}
 public function rooms(Request $r,AvailabilityService $availability){
   $types=RoomType::withCount('rooms')->where('active',true)->when($r->filled('capacity'),fn($q)=>$q->where('capacity','>=',$r->integer('capacity')))->when($r->filled('max_price'),fn($q)=>$q->where('base_rate','<=',$r->input('max_price')))->get();
   if($r->filled('check_in')&&$r->filled('check_out')){
     $d=$r->validate(['check_in'=>'required|date|after_or_equal:today','check_out'=>'required|date|after:check_in','adults'=>'nullable|integer|min:1|max:20','children'=>'nullable|integer|min:0|max:20']);
     $availableIds=$availability->available($d['check_in'],$d['check_out'],(int)($d['adults']??1)+(int)($d['children']??0))->pluck('room_type_id')->unique();
     $types=$types->whereIn('id',$availableIds);
   }
   return view('rooms.index',compact('types'));
 }
 public function room(RoomType $type){abort_unless($type->active,404);return view('rooms.show',['type'=>$type->load('rooms','amenities','images')]);}
 public function bookingForm(Request $r,RoomType $type,AvailabilityService $availability){
  $d=$r->validate(['check_in'=>'required|date|after_or_equal:today','check_out'=>'required|date|after:check_in','adults'=>'nullable|integer|min:1|max:20','children'=>'nullable|integer|min:0|max:20']);
  $rooms=$availability->available($d['check_in'],$d['check_out'],(int)($d['adults']??1)+(int)($d['children']??0))->where('room_type_id',$type->id)->get();
  return view('booking.form',compact('type','rooms','d'));
 }
 public function book(Request $r,ReservationService $service){
   $d=$r->validate(['room_id'=>'required|exists:rooms,id','check_in'=>'required|date|after_or_equal:today','check_out'=>'required|date|after:check_in','adults'=>'required|integer|min:1|max:20','children'=>'nullable|integer|min:0|max:20','name'=>'required|string|max:120','email'=>'required|email|max:180','phone'=>'nullable|string|max:30','coupon_code'=>'nullable|string|max:32','payment_method'=>'required|in:pix,cartao,presencial,transferencia']);
   if($r->user()){$d['name']=$r->user()->name;$d['email']=$r->user()->email;}
   $reservation=$service->create($d,$r->user()?->id);
   return redirect()->route('booking.receipt',$reservation->code)->with('booking_token',$reservation->id);
 }
 public function receipt(string $code,Request $r){
   $reservation=\App\Models\Reservation::where('code',$code)->with('rooms.type','payments','guest')->firstOrFail();
   $owner=$r->user()&&$reservation->guest->user_id===$r->user()->id;
   abort_unless($owner||session('booking_token')===$reservation->id,403);
   return view('booking.receipt',compact('reservation'));
 }
 public function page(string $slug){return view('page',['page'=>Page::where('slug',$slug)->where('published',true)->firstOrFail()]);}
 public function contact(){return view('contact');}
}

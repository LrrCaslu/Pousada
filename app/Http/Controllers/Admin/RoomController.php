<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Room,RoomType,RoomBlock};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class RoomController extends Controller {
 public function index(){return view('admin.rooms.index',['types'=>RoomType::with('rooms','amenities')->orderBy('name')->get(),'rooms'=>Room::with('type','blocks')->orderBy('number')->get(),'blocks'=>RoomBlock::with('room')->latest()->take(20)->get()]);}
 public function storeType(Request $r){
  $d=$r->validate(['name'=>'required|string|max:120|unique:room_types','description'=>'nullable|string|max:3000','capacity'=>'required|integer|min:1|max:20','beds'=>'required|integer|min:1|max:15','bathrooms'=>'required|integer|min:1|max:10','base_rate'=>'required|numeric|min:0.01|max:999999','view'=>'nullable|string|max:120']);
  $d['slug']=Str::slug($d['name']).'-'.Str::lower(Str::random(4));$t=RoomType::create($d);AuditService::record('categoria.criada','room_type',$t->id);return back()->with('success','Categoria criada.');
 }
 public function updateType(Request $r,RoomType $type){
  $d=$r->validate(['name'=>'required|string|max:120','description'=>'nullable|string|max:3000','capacity'=>'required|integer|min:1|max:20','beds'=>'required|integer|min:1|max:15','bathrooms'=>'required|integer|min:1|max:10','base_rate'=>'required|numeric|min:0.01|max:999999','view'=>'nullable|string|max:120','active'=>'required|boolean']);
  $type->update($d);AuditService::record('categoria.atualizada','room_type',$type->id);return back()->with('success','Categoria atualizada.');
 }
 public function store(Request $r){
  $d=$r->validate(['room_type_id'=>'required|exists:room_types,id','number'=>'required|string|max:30|unique:rooms,number','floor'=>'nullable|string|max:20','housekeeping_status'=>'required|in:limpo,aguardando_limpeza,em_limpeza,manutencao']);
  $room=Room::create($d);AuditService::record('quarto.criado','room',$room->id);return back()->with('success','Quarto cadastrado.');
 }
 public function update(Request $r,Room $room){
  $d=$r->validate(['room_type_id'=>'required|exists:room_types,id','number'=>'required|string|max:30|unique:rooms,number,'.$room->id,'floor'=>'nullable|string|max:20','housekeeping_status'=>'required|in:limpo,aguardando_limpeza,em_limpeza,manutencao','active'=>'required|boolean']);
  $room->update($d);AuditService::record('quarto.atualizado','room',$room->id);return back()->with('success','Quarto atualizado.');
 }
 public function block(Request $r){
  $d=$r->validate(['room_id'=>'required|exists:rooms,id','start_date'=>'required|date','end_date'=>'required|date|after:start_date','reason'=>'required|in:manutencao,bloqueio','notes'=>'nullable|string|max:1000']);
  $b=\Illuminate\Support\Facades\DB::transaction(function() use($d){
   Room::whereKey($d['room_id'])->lockForUpdate()->firstOrFail();
   $conflict=\Illuminate\Support\Facades\DB::table('reservation_rooms as rr')->join('reservations as r','r.id','=','rr.reservation_id')->where('rr.room_id',$d['room_id'])->where('r.check_in','<',$d['end_date'])->where('r.check_out','>',$d['start_date'])->whereIn('r.status',['confirmada','checkin'])->exists();
   if($conflict)throw \Illuminate\Validation\ValidationException::withMessages(['start_date'=>'Período já tem reserva confirmada; resolva a realocação antes de bloquear.']);
   return RoomBlock::create($d);
  },3);
  AuditService::record('quarto.bloqueado','room',$b->room_id);return back()->with('success','Período bloqueado.');
 }
 public function unblock(RoomBlock $block){AuditService::record('quarto.desbloqueado','room',$block->room_id);$block->delete();return back()->with('success','Bloqueio removido.');}
 public function image(Request $r,RoomType $type){
  $r->validate(['image'=>'required|image|mimes:jpg,jpeg,png,webp|max:4096']);
  $type->update(['cover_image'=>$r->file('image')->store('rooms','public')]);
  AuditService::record('categoria.imagem','room_type',$type->id);return back()->with('success','Imagem enviada.');
 }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use App\Models\{User,Role,Guest,RoomType,Room,Reservation,Payment,Expense,HousekeepingTask,Page,Setting};
class DemoSeeder extends Seeder {
 public function run():void {
  mt_srand(260426);
  $roleLabels=['admin'=>'Administrador','recepcao'=>'Recepcionista','financeiro'=>'Financeiro','governanca'=>'Governança','hospede'=>'Hóspede'];
  $roles=[];foreach($roleLabels as $slug=>$name)$roles[$slug]=Role::firstOrCreate(['slug'=>$slug],['name'=>$name]);
  foreach(['manage-reservations','manage-finance','manage-housekeeping','manage-rooms','manage-settings'] as $ability){$pid=DB::table('permissions')->where('slug',$ability)->value('id') ?? DB::table('permissions')->insertGetId(['slug'=>$ability,'name'=>$ability]);foreach($roles as $roleName=>$role){if($roleName==='admin'||match($ability){'manage-reservations','manage-rooms'=>$roleName==='recepcao','manage-finance'=>$roleName==='financeiro','manage-housekeeping'=>in_array($roleName,['governanca','recepcao']),default=>false})DB::table('permission_role')->insertOrIgnore(['role_id'=>$role->id,'permission_id'=>$pid]);}}
  $accounts=[['admin','admin@costaserena.test','Admin@12345'],['recepcao','recepcao@costaserena.test','Recep@12345'],['financeiro','financeiro@costaserena.test','Finan@12345'],['governanca','governanca@costaserena.test','Gover@12345'],['hospede','hospede@costaserena.test','Hosp@12345']];
  foreach($accounts as [$role,$email,$pass]){$u=User::updateOrCreate(['email'=>$email],['name'=>$roleLabels[$role].' Teste','role_id'=>$roles[$role]->id,'password'=>Hash::make($pass),'active'=>true]);if($role==='hospede')Guest::firstOrCreate(['user_id'=>$u->id],['name'=>'Hóspede Demonstração','email'=>$email,'phone'=>'(48) 99999-0000']);}
  $names=['Suíte Jardim','Suíte Oceano','Quarto Varanda','Suíte Família','Chalé Premium'];
  $capacities=[2,2,3,5,4];$prices=['290.00','390.00','340.00','590.00','740.00'];$counts=[3,3,2,2,2];$types=[];$rooms=[];$n=101;
  foreach($names as $i=>$name){
    $t=RoomType::firstOrCreate(['slug'=>'tipo-'.($i+1)],['name'=>$name,'description'=>'Acomodação confortável com decoração acolhedora, enxoval completo e experiência boutique.','capacity'=>$capacities[$i],'beds'=>($i>2?2:1),'bathrooms'=>1,'base_rate'=>$prices[$i],'view'=>$i%2?'Mar':'Jardim','active'=>true]);$types[]=$t;
    for($j=0;$j<$counts[$i];$j++){$rooms[]=Room::firstOrCreate(['number'=>(string)$n++],['room_type_id'=>$t->id,'floor'=>'1','housekeeping_status'=>'limpo','active'=>true]);}
  }
  // Temporadas e cupom fictícios, de acordo com a data em que o seed é executado.
  foreach($types as $t) {
   $start=now()->startOfMonth()->addMonth()->toDateString();
   $end=now()->startOfMonth()->addMonths(3)->toDateString();
   \App\Models\SeasonalRate::firstOrCreate(['room_type_id'=>$t->id,'name'=>'Alta temporada demo'],['start_date'=>$start,'end_date'=>$end,'price'=>number_format((float)$t->base_rate*1.25,2,'.',''),'priority'=>10]);
  }
  \App\Models\Coupon::firstOrCreate(['code'=>'BEMVINDO10'],['type'=>'percent','value'=>10,'active'=>true]);
  $amenities=['Wi-Fi','Ar-condicionado','Televisão','Frigobar','Café da manhã','Vista para o jardim','Banheiro privativo'];
  foreach($amenities as $a){$id=DB::table('amenities')->where('name',$a)->value('id') ?? DB::table('amenities')->insertGetId(['name'=>$a,'icon'=>'check']);foreach($types as $t)DB::table('room_amenities')->insertOrIgnore(['room_type_id'=>$t->id,'amenity_id'=>$id]);}
  $guests=[];for($i=1;$i<=19;$i++)$guests[]=Guest::firstOrCreate(['email'=>sprintf('hospede%02d@exemplo.test',$i)],['name'=>'Hóspede Fictício '.str_pad($i,2,'0',STR_PAD_LEFT),'phone'=>'(48) 99999-'.str_pad($i,4,'0',STR_PAD_LEFT)]);
  $guests[]=Guest::where('email','hospede@costaserena.test')->firstOrFail();
  // Recurring room slots are 84 days apart. Each booking is 2-5 days, so physical-room overlap is impossible.
  for($i=0;$i<30;$i++){
    $room=$rooms[$i%count($rooms)];$start=now()->startOfDay()->addDays(-90+$i*7);$end=$start->copy()->addDays(2+($i%4));
    $status=$end->isPast()?'checkout':($start->isPast()&&$end->isFuture()?'checkin':(['confirmada','aguardando_pagamento','cancelada'][$i%3]));
    $nightCount=$start->diffInDays($end);$total=number_format((float)$room->type->base_rate*$nightCount,2,'.','');
    $res=Reservation::firstOrCreate(['code'=>sprintf('DEMO-%04d',$i+1)],['guest_id'=>$guests[$i%20]->id,'check_in'=>$start->toDateString(),'check_out'=>$end->toDateString(),'adults'=>min(2,$room->type->capacity),'children'=>0,'status'=>$status,'expires_at'=>in_array($status,['pendente','aguardando_pagamento'])?now()->addDays(180):null,'payment_method'=>'pix','base_total'=>$total,'discount_total'=>0,'total'=>$total,'price_breakdown'=>['nights'=>$nightCount,'base_total'=>$total,'total'=>$total],'currency'=>'BRL']);
    DB::table('reservation_rooms')->insertOrIgnore(['reservation_id'=>$res->id,'room_id'=>$room->id,'total'=>$total]);
    DB::table('reservation_guests')->insertOrIgnore(['reservation_id'=>$res->id,'guest_id'=>$guests[$i%20]->id]);
    Payment::firstOrCreate(['reference'=>sprintf('SIM-DEMO-%04d',$i+1)],['reservation_id'=>$res->id,'method'=>'pix','provider'=>'simulado','status'=>in_array($status,['confirmada','checkin','checkout'])?'aprovado':'pendente','amount'=>$total,'paid_at'=>in_array($status,['confirmada','checkin','checkout'])?$start->copy()->subDays(2):null]);
  }
  foreach([['Lavanderia','Operação','480.00'],['Manutenção jardim','Manutenção','250.00'],['Materiais de limpeza','Governança','175.00']] as [$desc,$cat,$amount])Expense::firstOrCreate(['description'=>$desc],['category'=>$cat,'amount'=>$amount,'date'=>now()->subDays(3)->toDateString()]);
  foreach(array_slice($rooms,0,5) as $room)HousekeepingTask::firstOrCreate(['room_id'=>$room->id,'notes'=>'Tarefa fictícia de demonstração'],['status'=>'pendente']);
  if (!DB::table('maintenance_records')->where('room_id', $rooms[8]->id)->where('description', 'Vistoria preventiva demonstrativa')->exists()) DB::table('maintenance_records')->insert(['room_id'=>$rooms[8]->id,'description'=>'Vistoria preventiva demonstrativa','status'=>'aberta','created_at'=>now(),'updated_at'=>now()]);
  foreach(['pousada_nome'=>'Pousada Costa Serena','descricao'=>'Tranquilidade, natureza e hospitalidade em cada detalhe.','endereco'=>'Endereço fictício — Costa Serena, SC','telefone'=>'(48) 3000-0000','whatsapp'=>'554830000000','email'=>'contato@costaserena.test','instagram'=>'@costaserena.demo','checkin_hora'=>'14:00','checkout_hora'=>'11:00','politica_cancelamento'=>'Cancelamento demonstrativo sujeito às regras da reserva.'] as $k=>$v)Setting::firstOrCreate(['key'=>$k],['value'=>$v]);
  $pages=['sobre'=>'Sobre a pousada','estrutura'=>'Estrutura e serviços','galeria'=>'Galeria','experiencias'=>'Experiências e atrações','localizacao'=>'Localização','faq'=>'Perguntas frequentes','cancelamento'=>'Política de cancelamento','termos'=>'Termos de uso','privacidade'=>'Política de privacidade'];
  foreach($pages as $slug=>$title)Page::firstOrCreate(['slug'=>$slug],['title'=>$title,'body'=>'Conteúdo fictício de demonstração. Atualize no painel administrativo para publicar informações reais.']);
  if(DB::table('reviews')->count()===0)foreach(['Estadia tranquila e acolhedora.','Equipe atenciosa e acomodações confortáveis.','Ótima experiência em família.'] as $text)DB::table('reviews')->insert(['guest_id'=>$guests[0]->id,'rating'=>5,'content'=>$text,'approved'=>true,'demo'=>true,'created_at'=>now(),'updated_at'=>now()]);
 }
}

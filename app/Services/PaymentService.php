<?php
namespace App\Services;
use App\Models\{Payment,Reservation};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PaymentService {
 public function __construct(private ReservationService $reservations){}
 public function simulate(Payment $payment,string $result):Payment {
   if(!in_array($result,['aprovado','recusado','pendente','estornado'],true))throw ValidationException::withMessages(['status'=>'Resultado inválido.']);
   return DB::transaction(function()use($payment,$result){
     $p=Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
     if(!in_array($p->status,['pendente','aprovado'],true))throw ValidationException::withMessages(['status'=>'Pagamento já finalizado.']);
     if($result==='estornado'&&$p->status!=='aprovado')throw ValidationException::withMessages(['status'=>'Somente pagamentos aprovados podem ser estornados.']);
     if($result==='aprovado'&&$p->status==='pendente'){
       $r=Reservation::whereKey($p->reservation_id)->firstOrFail();
       $this->reservations->transition($r,'confirmada');
     }
     if($result==='estornado')DB::table('refunds')->insert(['payment_id'=>$p->id,'amount'=>$p->amount,'status'=>'simulado','reason'=>'Estorno local de demonstração','created_at'=>now(),'updated_at'=>now()]);
     $p->update(['status'=>$result,'paid_at'=>$result==='aprovado'?now():$p->paid_at]);
     AuditService::record('pagamento.simulado','payment',$p->id,['result'=>$result]);
     return $p;
   },3);
 }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Expense,Payment};
use App\Services\{PaymentService,AuditService};
use Illuminate\Http\Request;
class FinanceController extends Controller {
 public function index(){return view('admin.finance.index',['payments'=>Payment::with('reservation.guest')->latest()->paginate(12),'expenses'=>Expense::latest()->take(15)->get(), 'received'=>Payment::where('status','aprovado')->sum('amount'),'pending'=>Payment::where('status','pendente')->sum('amount'),'spent'=>Expense::sum('amount')]);}
 public function expense(Request $r){$d=$r->validate(['description'=>'required|string|max:180','category'=>'nullable|string|max:60','amount'=>'required|numeric|min:0.01|max:10000000','date'=>'required|date']);$d['created_by']=auth()->id();$e=Expense::create($d);AuditService::record('despesa.criada','expense',$e->id);return back()->with('success','Despesa registrada.');}
 public function simulate(Request $r,Payment $payment,PaymentService $service){$d=$r->validate(['result'=>'required|in:aprovado,recusado,pendente,estornado']);$service->simulate($payment,$d['result']);return back()->with('success','Transação simulada, sem movimentação bancária.');}
 public function export(){
  $file='financeiro-'.now()->format('Ymd').'.csv';
  return response()->streamDownload(function(){ $out=fopen('php://output','w');fputcsv($out,['referencia','reserva','status','metodo','valor','data'],';');Payment::with('reservation')->orderBy('id')->chunk(200,function($payments)use($out){foreach($payments as $p)fputcsv($out,[$p->reference,$p->reservation->code,$p->status,$p->method,$p->amount,$p->created_at],';');});fclose($out);},$file,['Content-Type'=>'text/csv; charset=UTF-8']);
 }
}

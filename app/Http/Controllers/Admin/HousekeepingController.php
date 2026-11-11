<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Room,HousekeepingTask};
use App\Services\AuditService;
use Illuminate\Http\Request;
class HousekeepingController extends Controller {
 public function index(){return view('admin.housekeeping.index',['tasks'=>HousekeepingTask::with('room.type')->latest()->paginate(20),'rooms'=>Room::with('type')->orderBy('number')->get()]);}
 public function store(Request $r){$d=$r->validate(['room_id'=>'required|exists:rooms,id','notes'=>'nullable|string|max:1000']);$t=HousekeepingTask::create($d);Room::findOrFail($d['room_id'])->update(['housekeeping_status'=>'aguardando_limpeza']);AuditService::record('limpeza.criada','housekeeping_task',$t->id);return back()->with('success','Tarefa criada.');}
 public function update(Request $r,HousekeepingTask $task){$d=$r->validate(['status'=>'required|in:pendente,em_andamento,concluida','notes'=>'nullable|string|max:1000']);$task->update(['status'=>$d['status'],'notes'=>$d['notes']??$task->notes,'completed_at'=>$d['status']==='concluida'?now():null]);$task->room->update(['housekeeping_status'=>match($d['status']){'concluida'=>'limpo','em_andamento'=>'em_limpeza',default=>'aguardando_limpeza'}]);AuditService::record('limpeza.atualizada','housekeeping_task',$task->id);return back()->with('success','Tarefa atualizada.');}
}

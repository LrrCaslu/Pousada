<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class AuditService {
 public static function record(string $action,?string $subject=null,?int $subjectId=null,array $details=[]):void {
  DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>$action,'subject'=>$subject,'subject_id'=>$subjectId,'details'=>json_encode($details,JSON_UNESCAPED_UNICODE),'ip'=>request()->ip(),'created_at'=>now(),'updated_at'=>now()]);
 }
}

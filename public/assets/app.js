document.addEventListener('DOMContentLoaded',()=>{
 const toggle=document.querySelector('#navToggle');if(toggle)toggle.addEventListener('click',()=>document.querySelector('.sidebar')?.classList.toggle('open'));
 document.querySelectorAll('input[name="check_in"]').forEach(input=>input.addEventListener('change',()=>{
  const f=input.closest('form');const out=f?.querySelector('input[name="check_out"]');if(out&&input.value){out.min=input.value;if(out.value<=input.value){const d=new Date(input.value+'T12:00:00');d.setDate(d.getDate()+1);out.value=[d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');}}
 }));
});

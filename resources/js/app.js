import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';
window.Alpine=Alpine;Alpine.start();
createIcons({icons,attrs:{'stroke-width':1.8}});
Chart.register(BarController,BarElement,CategoryScale,LinearScale,Tooltip,Legend);
const nav=document.querySelector('#navToggle');if(nav)nav.addEventListener('click',()=>document.querySelector('.sidebar')?.classList.toggle('open'));
const chart=document.querySelector('#finance-chart');if(chart){new Chart(chart,{type:'bar',data:{labels:['Recebido','Despesas'],datasets:[{label:'Valores de demonstração (R$)',data:[Number(chart.dataset.received),Number(chart.dataset.expenses)],backgroundColor:['#254A40','#C5A46D'],borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});}
document.querySelectorAll('input[name="check_in"]').forEach(input=>input.addEventListener('change',()=>{const out=input.closest('form')?.querySelector('input[name="check_out"]');if(out&&input.value){out.min=input.value;if(out.value<=input.value){const d=new Date(input.value+'T12:00:00');d.setDate(d.getDate()+1);out.value=d.toISOString().slice(0,10);}}}));

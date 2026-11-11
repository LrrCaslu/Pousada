// Galeria demonstrativa com ampliacao nativa, sem dependencias externas.
function initCostaSerenaGallery() {
 const dialog = document.getElementById('galleryDialog');
 if (!dialog || typeof dialog.showModal !== 'function') return;
 const image = document.getElementById('galleryDialogImage');
 const caption = document.getElementById('galleryDialogCaption');
 document.querySelectorAll('[data-gallery-image]').forEach(button => button.addEventListener('click',()=>{
   image.src = button.dataset.galleryImage;
   image.alt = button.dataset.galleryLabel || 'Fotografia ilustrativa da pousada';
   caption.textContent = button.dataset.galleryLabel || '';
   dialog.showModal();
 }));
 dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initCostaSerenaGallery);
else initCostaSerenaGallery();

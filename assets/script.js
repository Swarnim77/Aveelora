
console.log('Aveelora loaded');
document.addEventListener('DOMContentLoaded', function(){
  if(window.Aveelora && window.Aveelora.initFilters){
    const search = document.getElementById('searchInput');
    const cat = document.getElementById('catFilter');
    const price = document.getElementById('priceFilter');
    const cards = Array.from(document.querySelectorAll('#productGrid .card'));

    function applyFilters(){
      const q = (search.value || '').trim().toLowerCase();
      const c = (cat.value || '');
      const p = (price.value || '');
      let prmin = 0, prmax = Infinity;
      if(p){
        const parts = p.split('-'); prmin = Number(parts[0]); prmax = Number(parts[1] || Infinity);
      }
      cards.forEach(card => {
        const name = card.dataset.name || '';
        const category = card.dataset.category || '';
        const priceVal = Number(card.dataset.price || 0);
        const okName = q === '' || name.indexOf(q) !== -1;
        const okCat = c === '' || category === c;
        const okPrice = priceVal >= prmin && priceVal <= prmax;
        if(okName && okCat && okPrice) card.style.display = '';
        else card.style.display = 'none';
      });
    }

    [search, cat, price].forEach(el => el && el.addEventListener('input', applyFilters));
  }
});

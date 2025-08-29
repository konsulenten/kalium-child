document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.elementor-portfolio__filters').forEach(function(filterBar) {
        filterBar.addEventListener('click', function(e) {
            if (!e.target.classList.contains('elementor-portfolio__filter')) return;
            filterBar.querySelectorAll('.elementor-portfolio__filter').forEach(f => f.classList.remove('elementor-active'));
            e.target.classList.add('elementor-active');
            var filter = e.target.getAttribute('data-filter');
            // FIXED LINE:
            var grid = filterBar.parentElement.querySelector('.elementor-portfolio');
            if (!grid) return;
            grid.querySelectorAll('.elementor-portfolio-item').forEach(function(item) {
                if (filter === '__all' || item.classList.contains('elementor-filter-' + filter)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});


document.addEventListener('DOMContentLoaded', function() {
    const ul = document.querySelector('.elementor-portfolio__filters');
    if (!ul) return;
    ul.classList.add('filters-animate');
    const filters = ul.querySelectorAll('.elementor-portfolio__filter');
    filters.forEach((el, i) => {
      el.style.transitionDelay = (i * 60) + "ms";
      setTimeout(() => {
        el.style.opacity = 1;
        el.style.transform = 'translateY(0)';
      }, 150 + i * 60);
    });
  
    // Aktiv status
    filters.forEach(el => {
      el.addEventListener('click', function() {
        filters.forEach(f => f.classList.remove('elementor-active'));
        el.classList.add('elementor-active');
      });
    });
  });
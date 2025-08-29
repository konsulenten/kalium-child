document.addEventListener("DOMContentLoaded", function() {
    // Original functionality - move "Alle" filter to end
    var ul = document.querySelector('.portfolio-filters__terms');
    if (!ul) return;
    var alle = ul.querySelector('.portfolio-filters__term--reset');
    if (alle) ul.appendChild(alle); // Move to end

    // Wait for Elementor to load, then add hash functionality
    function initHashHandling() {
        // Try both possible selectors for filters
        let filterButtons = document.querySelectorAll('.elementor-portfolio__filter');
        
        if (filterButtons.length === 0) {
            // Try alternative selectors
            filterButtons = document.querySelectorAll('.portfolio-filters__term');
        }
        
        console.log('Found filter buttons:', filterButtons.length);
        
        if (filterButtons.length === 0) {
            // Retry in 500ms if no buttons found
            setTimeout(initHashHandling, 500);
            return;
        }
        
        // Add hash to URL when filter is clicked (but don't interfere with filtering)
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Small delay to let Elementor handle the filtering first
                setTimeout(() => {
                    const filterText = this.textContent.toLowerCase().trim();
                    console.log('Filter clicked, updating hash:', filterText);
                    
                    // Update URL hash based on filter
                    if (filterText !== 'alle') {
                        const slug = filterText.replace(/\s+/g, '-');
                        window.history.replaceState(null, null, '#' + slug);
                    } else {
                        // Remove hash for "Alle"
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                }, 100);
            });
        });
        
        // Restore filter from hash on page load
        const hash = window.location.hash;
        if (hash && hash.length > 1) {
            const filterSlug = hash.substring(1);
            console.log('Restoring filter:', filterSlug);
            
            setTimeout(() => {
                filterButtons.forEach(button => {
                    const buttonText = button.textContent.toLowerCase().trim().replace(/\s+/g, '-');
                    if (buttonText === filterSlug) {
                        console.log('Found matching filter button, clicking:', buttonText);
                        
                        // Let Elementor handle the filtering
                        button.click();
                        
                        // Ensure the button gets the active class
                        setTimeout(() => {
                            filterButtons.forEach(b => {
                                b.classList.remove('elementor-active');
                                b.classList.remove('active');
                            });
                            button.classList.add('elementor-active');
                        }, 50);
                    }
                });
            }, 500); // Longer delay for page load restoration
        }
    }
    
    // Try multiple times to catch Elementor loading
    setTimeout(initHashHandling, 100);
    setTimeout(initHashHandling, 1000);
    setTimeout(initHashHandling, 2000);
});

(function () {
    // Helper to set cookie for PHP side (30 minutes)
    function setFilterCookie(slug) {
      if (slug) {
        document.cookie = 'hrtb_portfolio_hash=' + encodeURIComponent(slug) + '; Max-Age=1800; Path=/';
      } else {
        document.cookie = 'hrtb_portfolio_hash=; Max-Age=0; Path=/';
      }
    }
  
    // Capture clicks on category filters (Elementor or Kalium)
    document.addEventListener('click', function (e) {
      const a = e.target.closest('.portfolio-filters__terms a, .elementor-portfolio__filter');
      if (!a) return;
  
      // Prefer data-term like "portfolio_category:plan"
      const dt = a.getAttribute('data-term') || '';
      let slug = '';
      const m = dt.match(/portfolio_category:([\w-]+)/);
      if (m && m[1]) {
        slug = m[1].toLowerCase();
      } else {
        const txt = (a.textContent || '').toLowerCase().trim();
        if (txt && txt !== 'alle') slug = txt.replace(/\s+/g, '-');
      }
  
      // Persist for PHP & single page
      if (slug) {
        sessionStorage.setItem('hrtb_portfolio_hash', slug);
        setFilterCookie(slug);
        // Keep URL as /arkitektur/#slug (no /kategori/… path)
        history.replaceState(null, '', '#'+slug);
      } else {
        sessionStorage.removeItem('hrtb_portfolio_hash');
        setFilterCookie('');
        history.replaceState(null, '', window.location.pathname.replace(/\/kategori\/[^/]+\/?$/,''));
      }
    }, true);
  
    // On archive load without hash, force “Alle” active (avoid default “utvalgte”)
    document.addEventListener('DOMContentLoaded', function () {
      if (location.pathname.endsWith('/arkitektur/') && !location.hash) {
        // If theme bootstraps with "current: utvalgte", neutralize it by virtually clicking "Alle"
        const reset = document.querySelector('.portfolio-filters__term--reset a,[data-term="portfolio_category:*"]');
        if (reset) setTimeout(() => reset.click(), 50);
      }
    });
  })();
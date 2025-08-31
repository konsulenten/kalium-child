// move-alle-filter.js  — archive hash + filter sync for /arkitektur/
(function () {
  // Only run on the archive
  function onArchive() {
    return location.pathname.endsWith('/arkitektur/');
  }

  // ---- Cookie helper (read/write) ----
  function setFilterCookie(slug) {
    if (slug) {
      document.cookie = 'hrtb_portfolio_hash=' + encodeURIComponent(slug) + '; Max-Age=1800; Path=/';
    } else {
      document.cookie = 'hrtb_portfolio_hash=; Max-Age=0; Path=/';
    }
  }

  // ---- Move "Alle" to end (once DOM exists) ----
  document.addEventListener('DOMContentLoaded', function () {
    if (!onArchive()) return;
    var ul = document.querySelector('.portfolio-filters__terms');
    if (!ul) return;
    var alle = ul.querySelector('.portfolio-filters__term--reset');
    if (alle) ul.appendChild(alle); // move reset/Alle last
  });

  // After your on-archive DOMContentLoaded check where you neutralize defaults:
document.addEventListener('DOMContentLoaded', function () {
  if (location.pathname.endsWith('/arkitektur/')) {
    if (!location.hash) {
      // mark explicit "unfiltered archive" context
      document.cookie = 'hrtb_portfolio_hash=ALL; Max-Age=1800; Path=/';
      sessionStorage.removeItem('hrtb_portfolio_hash');
      // (your existing "click reset" shim can remain)
    }
  }
});

// ---- Apply a hash filter by simulating a real click on its <a> ----
function applyHashFilterFromLocation() {
  if (!onArchive()) return;
  var h = (location.hash || '').replace(/^#/, '');

  // Normalize: no hash or "alle" → mark unfiltered archive (ALL)
  if (!h || h === '*' || h.toLowerCase() === 'alle') {
    // Clear any server-side default active (often "utvalgte")
    document.querySelectorAll('.portfolio-filters__term--active,.elementor-active,.active')
      .forEach(function (el) { el.classList.remove('portfolio-filters__term--active', 'elementor-active', 'active'); });

    // Persist explicit "unfiltered archive" context
    document.cookie = 'hrtb_portfolio_hash=ALL; Max-Age=1800; Path=/';
    sessionStorage.removeItem('hrtb_portfolio_hash');

    // Keep clean URL (no hash)
    history.replaceState(null, '', location.pathname);
    return;
  }

  var slug = h.toLowerCase();

  // 1) Clear default active state set by server (e.g., Utvalg/utvalgte)
  document.querySelectorAll('.portfolio-filters__terms .portfolio-filters__term--active')
    .forEach(function (li) { li.classList.remove('portfolio-filters__term--active'); });

  // 2) Find exact anchor by data-term (most reliable)
  var link = document.querySelector('[data-term="portfolio_category:' + slug + '"]');

  // Fallback: match by text → slug if data-term is missing
  if (!link) {
    var anchors = document.querySelectorAll('.portfolio-filters__terms a, .elementor-portfolio__filter');
    link = Array.prototype.find.call(anchors, function (a) {
      return (a.textContent || '').toLowerCase().trim().replace(/\s+/g, '-') === slug;
    });
  }

  if (link) {
    // Persist for PHP & single page
    document.cookie = 'hrtb_portfolio_hash=' + encodeURIComponent(slug) + '; Max-Age=1800; Path=/';
    sessionStorage.setItem('hrtb_portfolio_hash', slug);

    // 3) Trigger the real filter by clicking the <a> (not the <li>)
    // Delay lightly so widgets are fully ready
    setTimeout(function () {
      link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
      var li = link.closest('.portfolio-filters__term');
      if (li) li.classList.add('portfolio-filters__term--active');
      history.replaceState(null, '', '#' + slug); // keep tidy hash
    }, 50);
  }
}

  // ---- Capture user clicks to keep URL + cookie/session in sync ----
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.portfolio-filters__terms a, .elementor-portfolio__filter');
    if (!a || !onArchive()) return;

    // Prefer data-term like "portfolio_category:plan"
    var dt = a.getAttribute('data-term') || '';
    var slug = '';
    var m = dt.match(/portfolio_category:([\w-]+)/);
    if (m && m[1]) {
      slug = m[1].toLowerCase();
    } else {
      var txt = (a.textContent || '').toLowerCase().trim();
      if (txt && txt !== 'alle') slug = txt.replace(/\s+/g, '-');
    }

    if (slug) {
      // filtered → remember the slug (e.g., plan)
      sessionStorage.setItem('hrtb_portfolio_hash', slug);
      document.cookie = 'hrtb_portfolio_hash=' + encodeURIComponent(slug) + '; Max-Age=1800; Path=/';
      history.replaceState(null, '', '#'+slug);
    } else {
      // "Alle" → mark unfiltered
      sessionStorage.removeItem('hrtb_portfolio_hash');
      document.cookie = 'hrtb_portfolio_hash=ALL; Max-Age=1800; Path=/';
      history.replaceState(null, '', window.location.pathname.replace(/\/kategori\/[^/]+\/?$/,''));
    }
  }, true);

  // ---- Neutralize default "utvalgte" when no hash on first load ----
  document.addEventListener('DOMContentLoaded', function () {
    if (!onArchive()) return;
    if (!location.hash) {
      // Click reset /*or*/ send event the widget listens to
      var reset = document.querySelector('.portfolio-filters__term--reset a,[data-term="portfolio_category:*"]');
      if (reset) setTimeout(function () { reset.click(); }, 50);
    }
  });

  // ---- Sync on load, BFCache restore, and hash change ----
  document.addEventListener('DOMContentLoaded', function () {
    if (!onArchive()) return;
    // Initial apply (in case we landed with a hash like #plan)
    setTimeout(applyHashFilterFromLocation, 0);
  });

  window.addEventListener('pageshow', function () {
    if (!onArchive()) return;
    // BFCache restores (back button) can re-activate server default — re-apply
    setTimeout(applyHashFilterFromLocation, 0);
  });

  window.addEventListener('hashchange', function () {
    if (!onArchive()) return;
    applyHashFilterFromLocation();
  });

  // ---- Optional: listen to Kalium portfolio event if emitted ----
  document.addEventListener('kaliumPortfolioFiltered', function () {
    if (!onArchive()) return;
    // Keep cookie/session aligned with current hash after internal changes
    var h = (location.hash || '').replace(/^#/, '');
    if (h && h !== '*' && h.toLowerCase() !== 'alle') {
      var slug = h.toLowerCase().split('|')[0].split(':').pop().split(',')[0].split('+')[0];
      sessionStorage.setItem('hrtb_portfolio_hash', slug);
      setFilterCookie(slug);
    } else {
      sessionStorage.removeItem('hrtb_portfolio_hash');
      setFilterCookie('');
    }
  });
})();
document.addEventListener('DOMContentLoaded', () => {
  // Existing SVG logo code
  const img = document.querySelector('.header-logo img.main-logo[src$=".svg"]');
  if (!img) return;

  fetch(img.src, { credentials: 'same-origin' })
    .then(r => r.text())
    .then(txt => {
      const svg = new DOMParser().parseFromString(txt, 'image/svg+xml').documentElement;
      svg.classList.add('site-logo');
      svg.removeAttribute('width'); svg.removeAttribute('height'); // use viewBox + CSS
      img.replaceWith(svg);
    })
    .catch(() => {});
});

// Function to wrap text in parentheses with span
function wrapParenthesesText() {
    const titles = document.querySelectorAll('.elementor-portfolio-item__title');
    // console.log('Found', titles.length, 'titles with class .elementor-portfolio-item__title');
    
    titles.forEach(function(title, index) {
        const text = title.textContent;
        
        // Check if text contains parentheses
        if (text.includes('(') && text.includes(')')) {
            // Replace text with wrapped version
            const wrappedText = text.replace(/(\([^)]+\))/g, '<span class="note">$1</span>');
            title.innerHTML = wrappedText;
        } 
    });
  }


// Run the parentheses wrapping function at different times to catch dynamic content
document.addEventListener('DOMContentLoaded', function() {
    // Run immediately when DOM is ready
    wrapParenthesesText();
    
    // Also run it after delays to catch any dynamically loaded content
    setTimeout(wrapParenthesesText, 1000);
    setTimeout(wrapParenthesesText, 3000);
});
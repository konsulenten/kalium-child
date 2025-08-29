document.addEventListener('DOMContentLoaded', function() {
    const grid = document.querySelector('.elementor-portfolio');
    const filterBar = document.querySelector('.elementor-portfolio__filters');
    let iso = null;

    // Function to wrap text in parentheses with span
    function wrapParenthesesText() {
        const titles = document.querySelectorAll('.elementor-portfolio-item__title');
        
        titles.forEach(function(title) {
            const text = title.textContent;
            
            if (text.includes('(') && text.includes(')')) {
                const wrappedText = text.replace(/(\([^)]+\))/g, '<span class="note">$1</span>');
                title.innerHTML = wrappedText;
            }
        });
    }

    // Function to update URL when clicking on employee
    function updateURL(postSlug) {
        if (window.history && window.history.pushState) {
            const newUrl = window.location.pathname + '?ansatt=' + postSlug;
            window.history.pushState({ansatt: postSlug}, '', newUrl);
        }
    }

    // Function to auto-open ansatt based on URL parameter
    function checkAutoOpen() {
        if (window.ansattAutoOpen || window.ansattAutoOpenId) {
            console.log('Auto-open detected:', window.ansattAutoOpen || window.ansattAutoOpenId);
            
            setTimeout(function() {
                let targetItem = null;
                
                // Try to find by slug first
                if (window.ansattAutoOpen) {
                    targetItem = document.querySelector(`.elementor-portfolio-item[data-post-slug="${window.ansattAutoOpen}"]`);
                }
                
                // Fallback to ID
                if (!targetItem && window.ansattAutoOpenId) {
                    targetItem = document.querySelector(`.elementor-portfolio-item[data-post-id="${window.ansattAutoOpenId}"]`);
                }
                
                if (targetItem) {
                    console.log('Found target item, clicking...');
                    targetItem.style.display = '';
                    targetItem.click();
                    
                    setTimeout(function() {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }, 300);
                } else {
                    console.log('Target ansatt not found in grid');
                }
            }, 800);
        }
    }

    function initIsotope() {
        if (!grid || typeof Isotope !== "function") {
            console.log('Isotope not available or grid not found');
            return;
        }

        wrapParenthesesText();

        iso = new Isotope(grid, {
            itemSelector: '.elementor-portfolio-item',
            layoutMode: 'masonry',
            percentPosition: true,
            transitionDuration: '0.4s',
            masonry: {
                columnWidth: '.elementor-portfolio-item',
                gutter: 0
            }
        });

        // Setup filter functionality
        if (filterBar) {
            filterBar.addEventListener('click', function(e) {
                if (!e.target.classList.contains('elementor-portfolio__filter')) return;

                filterBar.querySelectorAll('.elementor-portfolio__filter').forEach(f => f.classList.remove('elementor-active'));
                e.target.classList.add('elementor-active');

                const filter = e.target.getAttribute('data-filter');
                const selector = (filter === '__all') ? '*' : '.elementor-filter-' + filter;
                
                iso.arrange({ filter: selector });
                
                setTimeout(function() {
                    iso.layout();
                }, 100);
            });
        }

        console.log('Isotope initialized successfully');
        checkAutoOpen();
        return iso;
    }

    function waitForContent() {
        const items = grid ? grid.querySelectorAll('.elementor-portfolio-item') : [];
        const images = grid ? grid.querySelectorAll('img') : [];

        // If no grid found, exit early
        if (!grid) {
            console.log('Grid not found, cannot initialize');
            return;
        }

        if (typeof imagesLoaded === 'function') {
            imagesLoaded(grid, function() {
                initIsotope();
            });
        } else {
            let loadedImages = 0;
            const totalImages = images.length;

            function checkAllLoaded() {
                if (loadedImages >= totalImages) {
                    initIsotope();
                }
            }

            if (totalImages === 0) {
                initIsotope();
            } else {
                images.forEach(function(img) {
                    if (img.complete && img.naturalHeight !== 0) {
                        loadedImages++;
                    } else {
                        img.addEventListener('load', function() {
                            loadedImages++;
                            checkAllLoaded();
                        });
                        img.addEventListener('error', function() {
                            loadedImages++;
                            checkAllLoaded();
                        });
                    }
                });
                
                checkAllLoaded();
                
                setTimeout(function() {
                    if (!iso) {
                        initIsotope();
                    }
                }, 3000);
            }
        }
    }

    // Initialize
    if ('IntersectionObserver' in window && grid) {
        let hasInitialized = false;
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !hasInitialized) {
                    hasInitialized = true;
                    
                    setTimeout(function() {
                        waitForContent();
                    }, 100);
                    
                    observer.disconnect();
                }
            });
        });
        
        observer.observe(grid);
    } else {
        setTimeout(waitForContent, 100);
    }

    // Handle resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (iso) {
                iso.layout();
            }
        }, 250);
    });
});

// UPDATED: Popup handling with URL update
jQuery(function($) {
    const popupId = 10040;
    const popupSelector = '#elementor-popup-modal-' + popupId + ' #ansatt-popup-content';

    $('.elementor-portfolio').on('click', '.elementor-portfolio-item', function (e) {
        const $item = $(this);
        const postId = $item.data('post-id');
        const postSlug = $item.data('post-slug');
        
        if (postId) {
            // Update URL with ansatt parameter
            if (postSlug && window.history && window.history.pushState) {
                const newUrl = window.location.pathname + '?ansatt=' + postSlug;
                window.history.pushState({ansatt: postSlug}, '', newUrl);
            }
            
            // Open popup
            if (typeof elementorProFrontend !== 'undefined' && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
                elementorProFrontend.modules.popup.showPopup({ id: popupId });
            }
            
            // Load content
            $(popupSelector).html('<div class="loading">Laster inn...</div>');
            $.post(ansattAjax.ajaxurl, {
                action: 'load_ansatt_popup',
                post_id: postId
            }, function (response) {
                if (response.success) {
                    $(popupSelector).html(response.data);
                } else {
                    $(popupSelector).html('<p>Fant ikke innhold.</p>');
                }
            }).fail(function () {
                $(popupSelector).html('<p>Kunne ikke laste inn informasjon.</p>');
            });
            
            e.preventDefault();
        }
    });

    // Handle popup close - remove URL parameter
    $(document).on('click', '.dialog-close-button, .elementor-popup-modal .elementor-popup-modal__close', function() {
        if (window.history && window.history.pushState) {
            window.history.pushState({}, '', window.location.pathname);
        }
    });

    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        const urlParams = new URLSearchParams(window.location.search);
        const ansattSlug = urlParams.get('ansatt');
        
        if (ansattSlug) {
            // Find and click the corresponding employee
            const targetItem = document.querySelector(`.elementor-portfolio-item[data-post-slug="${ansattSlug}"]`);
            if (targetItem) {
                targetItem.click();
            }
        } else {
            // Close popup if no ansatt parameter
            if (typeof elementorProFrontend !== 'undefined' && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
                elementorProFrontend.modules.popup.closePopup({}, e);
            }
        }
    });
});
// Main JavaScript File

// Header scroll effect
window.addEventListener('scroll', function() {
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Video Sliders
document.querySelectorAll('.video-slider').forEach(slider => {
    const container = slider.querySelector('.slider-container');
    const prevBtn = slider.querySelector('.prev');
    const nextBtn = slider.querySelector('.next');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            container.scrollBy({
                left: -container.offsetWidth,
                behavior: 'smooth'
            });
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            container.scrollBy({
                left: container.offsetWidth,
                behavior: 'smooth'
            });
        });
    }
});

// Search functionality
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const searchResults = document.getElementById('searchResults');
const siteUrl = (window.SITE_URL || `${window.location.origin}/ott anti`).replace(/\/$/, '');

const buildUrl = (path) => `${siteUrl}${path.startsWith('/') ? path : `/${path}`}`;

const goToSearchPage = () => {
    if (!searchInput) {
        return;
    }

    const query = searchInput.value.trim();
    if (query.length === 0) {
        searchInput.focus();
        return;
    }

    window.location.href = `${buildUrl('/search.php')}?q=${encodeURIComponent(query)}`;
};

let searchTimeout;

if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            goToSearchPage();
        }
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`${buildUrl('/ajax/search.php')}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        displaySearchResults(data);
                    } else {
                        searchResults.innerHTML = '<div class="no-results">No results found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(() => {
                    searchResults.innerHTML = '<div class="no-results">Search is temporarily unavailable</div>';
                    searchResults.style.display = 'block';
                });
        }, 300);
    });
}

if (searchBtn) {
    searchBtn.addEventListener('click', goToSearchPage);
}

function displaySearchResults(results) {
    let html = '<div class="search-results-container">';
    results.forEach(video => {
        const detailsPage = video.type === 'series' ? 'series-details.php' : 'watch.php';
        html += `
            <div class="search-result-item" onclick="location.href='${buildUrl(`/${detailsPage}`)}?id=${video.id}'">
                <img src="${buildUrl(`/assets/uploads/thumbnails/${video.thumbnail_path}`)}" alt="${video.title}">
                <div class="result-info">
                    <h4>${video.title}</h4>
                    <p>${video.category_name} • ${video.duration}</p>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    searchResults.innerHTML = html;
    searchResults.style.display = 'block';
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-box') && !e.target.closest('#searchResults')) {
        searchResults.style.display = 'none';
    }
});

// Video card hover effects
document.querySelectorAll('.video-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.zIndex = '10';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.zIndex = '1';
    });
});

// Lazy loading images
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.add('loaded');
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
});

// Mobile menu toggle
const createMobileMenu = () => {
    if (window.innerWidth <= 768) {
        const nav = document.querySelector('.nav-menu');
        const menuBtn = document.createElement('button');
        menuBtn.classList.add('mobile-menu-btn');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        
        document.querySelector('.nav-left').prepend(menuBtn);
        
        menuBtn.addEventListener('click', () => {
            nav.classList.toggle('show');
        });
    }
};

createMobileMenu();
window.addEventListener('resize', createMobileMenu);
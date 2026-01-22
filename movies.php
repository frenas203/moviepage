<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Movies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    // Include error handling and database connection
    require_once 'connect.php';
    
    // Create pictures directory if it doesn't exist
    $picturesDir = './pictures/movies/';
    if (!file_exists($picturesDir) && !is_dir($picturesDir)) {
        mkdir($picturesDir, 0775, true);
    }
    ?>
    
    <header>
        <img src="./pictures/logo.png" alt="Logo">
    </header>
    <nav class="topnav">
        <div class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul>
            <li><a href="./index.php">Home</a></li>
            <li><a href="./movies.php">All Movies</a></li>
            <li><a href="./register_movies.php">Register New movies</a></li>
            <li><a href="./dashboard.php">📊 Dashboard</a></li>
        </ul>
    </nav>

    <h1>Your Movie Collection</h1>
    
    <!-- Export button -->
    <div class="export-container">
        <button onclick="exportToCSV()" class="export-btn">📊 Export to CSV</button>
    </div>
    
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <button class="theme-toggle" id="themeToggle" title="Toggle dark/light theme">
            <span class="theme-icon">🌙</span>
        </button>
    </div>
    
    

    <!-- Controls -->
    <div class="controls-wrapper">
        <div class="controls">
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="Search movies..." autocomplete="off">
                <button class="search-clear" id="searchClear">×</button>
            </div>
            
            <div class="filters-group">
                <select id="sortSelect">
                    <option value="name_asc">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                    <option value="year_asc">Year (Oldest first)</option>
                    <option value="year_desc">Year (Newest first)</option>
                </select>
                
                <select id="formatSelect">
                    <option value="">All Formats</option>
                    <option value="UHD">UHD</option>
                    <option value="Bluray">Blu-ray</option>
                    <option value="DVD">DVD</option>
                </select>
                
                <select id="pageSizeSelect">
                    <option value="20">20 per page</option>
                    <option value="30">30 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>
        </div>
    </div>
    
    
    
    <!-- Statistics -->
    <div class="stats" id="stats"></div>
    
    <!-- Movies grid -->
    <div class="movie-grid" id="movieGrid"></div>
    
    <!-- Loading indicator -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        Loading movies...
    </div>
    
    <!-- Error display -->
    <div class="error" id="error"></div>
    
    <!-- Pagination -->
    <div class="pagination" id="pagination"></div>
    
    <!-- Debug information (legacy support) -->
    <div class="debug-section" id="debugSection">
        <button class="debug-toggle" onclick="toggleDebug()">Show Debug Info</button>
        <div class="debug-container" id="debugContainer">
            <h3>Debug Information:</h3>
            <pre id="debugInfo"></pre>
        </div>
    </div>

    <script>
        // Application state
        let currentState = {
            page: 1,
            limit: 20,
            sort: 'name_asc',
            format: '',
            search: '',
            theme: localStorage.getItem('snapflix-theme') || 'dark',
            loading: false,
            cache: new Map(),
            debounceTimer: null
        };
        
        // DOM elements
        const elements = {
            movieGrid: document.getElementById('movieGrid'),
            loading: document.getElementById('loading'),
            error: document.getElementById('error'),
            stats: document.getElementById('stats'),
            pagination: document.getElementById('pagination'),
            searchInput: document.getElementById('searchInput'),
            searchClear: document.getElementById('searchClear'),
            sortSelect: document.getElementById('sortSelect'),
            formatSelect: document.getElementById('formatSelect'),
            pageSizeSelect: document.getElementById('pageSizeSelect'),
            themeToggle: document.getElementById('themeToggle'),
            debugSection: document.getElementById('debugSection'),
            debugContainer: document.getElementById('debugContainer'),
            debugInfo: document.getElementById('debugInfo')
        };
        
        // Initialize the application
        function init() {
            setupEventListeners();
            loadFromURL();
            loadMovies();
            
            // Hide debug section by default
            if (elements.debugSection) {
                elements.debugSection.style.display = 'none';
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Initialize theme
            applyTheme();
            
            // Search with debouncing
            elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(currentState.debounceTimer);
                currentState.debounceTimer = setTimeout(() => {
                    currentState.search = e.target.value;
                    currentState.page = 1;
                    updateURL();
                    loadMovies();
                    toggleSearchClear();
                }, 300);
            });
            
            elements.searchClear.addEventListener('click', () => {
                elements.searchInput.value = '';
                currentState.search = '';
                currentState.page = 1;
                updateURL();
                loadMovies();
                toggleSearchClear();
            });
            
            // Filters
            elements.sortSelect.addEventListener('change', (e) => {
                currentState.sort = e.target.value;
                currentState.page = 1;
                updateURL();
                loadMovies();
            });
            
            elements.formatSelect.addEventListener('change', (e) => {
                currentState.format = e.target.value;
                currentState.page = 1;
                updateURL();
                loadMovies();
            });
            
            elements.pageSizeSelect.addEventListener('change', (e) => {
                currentState.limit = parseInt(e.target.value);
                currentState.page = 1;
                updateURL();
                loadMovies();
            });
            
            
            // Theme toggle
            elements.themeToggle.addEventListener('click', toggleTheme);
            
        }
        
        // Load state from URL parameters
        function loadFromURL() {
            const params = new URLSearchParams(window.location.search);
            currentState.page = parseInt(params.get('page')) || 1;
            currentState.limit = parseInt(params.get('limit')) || 20;
            currentState.sort = params.get('sort') || 'name_asc';
            currentState.format = params.get('format') || '';
            currentState.search = params.get('search') || '';
            
            // Update UI elements
            elements.searchInput.value = currentState.search;
            elements.sortSelect.value = currentState.sort;
            elements.formatSelect.value = currentState.format;
            elements.pageSizeSelect.value = currentState.limit.toString();
            toggleSearchClear();
        }
        
        // Update URL without page reload
        function updateURL() {
            const params = new URLSearchParams();
            if (currentState.page > 1) params.set('page', currentState.page);
            if (currentState.limit !== 20) params.set('limit', currentState.limit);
            if (currentState.sort !== 'name_asc') params.set('sort', currentState.sort);
            if (currentState.format) params.set('format', currentState.format);
            if (currentState.search) params.set('search', currentState.search);
            
            const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newURL);
        }
        
        // Generate cache key
        function getCacheKey() {
            return `${currentState.page}-${currentState.limit}-${currentState.sort}-${currentState.format}-${currentState.search}`;
        }
        
        // Load movies from API
        async function loadMovies() {
            if (currentState.loading) return;
            
            const cacheKey = getCacheKey();
            
            // Check cache first
            if (currentState.cache.has(cacheKey)) {
                const cachedData = currentState.cache.get(cacheKey);
                renderMovies(cachedData);
                return;
            }
            
            currentState.loading = true;
            showLoading(true);
            hideError();
            
            try {
                const params = new URLSearchParams({
                    page: currentState.page,
                    limit: currentState.limit,
                    sort: currentState.sort,
                    format: currentState.format,
                    search: currentState.search
                });
                
                const response = await fetch(`api/movies.php?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Unknown error occurred');
                }
                
                // Cache the result
                currentState.cache.set(cacheKey, data);
                
                // Limit cache size
                if (currentState.cache.size > 50) {
                    const firstKey = currentState.cache.keys().next().value;
                    currentState.cache.delete(firstKey);
                }
                
                renderMovies(data);
                updateDebugInfo(data);
                
            } catch (error) {
                console.error('Error loading movies:', error);
                showError('Failed to load movies: ' + error.message);
            } finally {
                currentState.loading = false;
                showLoading(false);
            }
        }
        
        // Render movies and pagination
        function renderMovies(data) {
            renderMovieGrid(data.data);
            renderStats(data.stats);
            renderPagination(data.pagination);
        }
        
        // Render movie grid
        function renderMovieGrid(movies) {
            if (movies.length === 0) {
                elements.movieGrid.innerHTML = `
                    <div class="no-movies-message">
                        <h3>No movies found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                    </div>
                `;
                return;
            }
            
            elements.movieGrid.innerHTML = movies.map((movie, index) => {
                // Use image from API or generate fallback suggestions
                const hasImage = movie.image !== null;
                const imagePath = hasImage ? movie.image : '';
                
                // Generate suggested filenames for missing images
                const suggestions = generateImageSuggestions(movie.name, movie.year);
                const suggestionText = suggestions.slice(0, 3).join(', ');
                
                return `
                    <div class="movie-card" style="animation-delay: ${index * 0.05}s">
                        <div class="movie-poster-container">
                            ${hasImage ? 
                                `<img src="${imagePath}" alt="${escapeHtml(movie.name)} (${movie.year})" class="movie-poster">` :
                                `<div class="movie-placeholder">
                                    <div class="placeholder-icon">🎬</div>
                                    <div class="placeholder-text">No Image</div>
                                    <div class="placeholder-info">Try: ${suggestionText}</div>
                                </div>`
                            }
                        </div>
                        <div class="year-badge">${movie.year}</div>
                        <div class="movie-info">
                            <h2>${escapeHtml(movie.name)}</h2>
                            <div class="movie-format">${movie.format}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Render statistics
        function renderStats(stats) {
            const formatCountsHtml = Object.entries(stats.formatCounts)
                .map(([format, count]) => `<span class="format-count">${format}: ${count}</span>`)
                .join('');
            
            elements.stats.innerHTML = `
                <p>Total Collection: ${stats.total} movies</p>
                <div class="format-counts">${formatCountsHtml}</div>
            `;
        }
        
        // Render pagination
        function renderPagination(pagination) {
            if (pagination.totalPages <= 1) {
                elements.pagination.innerHTML = '';
                return;
            }
            
            let paginationHtml = '';
            
            // Previous button
            paginationHtml += `
                <button onclick="goToPage(${pagination.page - 1})" 
                        ${!pagination.hasPrev ? 'disabled' : ''}>
                    ← Previous
                </button>
            `;
            
            // Page numbers
            const startPage = Math.max(1, pagination.page - 2);
            const endPage = Math.min(pagination.totalPages, pagination.page + 2);
            
            if (startPage > 1) {
                paginationHtml += `<button onclick="goToPage(1)">1</button>`;
                if (startPage > 2) {
                    paginationHtml += `<span>...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.page;
                paginationHtml += `
                    <button onclick="goToPage(${i})" 
                            class="${isActive ? 'current' : ''}">
                        ${i}
                    </button>
                `;
            }
            
            if (endPage < pagination.totalPages) {
                if (endPage < pagination.totalPages - 1) {
                    paginationHtml += `<span>...</span>`;
                }
                paginationHtml += `<button onclick="goToPage(${pagination.totalPages})">${pagination.totalPages}</button>`;
            }
            
            // Next button
            paginationHtml += `
                <button onclick="goToPage(${pagination.page + 1})" 
                        ${!pagination.hasNext ? 'disabled' : ''}>
                    Next →
                </button>
            `;
            
            elements.pagination.innerHTML = paginationHtml;
        }
        
        // Update debug info
        function updateDebugInfo(data) {
            if (elements.debugInfo) {
                const debugText = [
                    `Page: ${data.pagination.page}/${data.pagination.totalPages}`,
                    `Items per page: ${data.pagination.limit}`,
                    `Total items: ${data.pagination.total}`,
                    `Sort: ${data.filters.sort}`,
                    `Format filter: ${data.filters.format || 'All'}`,
                    `Search: ${data.filters.search || 'None'}`,
                    `Cache size: ${currentState.cache.size} entries`
                ].join('\n');
                elements.debugInfo.textContent = debugText;
            }
        }
        
        // Navigation functions
        function goToPage(page) {
            currentState.page = page;
            updateURL();
            loadMovies();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Utility functions
        function showLoading(show) {
            elements.loading.style.display = show ? 'flex' : 'none';
        }
        
        function showError(message) {
            elements.error.textContent = message;
            elements.error.style.display = 'block';
        }
        
        function hideError() {
            elements.error.style.display = 'none';
        }
        
        function toggleSearchClear() {
            elements.searchClear.style.display = currentState.search ? 'block' : 'none';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Generate image filename suggestions for missing posters
        function generateImageSuggestions(movieName, year) {
            const cleanName = movieName.replace(/[^a-zA-Z0-9\s\-_]/g, '').trim();
            
            return [
                `${cleanName}.jpg`,
                `${cleanName.replace(/\s+/g, '_')}.jpg`,
                `${cleanName.replace(/\s+/g, '_')}_${year}.jpg`,
                `${cleanName.toLowerCase().replace(/\s+/g, '_')}.jpg`,
                `${cleanName.replace(/\s+/g, '-')}.jpg`
            ];
        }
        
        // Legacy functions for compatibility
        function toggleMenu() {
            document.querySelector('.topnav').classList.toggle('active');
            const backdrop = document.querySelector('.menu-backdrop');
            backdrop.classList.toggle('active');
            
            // Set item index for staggered animation
            const menuItems = document.querySelectorAll('.topnav ul li');
            menuItems.forEach((item, index) => {
                item.style.setProperty('--item-index', index);
            });
        }
        
        function toggleDebug() {
            if (elements.debugContainer && elements.debugSection) {
                const isVisible = elements.debugContainer.style.display === 'block';
                elements.debugContainer.style.display = isVisible ? 'none' : 'block';
                const button = elements.debugSection.querySelector('.debug-toggle');
                if (button) {
                    button.textContent = isVisible ? 'Show Debug Info' : 'Hide Debug Info';
                }
            }
        }
        
        // Movie actions
        function editMovie(title, year, format) {
            const safeTitle = encodeURIComponent(title);
            const safeYear = encodeURIComponent(year);
            const safeFormat = encodeURIComponent(format);
            
            window.location.href = `update_format.php?title=${safeTitle}&year=${safeYear}&format=${safeFormat}`;
        }
        
        function deleteMovie(title, year) {
            if (confirm(`Are you sure you want to delete "${title}" (${year})?`)) {
                const safeTitle = encodeURIComponent(title);
                const safeYear = encodeURIComponent(year);
                
                window.location.href = `delete_movie.php?title=${safeTitle}&year=${safeYear}`;
            }
        }
        
        // Export function
        function exportToCSV() {
            const params = new URLSearchParams({
                sort: currentState.sort,
                format: currentState.format,
                search: currentState.search,
                yearMin: currentState.yearMin,
                yearMax: currentState.yearMax
            });
            window.location.href = `export_csv.php?${params}`;
        }
        
        
        function toggleTheme() {
            currentState.theme = currentState.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('snapflix-theme', currentState.theme);
            applyTheme();
        }
        
        function applyTheme() {
            document.body.classList.toggle('light-theme', currentState.theme === 'light');
            const icon = elements.themeToggle.querySelector('.theme-icon');
            icon.textContent = currentState.theme === 'dark' ? '☀️' : '🌙';
        }
        
        function applyViewMode() {
            const grid = elements.movieGrid;
            grid.className = `movie-grid view-${currentState.viewMode}`;
        }
        
        function applyAspectRatio() {
            const grid = elements.movieGrid;
            grid.classList.remove('aspect-auto', 'aspect-portrait', 'aspect-square', 'aspect-landscape');
            grid.classList.add(`aspect-${currentState.aspectRatio}`);
        }
        
        
        
        // Success message handling
        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        alert('Movie deleted successfully!');
        // Clear the success parameter from URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
        <?php endif; ?>
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', init);
    </script>
    
    <div class="menu-backdrop" onclick="toggleMenu()"></div>
</body>
</html>


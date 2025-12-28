<?php
/**
 * Movie Image Scanner Utility
 * Scans the pictures folder and subfolder to report on movie poster coverage
 */

// Include database connection
require_once 'connect.php';

// Set up directories to scan
$picturesPaths = [
    './pictures/' => 'Main Pictures Folder',
    './pictures/movies/' => 'Movies Subfolder'
];

$imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];

// Get all movies from database
function getAllMovies($conn) {
    $sql = "SELECT name, year FROM physicalmovies ORDER BY name ASC";
    $result = $conn->query($sql);
    $movies = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = [
                'name' => $row['name'],
                'year' => $row['year']
            ];
        }
    }
    
    return $movies;
}

// Scan directory for image files
function scanImagesInDirectory($directory) {
    $images = [];
    
    if (!is_dir($directory)) {
        return $images;
    }
    
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $directory . $file;
        if (is_file($filePath)) {
            $ext = strtolower(strrchr($file, '.'));
            if (in_array($ext, ['.jpg', '.jpeg', '.png', '.gif', '.webp'])) {
                $images[] = [
                    'filename' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath)
                ];
            }
        }
    }
    
    return $images;
}

// Generate name variations for matching
function generateNameVariations($movieName, $year) {
    $variations = [];
    
    // Clean the movie name for filename use
    $cleanName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $movieName);
    $cleanName = trim($cleanName);
    
    // Various naming patterns
    $variations[] = $cleanName;
    $variations[] = str_replace(' ', '_', $cleanName);
    $variations[] = str_replace(' ', '-', $cleanName);
    $variations[] = str_replace(' ', '', $cleanName);
    $variations[] = strtolower($cleanName);
    $variations[] = strtolower(str_replace(' ', '_', $cleanName));
    $variations[] = strtolower(str_replace(' ', '-', $cleanName));
    $variations[] = strtolower(str_replace(' ', '', $cleanName));
    $variations[] = $cleanName . '_' . $year;
    $variations[] = str_replace(' ', '_', $cleanName) . '_' . $year;
    $variations[] = strtolower(str_replace(' ', '_', $cleanName)) . '_' . $year;
    $variations[] = $cleanName . '-' . $year;
    $variations[] = $cleanName . '(' . $year . ')';
    
    return array_unique(array_filter($variations));
}

// Find matching image for a movie
function findImageForMovie($movieName, $year, $allImages) {
    $variations = generateNameVariations($movieName, $year);
    
    foreach ($allImages as $image) {
        $filename = pathinfo($image['filename'], PATHINFO_FILENAME);
        
        foreach ($variations as $variation) {
            if (strcasecmp($filename, $variation) === 0) {
                return $image;
            }
        }
    }
    
    return null;
}

// Main scanning logic
$movies = getAllMovies($conn);
$allImages = [];
$imagesByDirectory = [];

// Scan all directories
foreach ($picturesPaths as $path => $description) {
    $images = scanImagesInDirectory($path);
    $imagesByDirectory[$path] = [
        'description' => $description,
        'images' => $images
    ];
    $allImages = array_merge($allImages, $images);
}

// Analyze coverage
$moviesWithImages = [];
$moviesWithoutImages = [];
$unusedImages = $allImages;

foreach ($movies as $movie) {
    $foundImage = findImageForMovie($movie['name'], $movie['year'], $allImages);
    
    if ($foundImage) {
        $moviesWithImages[] = array_merge($movie, ['image' => $foundImage]);
        // Remove from unused images
        $unusedImages = array_filter($unusedImages, function($img) use ($foundImage) {
            return $img['filename'] !== $foundImage['filename'];
        });
    } else {
        $moviesWithoutImages[] = $movie;
    }
}

$coverage = count($movies) > 0 ? (count($moviesWithImages) / count($movies)) * 100 : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Poster Scanner</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .scan-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .coverage-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(92, 159, 143, 0.3);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #5C9F8F;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #bbb;
            font-size: 1.1em;
        }
        
        .coverage-bar {
            background: #333;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .coverage-fill {
            height: 100%;
            background: linear-gradient(90deg, #5C9F8F, #2E6356);
            transition: width 0.3s ease;
        }
        
        .section {
            margin: 40px 0;
            padding: 20px;
            background: rgba(92, 159, 143, 0.1);
            border-radius: 10px;
        }
        
        .movie-list {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }
        
        .movie-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #1e1e1e;
            border-radius: 8px;
            border-left: 4px solid #5C9F8F;
        }
        
        .movie-item.missing {
            border-left-color: #f44336;
        }
        
        .movie-suggestions {
            color: #888;
            font-size: 0.9em;
            margin-top: 5px;
            font-style: italic;
        }
        
        .image-info {
            color: #5C9F8F;
            font-size: 0.9em;
        }
        
        .collapsible {
            cursor: pointer;
            padding: 15px;
            background: #2a2a2a;
            border: none;
            color: white;
            text-align: left;
            outline: none;
            font-size: 16px;
            border-radius: 5px;
            margin: 10px 0;
            width: 100%;
            transition: background 0.3s ease;
        }
        
        .collapsible:hover {
            background: #333;
        }
        
        .collapsible.active {
            background: #5C9F8F;
        }
        
        .content {
            padding: 0 15px;
            display: none;
            overflow: hidden;
            background: #1a1a1a;
            border-radius: 0 0 5px 5px;
        }
        
        .content.show {
            display: block;
            padding: 15px;
        }
        
        .refresh-btn {
            background: #5C9F8F;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 0;
            transition: background 0.3s ease;
        }
        
        .refresh-btn:hover {
            background: #4a8a7a;
        }
    </style>
</head>
<body>
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
            <li><a href="./scan_images.php">🔍 Image Scanner</a></li>
        </ul>
    </nav>
    
    <div class="scan-container">
        <h1>Movie Poster Coverage Report</h1>
        
        <button class="refresh-btn" onclick="window.location.reload()">🔄 Refresh Scan</button>
        
        <div class="coverage-stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($movies) ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($moviesWithImages) ?></div>
                <div class="stat-label">With Posters</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= count($moviesWithoutImages) ?></div>
                <div class="stat-label">Missing Posters</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= number_format($coverage, 1) ?>%</div>
                <div class="stat-label">Coverage</div>
                <div class="coverage-bar">
                    <div class="coverage-fill" style="width: <?= $coverage ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Cards -->
        <div class="quick-stats" id="quickStats">
            <div class="stat-card">
                <div class="stat-number" id="totalMovies">0</div>
                <div class="stat-label">Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="mostCommonFormat">-</div>
                <div class="stat-label">Most Common Format</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="currentDecade">0</div>
                <div class="stat-label">Movies This Decade</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="recentlyAdded">0</div>
                <div class="stat-label">Added This Month</div>
            </div>
        </div>
        
        <!-- Recent Activity Feed -->
        <div class="recent-activity" id="recentActivity">
            <h3>Recent Activity</h3>
            <div class="activity-feed" id="activityFeed">
                <!-- Activity items will be loaded here -->
            </div>
        </div>
        
        <!-- Format Upgrade Recommendations -->
        <div class="upgrade-recommendations" id="upgradeRecommendations" style="display: none;">
            <h3>📈 Upgrade Recommendations</h3>
            <div class="recommendations-list" id="recommendationsList">
                <!-- Recommendations will be loaded here -->
            </div>
        </div>
        
        <!-- Directory Overview -->
        <div class="section">
            <h2>Image Directories</h2>
            <?php foreach ($imagesByDirectory as $path => $info): ?>
                <button class="collapsible">
                    <?= $info['description'] ?> (<?= count($info['images']) ?> images)
                </button>
                <div class="content">
                    <?php if (empty($info['images'])): ?>
                        <p>No images found in this directory.</p>
                        <p><strong>Directory:</strong> <?= htmlspecialchars($path) ?></p>
                    <?php else: ?>
                        <p><strong>Directory:</strong> <?= htmlspecialchars($path) ?></p>
                        <div class="movie-list">
                            <?php foreach ($info['images'] as $image): ?>
                                <div class="movie-item">
                                    <div>
                                        <strong><?= htmlspecialchars($image['filename']) ?></strong>
                                        <div class="image-info">
                                            Size: <?= number_format($image['size'] / 1024, 1) ?> KB
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Movies with Images -->
        <?php if (!empty($moviesWithImages)): ?>
        <div class="section">
            <button class="collapsible">✅ Movies with Posters (<?= count($moviesWithImages) ?>)</button>
            <div class="content">
                <div class="movie-list">
                    <?php foreach ($moviesWithImages as $movie): ?>
                        <div class="movie-item">
                            <div>
                                <strong><?= htmlspecialchars($movie['name']) ?> (<?= $movie['year'] ?>)</strong>
                                <div class="image-info">
                                    Found: <?= htmlspecialchars($movie['image']['filename']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Movies without Images -->
        <?php if (!empty($moviesWithoutImages)): ?>
        <div class="section">
            <button class="collapsible">❌ Movies Missing Posters (<?= count($moviesWithoutImages) ?>)</button>
            <div class="content">
                <div class="movie-list">
                    <?php foreach ($moviesWithoutImages as $movie): ?>
                        <?php $suggestions = generateNameVariations($movie['name'], $movie['year']); ?>
                        <div class="movie-item missing">
                            <div>
                                <strong><?= htmlspecialchars($movie['name']) ?> (<?= $movie['year'] ?>)</strong>
                                <div class="movie-suggestions">
                                    Try naming your image: <?= implode('.jpg, ', array_slice($suggestions, 0, 3)) ?>.jpg
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Unused Images -->
        <?php if (!empty($unusedImages)): ?>
        <div class="section">
            <button class="collapsible">🗂️ Unused Images (<?= count($unusedImages) ?>)</button>
            <div class="content">
                <p>These images don't match any movies in your database:</p>
                <div class="movie-list">
                    <?php foreach ($unusedImages as $image): ?>
                        <div class="movie-item">
                            <div>
                                <strong><?= htmlspecialchars($image['filename']) ?></strong>
                                <div class="image-info">
                                    Size: <?= number_format($image['size'] / 1024, 1) ?> KB
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h3>💡 Tips for Better Image Recognition</h3>
            <ul style="text-align: left; color: #bbb; line-height: 1.6;">
                <li>Place images in <code>./pictures/movies/</code> for best organization</li>
                <li>Use these naming patterns: <code>Movie_Name.jpg</code> or <code>movie_name_year.jpg</code></li>
                <li>Supported formats: JPG, JPEG, PNG, GIF, WebP</li>
                <li>Remove special characters from filenames (use underscores or hyphens)</li>
                <li>Keep filenames consistent with your movie database entries</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Collapsible sections
        document.querySelectorAll('.collapsible').forEach(button => {
            button.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                content.classList.toggle('show');
            });
        });
        
        // Menu toggle function
        function toggleMenu() {
            document.querySelector('.topnav').classList.toggle('active');
            const backdrop = document.querySelector('.menu-backdrop');
            if (backdrop) {
                backdrop.classList.toggle('active');
            }
        }
        
        // Recent Activity and Upgrade Recommendations functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        async function loadRecentActivity() {
            try {
                const response = await fetch('api/recent_activity.php');
                const data = await response.json();
                
                const activityFeed = document.getElementById('activityFeed');
                if (data.success && data.activities.length > 0) {
                    const html = data.activities.map(activity => `
                        <div class="activity-item">
                            <div class="activity-icon">${activity.type === 'added' ? '➕' : '✏️'}</div>
                            <div class="activity-content">
                                <div class="activity-title">${escapeHtml(activity.movie_name)} (${activity.year})</div>
                                <div class="activity-meta">${activity.action} • ${activity.time_ago}</div>
                            </div>
                        </div>
                    `).join('');
                    activityFeed.innerHTML = html;
                } else {
                    activityFeed.innerHTML = '<p>No recent activity</p>';
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
                document.getElementById('activityFeed').innerHTML = '<p>Unable to load activity feed</p>';
            }
        }
        
        async function loadUpgradeRecommendations() {
            try {
                const response = await fetch('api/upgrade_recommendations.php');
                const data = await response.json();
                
                const upgradeRecommendations = document.getElementById('upgradeRecommendations');
                const recommendationsList = document.getElementById('recommendationsList');
                
                if (data.success && data.recommendations.length > 0) {
                    const html = data.recommendations.map(rec => `
                        <div class="recommendation-item">
                            <div class="recommendation-content">
                                <div class="recommendation-title">${escapeHtml(rec.name)} (${rec.year})</div>
                                <div class="recommendation-current">Current: ${rec.current_format}</div>
                                <div class="recommendation-suggested">Suggested: ${rec.suggested_format}</div>
                                <div class="recommendation-reason">${rec.reason}</div>
                            </div>
                        </div>
                    `).join('');
                    recommendationsList.innerHTML = html;
                    upgradeRecommendations.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading upgrade recommendations:', error);
            }
        }
        
        async function loadQuickStats() {
            try {
                const response = await fetch('api/quick_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalMovies').textContent = data.stats.total;
                    document.getElementById('mostCommonFormat').textContent = data.stats.mostCommonFormat || '-';
                    document.getElementById('currentDecade').textContent = data.stats.currentDecade;
                    document.getElementById('recentlyAdded').textContent = data.stats.recentlyAdded;
                }
            } catch (error) {
                console.error('Error loading quick stats:', error);
            }
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadQuickStats();
            loadRecentActivity();
            loadUpgradeRecommendations();
        });
    </script>
    
    <div class="menu-backdrop" onclick="toggleMenu()"></div>
</body>
</html>


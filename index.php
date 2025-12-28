<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snapflix</title>
    <link rel="stylesheet" href="style.css">
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
            <li><a href="./dashboard.php">📊 Dashboard</a></li>
        </ul>
    </nav>
    <section>
        <div class="hero-container">
            <div class="img">
                <img src="./pictures/hero.png" alt="Movie Collection Hero">
            </div>
            <div class="text">
                <h1>Welcome to Your Movie Collection</h1>
                <p class="hero-subtitle">Track and manage your physical movie collection with style</p>
            </div>
        </div>
    </section>
    <article>
        <div class="upcoming-container">
            <h1>Recently Added Movies</h1>
            <div class="movies-flexbox">
                <?php
                require_once 'connect.php';

                $sql = "SELECT name, year, format FROM physicalmovies ORDER BY id DESC LIMIT 3";
                $result = mysqli_query($conn, $sql);

                if (!$result) {
                    die("Query failed: " . mysqli_error($conn));
                }

                while ($row = mysqli_fetch_assoc($result)) {
                    $name = htmlspecialchars($row['name']);
                    $year = htmlspecialchars($row['year']);
                    $format = htmlspecialchars($row['format']);

                    $imageBase = './pictures/movies/' . $name;
                    $imgPath = file_exists($imageBase . '.jpg') ? $imageBase . '.jpg' : 
                             (file_exists($imageBase . '.png') ? $imageBase . '.png' : null);

                    echo '<div class="movie-card">';
                    echo '<div class="movie-poster-container">';
                    if ($imgPath) {
                        echo '<img class="movie-poster" src="' . $imgPath . '" alt="' . $name . ' Poster">';
                    } else {
                        echo '<div class="movie-placeholder">Coming Soon</div>';
                    }
                    echo '<div class="year-badge">' . $year . '</div>';
                    echo '</div>';
                    echo '<div class="movie-info">';
                    echo '<h2>' . $name . '</h2>';
                    echo '<div class="movie-format">' . $format . '</div>';
                    echo '</div>';
                    echo '</div>';
                }

                mysqli_close($conn);
                ?>
            </div>
        </div>
    </article>
    <div class="menu-backdrop" onclick="toggleMenu()"></div>
    <script>
    function toggleMenu() {
        document.querySelector('.topnav').classList.toggle('active');
        const backdrop = document.querySelector('.menu-backdrop');
        backdrop.classList.toggle('active');
        
        // Toggle body scrolling
        if (document.querySelector('.topnav').classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        
        // Set item index for staggered animation
        const menuItems = document.querySelectorAll('.topnav ul li');
        menuItems.forEach((item, index) => {
            item.style.setProperty('--item-index', index);
        });
    }
    
    // Initialize item indices on page load
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.topnav ul li');
        menuItems.forEach((item, index) => {
            item.style.setProperty('--item-index', index);
        });
    });
    </script>
</body>
</html>


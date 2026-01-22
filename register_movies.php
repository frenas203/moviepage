<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Movies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
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
            <li><a href="./dashboard.php">📊 Dashboard</a></li>
        </ul>
    </nav>

    <?php
    if (isset($_GET['success'])) {
        $success_message = match($_GET['success']) {
            'format_updated' => 'Movie format updated successfully!',
            'movie_deleted' => 'Movie deleted successfully!',
            'added' => 'Movie added successfully!',
            default => 'Operation completed successfully!'
        };
        echo '<div class="status-message success">' . $success_message;
        
        // Add poster upload status if available
        if (isset($_GET['poster'])) {
            $poster_message = match($_GET['poster']) {
                default => ''
            };
            if (!empty($poster_message)) {
                echo '<br>' . $poster_message;
            }
        }
        
        echo '</div>';
    }
    if (isset($_GET['error'])) {
        $error_message = match($_GET['error']) {
            'missing_fields' => 'Please fill in all fields.',
            'prepare_failed' => 'Database error occurred.',
            'update_failed' => 'Failed to update movie format.',
            'delete_failed' => 'Failed to delete movie.',
            'invalid_year' => 'Year must be a number.',
            'invalid_format' => 'Please select a valid format.',
            'execution_failed' => 'Database error occurred while saving movie.',
            default => 'An error occurred.'
        };
        echo '<div class="status-message error">' . $error_message . '</div>';
    }
    ?>

    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-button active" onclick="openTab('add')">Add Movie</button>
            <button class="tab-button" onclick="openTab('update')">Update Format</button>
            <button class="tab-button" onclick="openTab('delete')">Delete Movie</button>
        </div>

        <div id="add" class="tab-content active">
            <h1 class="new-entry">Add New Movie</h1>
            <form action="add_entry.php" method="post" class="add-movie-form">
                <label for="title">Movie Title:</label>
                <input type="text" id="title" name="title" placeholder="Enter movie title" required>
                
                <label for="year">Year:</label>
                <input type="text" id="year" name="year" placeholder="Enter release year" required>
                
                <label for="format">Format:</label>
                <select name="format" id="format" required>
                    <option value="">Select format</option>
                    <option value="UHD">UHD</option>
                    <option value="Bluray">Blu-ray</option>
                    <option value="DVD">DVD</option>
                </select>
                
                <input type="submit" value="Add Movie">
            </form>
        </div>

        <div id="update" class="tab-content">
            <h1 class="new-entry">Update Movie Format</h1>
            <form action="update_format.php" method="post">
                <label for="movie_name">Select Movie:</label>
                <select name="movie_name" id="movie_name" required>
                    <option value="">Select a movie</option>
                    <?php
                    require_once 'connect.php';
                    $sql = "SELECT name FROM physicalmovies ORDER BY name";
                    $result = mysqli_query($conn, $sql);
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . 
                             htmlspecialchars($row['name']) . '</option>';
                    }
                    ?>
                </select>
                
                <label for="new_format">New Format:</label>
                <select name="new_format" id="new_format" required>
                    <option value="">Select new format</option>
                    <option value="UHD">UHD</option>
                    <option value="Bluray">Blu-ray</option>
                    <option value="DVD">DVD</option>
                </select>
                <input type="submit" value="Update Format">
            </form>
        </div>

        <div id="delete" class="tab-content">
            <h1 class="new-entry">Delete Movie</h1>
            <form action="delete_movie.php" method="post" onsubmit="return confirm('Are you sure you want to delete this movie?');">
                <label for="delete_movie">Select Movie to Delete:</label>
                <select name="movie_name" id="delete_movie" required>
                    <option value="">Select a movie</option>
                    <?php
                    $sql = "SELECT name FROM physicalmovies ORDER BY name";
                    $result = mysqli_query($conn, $sql);
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . 
                             htmlspecialchars($row['name']) . '</option>';
                    }
                    mysqli_close($conn);
                    ?>
                </select>
                <input type="submit" value="Delete Movie" class="delete-button">
            </form>
        </div>
    </div>

    <div class="menu-backdrop" onclick="toggleMenu()"></div>
    <script>
    function toggleMenu() {
        document.querySelector('.topnav').classList.toggle('active');
        document.querySelector('.menu-backdrop').style.display = 
            document.querySelector('.menu-backdrop').style.display === 'block' ? 'none' : 'block';
    }
    
    function openTab(tabName) {
        // Hide all tab content
        const tabContents = document.getElementsByClassName('tab-content');
        for (let content of tabContents) {
            content.classList.remove('active');
        }
        
        // Remove active class from all buttons
        const tabButtons = document.getElementsByClassName('tab-button');
        for (let button of tabButtons) {
            button.classList.remove('active');
        }
        
        // Show the selected tab content and activate the button
        document.getElementById(tabName).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    </script>
</body>
</html>


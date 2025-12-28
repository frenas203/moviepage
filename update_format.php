<?php
require_once 'connect.php';

// Get and sanitize POST data
$movie_name = $_POST['movie_name'] ?? '';
$new_format = $_POST['new_format'] ?? '';

// Basic validation
if (empty($movie_name) || empty($new_format)) {
    header("Location: register_movies.php?error=missing_fields");
    exit();
}

// Prepare and execute the update query
$stmt = $conn->prepare("UPDATE physicalmovies SET format = ? WHERE name = ?");
if (!$stmt) {
    header("Location: register_movies.php?error=prepare_failed");
    exit();
}

$stmt->bind_param("ss", $new_format, $movie_name);

if ($stmt->execute()) {
    header("Location: register_movies.php?success=format_updated");
    exit();
} else {
    header("Location: register_movies.php?error=update_failed");
    exit();
}

$stmt->close();
$conn->close();
?>


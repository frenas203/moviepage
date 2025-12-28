<?php
require_once 'connect.php';

// Get and sanitize POST data
$movie_name = $_POST['movie_name'] ?? '';

// Basic validation
if (empty($movie_name)) {
    header("Location: register_movies.php?error=missing_fields");
    exit();
}

// Prepare and execute the delete query
$stmt = $conn->prepare("DELETE FROM physicalmovies WHERE name = ?");
if (!$stmt) {
    header("Location: register_movies.php?error=prepare_failed");
    exit();
}

$stmt->bind_param("s", $movie_name);

if ($stmt->execute()) {
    header("Location: register_movies.php?success=movie_deleted");
    exit();
} else {
    header("Location: register_movies.php?error=delete_failed");
    exit();
}

$stmt->close();
$conn->close();
?>


<?php
// Include the DB connection
require_once 'connect.php';

// Get and sanitize POST data
$name = $_POST['title'] ?? '';
$year = $_POST['year'] ?? '';
$format = $_POST['format'] ?? '';

// Basic validation
if (empty($name) || empty($year) || empty($format)) {
    header("Location: register_movies.php?error=missing_fields");
    exit();
}

if (!is_numeric($year)) {
    header("Location: register_movies.php?error=invalid_year");
    exit();
}

if ($format === "none") {
    header("Location: register_movies.php?error=invalid_format");
    exit();
}

// Database insert
$stmt = $conn->prepare("INSERT INTO physicalmovies (name, year, format) VALUES (?, ?, ?)");
if (!$stmt) {
    error_log("Database prepare failed: " . $conn->error);
    header("Location: register_movies.php?error=prepare_failed");
    exit();
}

$stmt->bind_param("sis", $name, $year, $format);

// Execute and handle result
if ($stmt->execute()) {
    header("Location: register_movies.php?success=added");
    exit();
} else {
    error_log("Database execution error: " . $stmt->error);
    header("Location: register_movies.php?error=execution_failed");
    exit();
}

$stmt->close();
$conn->close();
?>


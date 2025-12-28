<?php
// Include the DB connection
require_once 'connect.php';

// Check if we have a valid database connection
if (!$conn) {
    die("Database connection failed");
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="movies_collection_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers (column names)
fputcsv($output, array('Name', 'Year', 'Format'));

// Get sort parameter if provided
$sort = $_GET['sort'] ?? 'name_asc';
$format_filter = $_GET['format'] ?? '';
$search = $_GET['search'] ?? '';

// Build the SQL query with the same sorting logic as movies.php
$sortField = "CASE 
    WHEN name LIKE 'The %' 
    THEN CONCAT(SUBSTRING(name, 5), ', The')
    ELSE name 
END";

try {
    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($format_filter)) {
        $whereConditions[] = "format = ?";
        $params[] = $format_filter;
        $types .= 's';
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(name LIKE ? OR year LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    if (!empty($whereConditions)) {
        // With filters
        if ($sort == 'name_asc') {
            $sql = "SELECT name, year, format FROM physicalmovies $whereClause ORDER BY $sortField ASC";
        } else if ($sort == 'name_desc') {
            $sql = "SELECT name, year, format FROM physicalmovies $whereClause ORDER BY $sortField DESC";
        } else if ($sort == 'year_asc') {
            $sql = "SELECT name, year, format FROM physicalmovies $whereClause ORDER BY year ASC, $sortField ASC";
        } else if ($sort == 'year_desc') {
            $sql = "SELECT name, year, format FROM physicalmovies $whereClause ORDER BY year DESC, $sortField ASC";
        } else {
            // Default sort
            $sql = "SELECT name, year, format FROM physicalmovies $whereClause ORDER BY $sortField ASC";
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    } else {
        // No filters
        if ($sort == 'name_asc') {
            $sql = "SELECT name, year, format FROM physicalmovies ORDER BY $sortField ASC";
        } else if ($sort == 'name_desc') {
            $sql = "SELECT name, year, format FROM physicalmovies ORDER BY $sortField DESC";
        } else if ($sort == 'year_asc') {
            $sql = "SELECT name, year, format FROM physicalmovies ORDER BY year ASC, $sortField ASC";
        } else if ($sort == 'year_desc') {
            $sql = "SELECT name, year, format FROM physicalmovies ORDER BY year DESC, $sortField ASC";
        } else {
            // Default sort
            $sql = "SELECT name, year, format FROM physicalmovies ORDER BY $sortField ASC";
        }
        
        $stmt = $conn->prepare($sql);
    }
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Write each row to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['name'],
            $row['year'], 
            $row['format']
        ));
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // If there's an error, we need to clear any headers and show error
    if (!headers_sent()) {
        header_remove();
        header('Content-Type: text/html');
    }
    die("Export failed: " . $e->getMessage());
}

// Close the output stream and database connection
fclose($output);
$conn->close();
?>


<?php
session_start();
require_once "db.php";

// Only admins can delete books.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Admin login required.");
}

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Invalid request method.");
}

// Validate book ID.
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid book ID.");
}

// Check whether the book exists.
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, title FROM books WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$book) {
    header("Location: books.php?error=not_found");
    exit;
}

// Check whether this book has any borrowing history.
$check = mysqli_prepare(
    $conn,
    "SELECT id FROM issued_books WHERE book_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($check, "i", $id);
mysqli_stmt_execute($check);

$historyResult = mysqli_stmt_get_result($check);
$hasHistory = mysqli_num_rows($historyResult) > 0;

mysqli_stmt_close($check);

// Preserve books that have borrowing records.
if ($hasHistory) {
    header("Location: books.php?error=book_has_history");
    exit;
}

// Delete books that have no borrowing history.
$delete = mysqli_prepare(
    $conn,
    "DELETE FROM books WHERE id = ?"
);

mysqli_stmt_bind_param($delete, "i", $id);

if (mysqli_stmt_execute($delete)) {
    mysqli_stmt_close($delete);
    header("Location: books.php?deleted=1");
    exit;
}

mysqli_stmt_close($delete);

// Handle any unexpected database error.
error_log("Book deletion failed for book ID: " . $id);

header("Location: books.php?error=delete_failed");
exit;
?>
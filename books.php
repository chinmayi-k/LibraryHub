
<?php
session_start();
require_once "db.php";

// Identify whether the logged-in user is an admin.
$isAdmin = isset($_SESSION["user_id"])
    && ($_SESSION["role"] ?? "") === "admin";

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");

// Get categories that exist in the database.
$categoryQuery = mysqli_query(
    $conn,
    "SELECT DISTINCT category
     FROM books
     WHERE category IS NOT NULL
       AND category <> ''
     ORDER BY category ASC"
);

$categories = [];

while ($row = mysqli_fetch_assoc($categoryQuery)) {
    $categories[] = $row["category"];
}

// Build the book search query safely.
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (
        title LIKE ?
        OR author LIKE ?
        OR category LIKE ?
        OR isbn LIKE ?
    )";

    $term = "%" . $search . "%";

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= "ssss";
}

if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Books | LibraryHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .admin-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .admin-actions a,
        .admin-actions button,
        .add-book-button {
            display: inline-block;
            padding: 10px 14px;
            border: none;
            border-radius: 6px;
            background: #234b3a;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .admin-actions .delete-button {
            background: #a52a2a;
        }

        .add-book-area {
            margin: 20px 0;
        }

        .book-stock {
            margin-top: 12px;
        }
        .availability {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.availability.available {
    background: #e2f5e9;
    color: #176b37;
}

.availability.unavailable {
    background: #fde8e8;
    color: #a52a2a;
}
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">📚 LibraryHub</a>

    <nav>
        <a href="index.php">Home</a>
        <a href="books.php">Books</a>

        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<section class="catalogue">

    <p class="section-label">DISCOVER YOUR NEXT READ</p>

    <h1>Our Book Collection</h1>

    <p class="catalogue-description">
        Explore our collection and find a book that inspires you.
    </p>

    <?php if (isset($_GET["deleted"])): ?>
        <p role="status">Book deleted successfully.</p>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <div class="add-book-area">
            <a class="add-book-button" href="add_book.php">
                + Add New Book
            </a>
        </div>
    <?php endif; ?>

   <form action="books.php" method="GET" class="search-form">

    <input
        type="text"
        name="search"
        placeholder="Search by title, author, category, or ISBN..."
        value="<?= h($search) ?>"
    >

    <select name="category" aria-label="Filter by category">
        <option value="">All Categories</option>

        <?php foreach ($categories as $cat): ?>
            <option
                value="<?= h($cat) ?>"
                <?= $category === $cat ? "selected" : "" ?>
            >
                <?= h($cat) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Search</button>

    <a href="books.php">Clear Filters</a>

</form>

    <div class="book-grid">

        <?php if ($result && mysqli_num_rows($result) > 0): ?>

            <?php while ($book = mysqli_fetch_assoc($result)): ?>

                <div class="book-card">

                    <div class="book-icon">📖</div>

                    <p class="book-category">
                        <?= h($book["category"]) ?>
                    </p>

                    <h2><?= h($book["title"]) ?></h2>

                    <p class="book-author">
                        By <?= h($book["author"]) ?>
                    </p>

                
<p class="book-stock">
    Available:
    <?= (int)$book["available"] ?>
    /
    <?= (int)$book["quantity"] ?>
</p>

<?php if ((int)$book["available"] > 0): ?>
    <span class="availability available">
        ● Available
    </span>
<?php else: ?>
    <span class="availability unavailable">
        ● Out of Stock
    </span>
<?php endif; ?>


                    <?php if ($isAdmin): ?>
                        <div class="admin-actions">

                            <a href="edit_book.php?id=<?= (int)$book["id"] ?>">
                                ✏️ Edit
                            </a>

                            <form
                                action="delete_book.php"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this book?');"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$book["id"] ?>"
                                >

                                <button class="delete-button" type="submit">
                                    🗑️ Delete
                                </button>
                            </form>

                        </div>
                    <?php endif; ?>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No books found in the library.</p>

        <?php endif; ?>

    </div>
</section>

<footer>
    <p>&copy; <?= date("Y") ?> LibraryHub Library Management System.</p>
</footer>

</body>
</html>

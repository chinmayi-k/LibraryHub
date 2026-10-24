<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars(
    $_SESSION["full_name"],
    ENT_QUOTES,
    "UTF-8"
);

$role = $_SESSION["role"];
// Student borrowing statistics
if ($role === "student") {

    $student_id = (int) $_SESSION["user_id"];

    $studentBooksQuery = mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS borrowed_books,
            COALESCE(
                SUM(CASE
                    WHEN due_date < CURDATE() THEN 1
                    ELSE 0
                END),
                0
            ) AS overdue_books
         FROM issued_books
         WHERE user_id = $student_id
           AND status = 'issued'"
    );

    $studentBooks = mysqli_fetch_assoc($studentBooksQuery);
}

if ($role === "admin") {

    // Book statistics
    $query = mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS total_titles,
            COALESCE(SUM(quantity), 0) AS total_copies,
            COALESCE(SUM(available), 0) AS available_copies
         FROM books"
    );

    $stats = mysqli_fetch_assoc($query);

    // Issued and overdue book statistics
    $issuedQuery = mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS issued_books,
            COALESCE(
                SUM(CASE
                    WHEN due_date < CURDATE() THEN 1
                    ELSE 0
                END),
                0
            ) AS overdue_books
         FROM issued_books
         WHERE status = 'issued'"
    );

    $issuedStats = mysqli_fetch_assoc($issuedQuery);

    // Registered student count
    $studentsQuery = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total_students
         FROM users
         WHERE role = 'student'"
    );

    $studentStats = mysqli_fetch_assoc($studentsQuery);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard | LibraryHub</title>

    <link rel="stylesheet" href="style.css">

    <style>
        .dashboard {
            max-width: 1200px;
            margin: 40px auto;
            padding: 25px;
        }

        .dashboard h1 {
            color: #234b3a;
            margin-bottom: 5px;
        }

        .dashboard h2 {
            color: #234b3a;
        }

        .stats {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
            border-top: 4px solid #234b3a;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
        }

        .stat-card h3 {
            color: #426b5a;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .stat-card p {
            font-size: 32px;
            font-weight: bold;
            color: #234b3a;
        }

        .dashboard-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .dashboard-links a {
            display: inline-block;
            padding: 12px 20px;
            background: #234b3a;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            transition: background 0.2s;
        }

        .dashboard-links a:hover {
            background: #356b52;
        }

        .dashboard-description {
            color: #555555;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            .dashboard {
                margin: 15px auto;
                padding: 15px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="dashboard">

    <h1>Welcome, <?= $name ?>!</h1>

    <?php if ($role === "admin"): ?>

        <h2>Admin Dashboard</h2>

        <p class="dashboard-description">
            Manage your library and monitor book availability.
        </p>

        <div class="stats">

            <div class="stat-card">
                <h3>Total Titles</h3>
                <p>
                    <?= (int)$stats["total_titles"] ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Total Copies</h3>
                <p>
                    <?= (int)$stats["total_copies"] ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Available Copies</h3>
                <p>
                    <?= (int)$stats["available_copies"] ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Currently Issued</h3>
                <p>
                    <?= (int)$issuedStats["issued_books"] ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Registered Students</h3>
                <p>
                    <?= (int)$studentStats["total_students"] ?>
                </p>
            </div>

            <div class="stat-card">
                <h3>Overdue Books</h3>
                <p>
                    <?= (int)$issuedStats["overdue_books"] ?>
                </p>
            </div>

        </div>

        <div class="dashboard-links">
            <a href="books.php">View Books</a>

            <a href="issue_book.php">Issue Book</a>

            <a href="manage_issues.php">
                Manage Issued Books
            </a>

            <a href="index.php">Home</a>

            <a href="logout.php">Logout</a>
        </div>

    <?php elseif ($role === "student"): ?>

        <h2>Student Dashboard</h2>

        <p class="dashboard-description">
            Explore our collection and discover your next favorite book.
        </p>
        <div class="stats">

    <div class="stat-card">
        <h3>Currently Borrowed</h3>
        <p><?= (int)$studentBooks["borrowed_books"] ?></p>
    </div>

    <div class="stat-card">
        <h3>Overdue Books</h3>
        <p><?= (int)$studentBooks["overdue_books"] ?></p>
    </div>

</div>

        <div class="dashboard-links">
            <a href="books.php">Browse Books</a>

            <a href="my_books.php">My Borrowed Books</a>

            <a href="index.php">Home</a>

            <a href="logout.php">Logout</a>
        </div>

    <?php else: ?>

        <h2>Access denied</h2>

        <p>Your account role is not recognized.</p>

    <?php endif; ?>

</div>

</body>
</html>
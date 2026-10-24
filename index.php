
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LibaryHub | Library Management System</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">📚 LibaryHub</a>

        <nav>
            <a href="index.php">Home</a>
            <a href="books.php">Books</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a href="login.php" class="login-btn">Login</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <p class="tagline">YOUR WORLD OF KNOWLEDGE</p>

            <h1>Every Book Opens<br>A New World.</h1>

            <p class="description">
                Discover inspiring stories, explore new ideas,
                and find your next favourite book.
            </p>

            <a href="books.php" class="explore-btn">
                Explore Our Collection →
            </a>
        </section>

        <section class="welcome" id="about">
            <p class="section-label">WELCOME TO LibaryHub</p>

            <h2>A little reading goes a long way.</h2>

            <p>
                Discover books, explore different subjects,
                and make reading part of your everyday life.
            </p>
        </section>
    </main>

    <footer id="contact">
        <p>
            &copy; <?php echo date("Y"); ?>
            LibraryHub Library Management System.
        </p>
    </footer>

</body>
</html>

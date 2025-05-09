<?php
// Start the session at the beginning of each page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Support System</title>
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --border-radius: 10px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        header {
            background-color: grey;
            padding: 15px 30px;
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        nav li {
            display: inline;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 1em;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            background-color: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .container h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .container p {
            font-size: 1.2em;
        }

        @media (max-width: 600px) {
            nav ul {
                flex-direction: column;
                gap: 10px;
            }

            .container {
                margin: 30px 15px;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    
    <header>
    <nav>
    <ul>
        <!-- Home link visible to all users -->
        <li><a href="index.php">Home</a></li>

        <?php if (isset($_SESSION['role'])): ?>
            <!-- If the user is logged in, display the appropriate links based on role -->

            <?php if ($_SESSION['role'] == 'user'): ?>
                <!-- Links visible only to regular users -->
                <li><a href="make-ticket.php">Make Ticket</a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'helpDesk' || $_SESSION['role'] == 'admin'): ?>
                <!-- Links visible to admins and helpdesk -->
                <li><a href="check-tickets.php">Check Tickets</a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'helpDesk' || $_SESSION['role'] == 'admin'): ?>
                <!-- Links visible to admins and helpdesk -->
                <li><a href="map.php">Map</a></li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'admin'): ?>
                <!-- Link visible only to admins -->
                <li><a href="stats.php">Stats</a></li>
            <?php endif; ?>

            <!-- Logout link displayed for logged-in users -->
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <!-- Login link displayed for non-logged-in users -->
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
    </header>

    <div class="container">
        <h1>Welcome to Our Support System</h1>
        <?php if (isset($_SESSION['username'])): ?>
            <p>Welcome, <?php echo $_SESSION['username']; ?>! You are logged in as <?php echo $_SESSION['role']; ?>.</p>
        <?php else: ?>
            <p>Please log in to continue.</p>
        <?php endif; ?>
    </div>
</body>
</html>
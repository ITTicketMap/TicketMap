<?php
session_start();

// Hardcoded users (username => [password, role])
$users = [
    'admin' => ['password' => 'admin123', 'role' => 'admin'],
    'helpdesk' => ['password' => 'help123', 'role' => 'helpDesk'],
    'user' => ['password' => 'user123', 'role' => 'user']
];

// Process login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];

    // Check if username exists and password matches
    if (isset($users[$inputUsername]) && $inputPassword === $users[$inputUsername]['password']) {
        // Set session variables
        $_SESSION['username'] = $inputUsername;
        $_SESSION['role'] = $users[$inputUsername]['role'];

        // Redirect to make-ticket.php for all users
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        .login-container {
            max-width: 400px;
            background-color: white;
            margin: 60px auto;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .login-container h2 {
            color: var(--primary-color);
            margin-bottom: 25px;
        }

        .login-container label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        .login-container input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            width: 100%;
        }

        .login-container input[type="submit"]:hover {
            background-color: #1565c0;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }

        @media (max-width: 600px) {
            .login-container {
                margin: 30px 15px;
                padding: 25px;
            }

            nav ul {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>

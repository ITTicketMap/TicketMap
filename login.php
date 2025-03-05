<?php
session_start();

$servername = "localhost";
$username = "root"; // Whatever the database username is 
$password = "";     // Whatever the database password is 
$dbname = "SupportSystem"; // The name of the database

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];


    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($inputPassword == $user['password']) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: check-tickets.html");
            } else {
                header("Location: make-ticket.html");
            }
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User does not exist!";
    }

    $stmt->close();
}

$conn->close();
?>

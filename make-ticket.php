<?php
// Start the session at the beginning of each page
session_start();

// Check if user is logged in and has user role
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'user' && $_SESSION['role'] != 'admin' && $_SESSION['role'] != 'helpDesk')) {
    // Redirect to login page if not logged in or not authorized
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Ticket</title>
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

        .ticket-container {
            width: 80%;
            max-width: 600px;
            margin: 60px auto;
            padding: 20px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .ticket-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .ticket-label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .ticket-input, .ticket-textarea, .ticket-select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .ticket-textarea {
            height: 100px;
            resize: none;
        }

        .ticket-button {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .ticket-button:hover {
            background-color: #0056b3;
        }

        .result-message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            display: none;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 600px) {
            nav ul {
                flex-direction: column;
                gap: 10px;
            }

            .ticket-container {
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

    <!-- Form to create a ticket -->
    <div class="ticket-container">
        <h2>Make a Ticket</h2>
        <form id="ticketForm">
            <label for="userName" class="ticket-label">Your Name:</label>
            <input type="text" id="userName" name="userName" class="ticket-input" required>

            <label for="userEmail" class="ticket-label">Your Email:</label>
            <input type="email" id="userEmail" name="userEmail" class="ticket-input" required>

            <label for="ticketTitle" class="ticket-label">Ticket Title:</label>
            <input type="text" id="ticketTitle" name="ticketTitle" class="ticket-input" required>

            <label for="ticketDescription" class="ticket-label">Description:</label>
            <textarea id="ticketDescription" name="ticketDescription" class="ticket-textarea" required></textarea>

            <!-- Priority field removed as requested -->
            <input type="hidden" id="ticketPriority" name="ticketPriority" value="medium">

            <button type="submit" class="ticket-button">Submit Ticket</button>
        </form>
        <div id="resultMessage" class="result-message"></div>
    </div>

    <script>
        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const userName = document.getElementById('userName').value;
            const userEmail = document.getElementById('userEmail').value;
            const ticketTitle = document.getElementById('ticketTitle').value;
            const ticketDescription = document.getElementById('ticketDescription').value;
            const ticketPriority = document.getElementById('ticketPriority').value;
            
            // Show loading message
            const resultDiv = document.getElementById('resultMessage');
            resultDiv.className = 'result-message';
            resultDiv.innerText = 'Submitting ticket...';
            resultDiv.style.display = 'block';
            
            // Create URL-encoded form data (alternative to FormData)
            const formBody = new URLSearchParams();
            formBody.append('userName', userName);
            formBody.append('userEmail', userEmail);
            formBody.append('ticketTitle', ticketTitle);
            formBody.append('ticketDescription', ticketDescription);
            formBody.append('ticketPriority', ticketPriority);
            
            // Submit ticket using URL-encoded form data and explicit server address
            fetch('http://localhost:5000/create-ticket', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formBody
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    resultDiv.className = 'result-message success';
                    resultDiv.innerText = `Ticket created successfully! Your ticket number is #${data.ticket_id}`;
                    document.getElementById('ticketForm').reset();
                } else {
                    resultDiv.className = 'result-message error';
                    resultDiv.innerText = data.message || 'Error creating ticket. Please try again.';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.className = 'result-message error';
                resultDiv.innerText = 'Network error.';
            });
        });
    </script>

</body>
</html>
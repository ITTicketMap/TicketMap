<?php
// Start the session at the beginning of each page
session_start();

// Check if user is logged in and has helpdesk or admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin')) {
    // Redirect to login page if not logged in or not authorized
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Statistics</title>
    <style>
        :root {
            --primary-color: #1e88e5;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --border-radius: 10px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

        main {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1, h2, h3 {
            color: var(--primary-color);
        }

        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            flex: 1;
            min-width: 250px;
        }

        .stats-card h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .admin-stats {
            margin-top: 10px;
        }

        .admin-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .admin-name {
            font-weight: bold;
        }

        .chart-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 30px;
            height: 400px;
            width: 100%;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-style: italic;
            color: #757575;
        }

        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .success-message {
            color: #388e3c;
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }

        .refresh-button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            margin-bottom: 20px;
            display: block;
        }

        .refresh-button:hover {
            background-color: #1565c0;
        }

        #responseTimeChart {
            width: 100%;
            height: 100%;
        }

        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
            }
            
            .stats-card {
                width: 100%;
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

<main>
    <h1><center>Admin Ticket Statistics</center></h1>
    
    <button id="refreshStatsBtn" class="refresh-button">Refresh Statistics</button>
    
    <div id="statusMessage"></div>
    
    <div class="stats-container">
        <div class="stats-card">
            <h3>Tickets Handled (Last 24 Hours)</h3>
            <div id="dailyStats" class="admin-stats">
                <div class="loading">Loading stats...</div>
            </div>
        </div>
        
        <div class="stats-card">
            <h3>Tickets Handled (Last 7 Days)</h3>
            <div id="weeklyStats" class="admin-stats">
                <div class="loading">Loading stats...</div>
            </div>
        </div>
        
        <div class="stats-card">
            <h3>Tickets Handled (Last 30 Days)</h3>
            <div id="monthlyStats" class="admin-stats">
                <div class="loading">Loading stats...</div>
            </div>
        </div>
    </div>
    
    <div class="chart-container">
        <h2>Average Response Time (Hours)</h2>
        <canvas id="responseTimeChart"></canvas>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
<script>
    let responseTimeChart = null;

    // Helper function to show status messages
    function showStatusMessage(message, isError = false) {
        const statusElement = document.getElementById("statusMessage");
        statusElement.className = isError ? "error-message" : "success-message";
        statusElement.innerHTML = message;
        
        // Clear message after 5 seconds
        setTimeout(() => {
            statusElement.innerHTML = "";
            statusElement.className = "";
        }, 5000);
    }

    // Fetch admin statistics from the server
    async function fetchAdminStats() {
        try {
            document.getElementById("dailyStats").innerHTML = '<div class="loading">Loading stats...</div>';
            document.getElementById("weeklyStats").innerHTML = '<div class="loading">Loading stats...</div>';
            document.getElementById("monthlyStats").innerHTML = '<div class="loading">Loading stats...</div>';
            
            const response = await fetch("http://127.0.0.1:5000/admin-stats");
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const stats = await response.json();
            
            // Populate daily stats
            populateAdminStats("dailyStats", stats.daily);
            
            // Populate weekly stats
            populateAdminStats("weeklyStats", stats.weekly);
            
            // Populate monthly stats
            populateAdminStats("monthlyStats", stats.monthly);
            
            // Create or update response time chart
            createResponseTimeChart(stats.response_times);
            
        } catch (error) {
            console.error("Error fetching admin statistics:", error);
            showStatusMessage(`Failed to load statistics: ${error.message}`, true);
            
            document.getElementById("dailyStats").innerHTML = 'Could not load statistics';
            document.getElementById("weeklyStats").innerHTML = 'Could not load statistics';
            document.getElementById("monthlyStats").innerHTML = 'Could not load statistics';
            
            // Show dummy data for chart if no data is available
            createDummyResponseTimeChart();
        }
    }

    // Populate admin statistics in a container
    function populateAdminStats(containerId, stats) {
        const container = document.getElementById(containerId);
        
        if (!stats || stats.length === 0) {
            container.innerHTML = '<p>No data available</p>';
            return;
        }
        
        let html = '';
        stats.forEach(item => {
            html += `
                <div class="admin-row">
                    <div class="admin-name">${item.admin}</div>
                    <div class="admin-count">${item.count} tickets</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Create dummy data for chart if API fails
    function createDummyResponseTimeChart() {
        const dummyData = [
            { admin: "John Doe", avg_hours: 2.4 },
            { admin: "Jane Smith", avg_hours: 1.8 },
            { admin: "Bob Johnson", avg_hours: 3.2 },
            { admin: "Alice Williams", avg_hours: 1.2 }
        ];
        
        createResponseTimeChart(dummyData);
        
        // Add notification that this is dummy data
        showStatusMessage("Unable to load real statistics. Showing sample data for demonstration purposes.", true);
    }

    // Create or update the response time chart
    function createResponseTimeChart(data) {
        const ctx = document.getElementById('responseTimeChart').getContext('2d');
        
        // If chart already exists, destroy it before recreating
        if (responseTimeChart) {
            responseTimeChart.destroy();
        }
        
        // Handle empty data case
        if (!data || data.length === 0) {
            // Create a chart with a "No data available" message
            responseTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['No data available'],
                    datasets: [{
                        label: 'No data',
                        data: [0],
                        backgroundColor: 'rgba(200, 200, 200, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'No response time data available'
                        }
                    }
                }
            });
            return;
        }
        
        // Prepare data for chart
        const labels = data.map(item => item.admin);
        const values = data.map(item => item.avg_hours);
        
        // Create chart with the data
        responseTimeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Response Time (Hours)',
                    data: values,
                    backgroundColor: 'rgba(30, 136, 229, 0.7)',
                    borderColor: 'rgba(30, 136, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Admin'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Add event listener for the refresh button
    document.getElementById("refreshStatsBtn").addEventListener("click", fetchAdminStats);

    // Load stats when the page is ready
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Document loaded, fetching stats...");
        fetchAdminStats();
    });
</script>

</body>
</html>
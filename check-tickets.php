<?php
// Start the session at the beginning of each page
session_start();

// Check if user is logged in and has helpdesk or admin role
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'helpDesk' && $_SESSION['role'] != 'admin')) {
    // Redirect to login page if not logged in or not authorized
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Tickets</title>
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
        }

        h1, h2, h3 {
            color: var(--primary-color);
        }

        .ticket-container, #ticketDetails {
            max-width: 800px;
            margin: auto;
        }

        .ticket {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }

        .ticket:hover {
            transform: scale(1.01);
        }

        .ticket h3 {
            margin: 0 0 10px;
        }

        .ticket a {
            text-decoration: none;
            color: var(--primary-color);
        }

        .ticket a:hover {
            text-decoration: underline;
        }

        #ticketDetails {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
        }

        #ticketDetails p {
            margin: 8px 0;
        }

        #messageThread {
            margin-top: 15px;
        }

        .message {
            background-color: #e3f2fd;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .message.admin {
            background-color: #e8f5e9;
            border-left: 5px solid #43a047;
        }

        .message strong {
            display: block;
            color: #0d47a1;
            margin-bottom: 5px;
        }

        .message.admin strong {
            color: #2e7d32;
        }

        textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            font-size: 1em;
            border-radius: 6px;
            border: 1px solid #ccc;
            resize: vertical;
            margin-top: 10px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #1565c0;
        }

        select {
            padding: 8px;
            font-size: 1em;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin: 5px 0 15px 0;
            display: block;
        }

        a {
            display: inline-block;
            color: var(--primary-color);
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .delete-btn {
            background-color: #e53935;
        }

        .delete-btn:hover {
            background-color: #c62828;
        }

        .update-btn {
            background-color: #43a047;
        }

        .update-btn:hover {
            background-color: #2e7d32;
        }

        .file-upload {
            margin: 15px 0;
        }

        .attachments {
            margin-top: 15px;
        }

        .attachment {
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .attachment a {
            margin-top: 0;
        }

        .loading {
            text-align: center;
            padding: 20px;
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

        @media (max-width: 600px) {
            nav ul {
                flex-direction: column;
                gap: 10px;
            }

            textarea {
                height: 100px;
            }

            main {
                padding: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
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
    <h1><center>IT Map Support Tickets</center></h1>
    <div class="action-buttons" style="justify-content: center; margin-bottom: 20px;">
        <button id="refreshTicketsBtn" class="update-btn">Refresh Tickets</button>
    </div>
    
    <div id="statusMessage"></div>
    <div id="ticketList" class="ticket-container"></div>

    <div id="ticketDetails" style="display: none;">
        <h2>Ticket Details</h2>
        <p><strong>Submitted By:</strong> <span id="ticketName"></span></p>
        <p><strong>Email:</strong> <span id="ticketEmail"></span></p>

        <p><strong>Category:</strong>
            <select id="categorySelect">
                <option value="General">General</option>
                <option value="Hardware">Hardware</option>
                <option value="Software">Software</option>
                <option value="Network">Network</option>
                <option value="Other">Other</option>
            </select>
        </p>

        <p><strong>Status:</strong>
            <select id="statusSelect">
                <option value="New">New</option>
                <option value="In Progress">In Progress</option>
                <option value="On Hold">On Hold</option>
                <option value="Resolved">Resolved</option>
                <option value="Closed">Closed</option>
            </select>
        </p>

        <p><strong>Assigned To:</strong> 
            <select id="assignSelect">
                <option value="Unassigned">Unassigned</option>
                <option value="John Doe">John Doe</option>
                <option value="Jane Smith">Jane Smith</option>
                <option value="Bob Johnson">Bob Johnson</option>
                <option value="Alice Williams">Alice Williams</option>
            </select>
        </p>

        <p><strong>Subject:</strong> <span id="ticketSubject"></span></p>

        <h3>Attachments:</h3>
        <div id="attachmentsList" class="attachments"></div>

        <h3>Add Attachment:</h3>
        <div class="file-upload">
            <input type="file" id="fileAttachment">
            <button id="uploadAttachmentBtn">Upload Attachment</button>
        </div>

        <h3>Messages:</h3>
        <div id="messageThread"></div>

        <h3>Admin Response:</h3>
        <textarea id="ticketResponse" placeholder="Write your response here..."></textarea><br>
        
        <div class="action-buttons">
            <button id="submitResponseBtn">Submit Response</button>
            <button id="saveChangesBtn">Save Changes</button>
            <button id="deleteTicketBtn" class="delete-btn">Delete Ticket</button>
        </div>
        
        <a href="javascript:void(0);" onclick="backToTickets()">← Back to ticket list</a>
    </div>
</main>

<script>
    let allTickets = [];
    let currentTicketId = null;
    let currentTicket = null;

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

    async function fetchTickets() {
        try {
            // Show loading indicator
            document.getElementById("ticketList").innerHTML = '<div class="loading">Loading tickets...</div>';
            
            const response = await fetch("http://127.0.0.1:5000/tickets");
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            allTickets = await response.json();

            let ticketListHtml = "";
            if (allTickets.length === 0) {
                ticketListHtml = "<p>No tickets found</p>";
            } else {
                allTickets.forEach(ticket => {
                    // Make sure all properties exist
                    const status = ticket.status || "New";
                    const category = ticket.category || "General";
                    const assignedTo = ticket.assigned_to || "Unassigned";
                    
                    ticketListHtml += `
                        <div class="ticket" id="ticket-${ticket.id}">
                            <h3><a href="javascript:void(0);" onclick="showTicketDetails('${ticket.id}')">Ticket #${ticket.id}: ${ticket.subject}</a></h3>
                            <p><strong>Status:</strong> ${status}</p>
                            <p><strong>Submitted By:</strong> ${ticket.sender}</p>
                            <p><strong>Category:</strong> <span id="category-${ticket.id}">${category}</span></p>
                            <p><strong>Assigned To:</strong> <span id="assigned-${ticket.id}">${assignedTo}</span></p>
                        </div>
                    `;
                });
            }

            document.getElementById("ticketList").innerHTML = ticketListHtml;
        } catch (error) {
            console.error("Error fetching tickets:", error);
            document.getElementById("ticketList").innerHTML = `
                <div class="error-message">
                    Error loading tickets. Please try again later.
                    <p>Details: ${error.message}</p>
                </div>
            `;
        }
    }

    async function showTicketDetails(ticketId) {
        currentTicketId = ticketId;
        
        try {
            document.getElementById("ticketDetails").innerHTML = '<div class="loading">Loading ticket details...</div>';
            document.getElementById("ticketList").style.display = "none";
            document.getElementById("ticketDetails").style.display = "block";
            
            const response = await fetch(`http://127.0.0.1:5000/tickets/${ticketId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            currentTicket = await response.json();
            
            // Reset the details view to its original state
            document.getElementById("ticketDetails").innerHTML = `
                <h2>Ticket Details</h2>
                <p><strong>Submitted By:</strong> <span id="ticketName"></span></p>
                <p><strong>Email:</strong> <span id="ticketEmail"></span></p>

                <p><strong>Category:</strong>
                    <select id="categorySelect">
                        <option value="General">General</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Network">Network</option>
                        <option value="Other">Other</option>
                    </select>
                </p>

                <p><strong>Status:</strong>
                    <select id="statusSelect">
                        <option value="New">New</option>
                        <option value="In Progress">In Progress</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </p>

                <p><strong>Assigned To:</strong> 
                    <select id="assignSelect">
                        <option value="Unassigned">Unassigned</option>
                        <option value="John Doe">John Doe</option>
                        <option value="Jane Smith">Jane Smith</option>
                        <option value="Bob Johnson">Bob Johnson</option>
                        <option value="Alice Williams">Alice Williams</option>
                    </select>
                </p>

                <p><strong>Subject:</strong> <span id="ticketSubject"></span></p>

                <h3>Attachments:</h3>
                <div id="attachmentsList" class="attachments"></div>

                <h3>Add Attachment:</h3>
                <div class="file-upload">
                    <input type="file" id="fileAttachment">
                    <button id="uploadAttachmentBtn">Upload Attachment</button>
                </div>

                <h3>Messages:</h3>
                <div id="messageThread"></div>

                <h3>Admin Response:</h3>
                <textarea id="ticketResponse" placeholder="Write your response here..."></textarea><br>
                
                <div class="action-buttons">
                    <button id="submitResponseBtn">Submit Response</button>
                    <button id="saveChangesBtn">Save Changes</button>
                    <button id="deleteTicketBtn" class="delete-btn">Delete Ticket</button>
                </div>
                
                <a href="javascript:void(0);" onclick="backToTickets()">← Back to ticket list</a>
            `;

            // Populate ticket details
            document.getElementById("ticketName").textContent = currentTicket.sender;
            document.getElementById("ticketEmail").textContent = currentTicket.email || "N/A";
            document.getElementById("ticketSubject").textContent = currentTicket.subject;
            document.getElementById("ticketResponse").value = currentTicket.admin_response || "";

            // Set form values
            const categorySelect = document.getElementById("categorySelect");
            const statusSelect = document.getElementById("statusSelect");
            const assignSelect = document.getElementById("assignSelect");

            categorySelect.value = currentTicket.category || "General";
            statusSelect.value = currentTicket.status || "New";
            assignSelect.value = currentTicket.assigned_to || "Unassigned";

            // Display attachments if any
            const attachmentsList = document.getElementById("attachmentsList");
            if (currentTicket.attachments && currentTicket.attachments.length > 0) {
                let attachmentsHtml = "";
                currentTicket.attachments.forEach((attachment, index) => {
                    attachmentsHtml += `
                        <div class="attachment">
                            <span>${attachment.filename}</span>
                            <div>
                                <a href="http://127.0.0.1:5000${attachment.url}" target="_blank">Download</a>
                                <button onclick="deleteAttachment('${ticketId}', ${index})">Remove</button>
                            </div>
                        </div>
                    `;
                });
                attachmentsList.innerHTML = attachmentsHtml;
            } else {
                attachmentsList.innerHTML = "<p>No attachments</p>";
            }

            // Display message thread
            let threadHtml = "";
            if (currentTicket.messages && currentTicket.messages.length > 0) {
                currentTicket.messages.forEach(msg => {
                    const messageType = msg.type === "admin" ? "admin" : "";
                    threadHtml += `
                        <div class="message ${messageType}">
                            <strong>${msg.from}:</strong>
                            <p>${msg.body.replace(/\n/g, "<br>")}</p>
                        </div>
                    `;
                });
            } else {
                threadHtml = "<p>No message history available</p>";
            }
            document.getElementById("messageThread").innerHTML = threadHtml;
            
            // Set up button event handlers
            document.getElementById("submitResponseBtn").onclick = submitResponse;
            document.getElementById("saveChangesBtn").onclick = saveTicketChanges;
            document.getElementById("deleteTicketBtn").onclick = deleteTicket;
            document.getElementById("uploadAttachmentBtn").onclick = uploadAttachment;
        } catch (error) {
            console.error("Error fetching ticket details:", error);
            document.getElementById("ticketDetails").innerHTML = `
                <div class="error-message">
                    Error loading ticket details. Please try again later.
                    <p>Details: ${error.message}</p>
                </div>
                <a href="javascript:void(0);" onclick="backToTickets()">← Back to ticket list</a>
            `;
        }
    }

    async function saveTicketChanges() {
        if (!currentTicketId) return;
        
        const categoryValue = document.getElementById("categorySelect").value;
        const statusValue = document.getElementById("statusSelect").value;
        const assignedValue = document.getElementById("assignSelect").value;

        try {
            const response = await fetch(`http://127.0.0.1:5000/tickets/${currentTicketId}`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    category: categoryValue,
                    status: statusValue,
                    assigned_to: assignedValue
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // Update local data
            if (currentTicket) {
                currentTicket.category = categoryValue;
                currentTicket.status = statusValue;
                currentTicket.assigned_to = assignedValue;
            }
            
            showStatusMessage("Ticket details saved successfully!");
            
            // Refresh ticket list in the background
            fetchTickets();
        } catch (error) {
            console.error("Error saving ticket changes:", error);
            showStatusMessage(`Failed to save changes: ${error.message}`, true);
        }
    }

    async function submitResponse() {
        if (!currentTicketId || !currentTicket) return;
        
        const responseText = document.getElementById("ticketResponse").value;
        const subject = currentTicket.subject;

        if (!responseText.trim()) {
            showStatusMessage("Please enter a response before submitting.", true);
            return;
        }

        try {
            const response = await fetch(`http://127.0.0.1:5000/tickets/${currentTicketId}`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ 
                    response: responseText, 
                    subject: subject 
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const result = await response.json();
            showStatusMessage(result.message || "Response submitted successfully!");
            
            // Add the response to the message thread
            const messageThread = document.getElementById("messageThread");
            const newMessage = document.createElement("div");
            newMessage.className = "message admin";
            newMessage.innerHTML = `
                <strong>Admin:</strong>
                <p>${responseText.replace(/\n/g, "<br>")}</p>
            `;
            messageThread.appendChild(newMessage);
            
            // Clear the response field
            document.getElementById("ticketResponse").value = "";
            
            // Save current response to memory
            if (currentTicket) {
                currentTicket.admin_response = responseText;
                if (!currentTicket.messages) {
                    currentTicket.messages = [];
                }
                currentTicket.messages.push({
                    from: "Admin",
                    body: responseText,
                    type: "admin"
                });
            }
            
            // Refresh tickets in the background
            fetchTickets();
        } catch (error) {
            console.error("Error submitting response:", error);
            showStatusMessage(`Failed to submit response: ${error.message}`, true);
        }
    }

    async function deleteTicket() {
        if (!currentTicketId) return;
        
        if (!confirm("Are you sure you want to delete this ticket? This action cannot be undone.")) {
            return;
        }

        try {
            const response = await fetch(`http://127.0.0.1:5000/tickets/${currentTicketId}`, {
                method: "DELETE"
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            showStatusMessage("Ticket deleted successfully!");
            backToTickets();
            fetchTickets();
        } catch (error) {
            console.error("Error deleting ticket:", error);
            showStatusMessage(`Failed to delete ticket: ${error.message}`, true);
        }
    }

    async function uploadAttachment() {
        if (!currentTicketId) return;
        
        const fileInput = document.getElementById("fileAttachment");
        const file = fileInput.files[0];
        
        if (!file) {
            showStatusMessage("Please select a file to upload.", true);
            return;
        }

        const formData = new FormData();
        formData.append("file", file);

        try {
            // Show uploading indicator
            const uploadBtn = document.getElementById("uploadAttachmentBtn");
            const originalText = uploadBtn.textContent;
            uploadBtn.textContent = "Uploading...";
            uploadBtn.disabled = true;
            
            const response = await fetch(`http://127.0.0.1:5000/tickets/${currentTicketId}/attachments`, {
                method: "POST",
                body: formData
            });

            uploadBtn.textContent = originalText;
            uploadBtn.disabled = false;

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            showStatusMessage("File uploaded successfully!");
            
            // Refresh ticket details to show the new attachment
            showTicketDetails(currentTicketId);
        } catch (error) {
            console.error("Error uploading attachment:", error);
            showStatusMessage(`Failed to upload file: ${error.message}`, true);
            
            const uploadBtn = document.getElementById("uploadAttachmentBtn");
            uploadBtn.textContent = "Upload Attachment";
            uploadBtn.disabled = false;
        }
    }

    async function deleteAttachment(ticketId, attachmentIndex) {
        if (!confirm("Are you sure you want to delete this attachment?")) {
            return;
        }

        try {
            const response = await fetch(`http://127.0.0.1:5000/tickets/${ticketId}/attachments/${attachmentIndex}`, {
                method: "DELETE"
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            showStatusMessage("Attachment deleted successfully!");
            
            // Refresh ticket details to update the attachments list
            showTicketDetails(ticketId);
        } catch (error) {
            console.error("Error deleting attachment:", error);
            showStatusMessage(`Failed to delete attachment: ${error.message}`, true);
        }
    }

    function backToTickets() {
        document.getElementById("ticketDetails").style.display = "none";
        document.getElementById("ticketList").style.display = "block";
        currentTicketId = null;
        currentTicket = null;
    }

    // Add event listener for the refresh button
    document.getElementById("refreshTicketsBtn").addEventListener("click", fetchTickets);

    // Load tickets when the page is ready
    document.addEventListener("DOMContentLoaded", fetchTickets);
</script>

</body>
</html>
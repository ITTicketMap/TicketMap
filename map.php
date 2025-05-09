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
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
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

        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            margin: 0;
            padding: 0;
        }

        .close-button:hover {
            color: #333;
        }

        .ticket-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ticket-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .ticket-item:last-child {
            border-bottom: none;
        }

        .ticket-title {
            font-weight: bold;
            margin: 0;
        }

        .ticket-meta {
            display: flex;
            gap: 15px;
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .ticket-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-new {
            background-color: #bbdefb;
            color: #0d47a1;
        }

        .status-in-progress {
            background-color: #fff9c4;
            color: #827717;
        }

        .status-resolved {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .status-closed {
            background-color: #d7ccc8;
            color: #4e342e;
        }

        .view-ticket-link {
            margin-top: 8px;
            display: inline-block;
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

            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div id="root"></div>
<script type="text/babel">
    // Modal component for displaying tickets
    function TicketModal({ isOpen, onClose, user, tickets, loading }) {
        if (!isOpen) return null;

        const getStatusClass = (status) => {
            switch(status.toLowerCase()) {
                case 'new': return 'status-new';
                case 'in progress': return 'status-in-progress';
                case 'resolved': return 'status-resolved';
                case 'closed': return 'status-closed';
                default: return '';
            }
        };

        return (
            <div className="modal-backdrop" onClick={onClose}>
                <div className="modal-content" onClick={e => e.stopPropagation()}>
                    <div className="modal-header">
                        <h2>{user.label}'s Tickets</h2>
                        <button className="close-button" onClick={onClose}>&times;</button>
                    </div>
                    
                    {loading ? (
                        <div className="loading">Loading tickets...</div>
                    ) : tickets.length > 0 ? (
                        <ul className="ticket-list">
                            {tickets.map(ticket => (
                                <li key={ticket.id} className="ticket-item">
                                    <h3 className="ticket-title">{ticket.subject}</h3>
                                    <div className="ticket-meta">
                                        <span>From: {ticket.sender}</span>
                                        <span className={`ticket-status ${getStatusClass(ticket.status)}`}>
                                            {ticket.status}
                                        </span>
                                    </div>
                                    <a href={`check-tickets.php?id=${ticket.id}`} className="view-ticket-link">
                                        View Ticket
                                    </a>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p>No tickets found for {user.label}.</p>
                    )}
                </div>
            </div>
        );
    }

    function HoverButton({ label, position, email }) {
        const [clicked, setClicked] = React.useState(false);
        const [hovered, setHovered] = React.useState(false);
        const [emailCount, setEmailCount] = React.useState(null);
        const [loading, setLoading] = React.useState(false);
        const [modalOpen, setModalOpen] = React.useState(false);
        const [tickets, setTickets] = React.useState([]);
        const [loadingTickets, setLoadingTickets] = React.useState(false);

        // Function to fetch tickets for the user
        const fetchTickets = async () => {
            if (email) {
                try {
                    setLoadingTickets(true);
                    const response = await fetch('http://127.0.0.1:5000/tickets');
                    if (response.ok) {
                        const allTickets = await response.json();
                        
                        // Filter tickets associated with this user's email
                        let userTickets = [];
                        
                        // For John, we specifically look for tickets with itmaphelpdesk@gmail.com
                        if (email.toLowerCase() === 'itmaphelpdesk@gmail.com') {
                            userTickets = allTickets.filter(ticket => {
                                // Check if ticket's email matches John's email
                                return ticket.email && ticket.email.toLowerCase() === email.toLowerCase();
                            });
                        } else {
                            // For other users, filter with the standard logic
                            userTickets = allTickets.filter(ticket => {
                                if (ticket.email && ticket.email.toLowerCase() === email.toLowerCase()) {
                                    return true;
                                }
                                
                                return false;
                            });
                        }
                        
                        setTickets(userTickets);
                        setEmailCount(userTickets.length);
                    } else {
                        console.error('Failed to fetch tickets');
                    }
                } catch (error) {
                    console.error('Error fetching tickets:', error);
                } finally {
                    setLoadingTickets(false);
                }
            } else {
                setTickets([]);
                setEmailCount(0);
            }
        };

        const handleClick = () => {
            const newClickedState = !clicked;
            setClicked(newClickedState);
            
            if (newClickedState && email) {
                fetchTickets();
                setModalOpen(true);
            } else {
                setModalOpen(false);
            }
        };

        const closeModal = () => {
            setModalOpen(false);
            setClicked(false);
        };

        return (
            <>
                <div
                    onClick={handleClick}
                    onMouseEnter={() => setHovered(true)}
                    onMouseLeave={() => setHovered(false)}
                    style={{
                        position: 'absolute',
                        top: position.top,
                        left: position.left,
                        width: '50px',
                        height: '50px',
                        backgroundColor: clicked ? 'green' : 'blue',
                        color: 'white',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        cursor: 'pointer',
                        borderRadius: '8px',
                        fontSize: '20px',
                        fontWeight: 'bold'
                    }}
                >
                    {clicked && emailCount !== null ? emailCount : label.charAt(0)}
                    {clicked && loading && <span>...</span>}

                    {(hovered || clicked) && (
                        <div
                            style={{
                                position: 'absolute',
                                top: '-35px',
                                left: '50%',
                                transform: 'translateX(-50%)',
                                backgroundColor: '#333',
                                color: 'white',
                                padding: '6px 10px',
                                borderRadius: '6px',
                                fontSize: '13px',
                                whiteSpace: 'nowrap',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
                                zIndex: 10
                            }}
                        >
                            {label} {email ? `<${email}>` : ''}
                            {clicked && emailCount !== null ? ` - ${emailCount} tickets` : ''}
                        </div>
                    )}
                </div>

                {/* Render modal for tickets */}
                <TicketModal 
                    isOpen={modalOpen}
                    onClose={closeModal}
                    user={{ label, email }}
                    tickets={tickets}
                    loading={loadingTickets}
                />
            </>
        );
    }

    function Buttons() {
        const buttons = [
            {label: 'John', position: {top: '448px', left: '513px'}, email: 'itmaphelpdesk@gmail.com'},
            {label: 'Amanda', position: {top: '558px', left: '513px'}, email: 'helpdesktest53@gmail.com'},
            {label: 'Bill', position: {top: '788px', left: '513px'}, email: 'crabtree011@gmail.com'},
            {label: 'Sue', position: {top: '898px', left: '513px'}, email: 'sue@example.com'},
            {label: 'Jake', position: {top: '448px', left: '1191px'}, email: 'jake@example.com'},
            {label: 'Jen', position: {top: '558px', left: '1191px'}, email: 'jen@example.com'}
        ];
      
        return (
            <>
                {buttons.map((btn, index) => (
                    <HoverButton 
                        key={index} 
                        label={btn.label} 
                        position={btn.position} 
                        email={btn.email}
                    />
                ))}
            </>
        );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<Buttons />);
</script>
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

    <!-- Display Image of map -->
    <center>
    <div class="container">
        <h2>Location Map</h2>
        <p>Click on a person to view their assigned tickets</p>
        <img src="map-pic.png" alt="Map" style="max-width: 100%; height: auto;">
       </div>
    </center>
</body>
</html>
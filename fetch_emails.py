from flask import Flask, jsonify, request, send_from_directory
import imaplib
import email
from email.header import decode_header
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import smtplib
from flask_cors import CORS
import re
import os
import uuid
import json
from werkzeug.utils import secure_filename
from datetime import datetime, timedelta
import time

app = Flask(__name__)
CORS(app)

EMAIL_USER = "ithelpdeskmap@zohomail.com"
EMAIL_PASS = "Itmap687!"
IMAP_SERVER = "imap.zoho.com"
IMAP_PORT = 993
SMTP_SERVER = "smtp.zoho.com"
SMTP_PORT = 587

# Data storage file paths
DATA_FOLDER = 'data'
RESPONSES_FILE = os.path.join(DATA_FOLDER, 'responses.json')
STATUS_FILE = os.path.join(DATA_FOLDER, 'status.json')
CATEGORY_FILE = os.path.join(DATA_FOLDER, 'category.json')
ASSIGNED_FILE = os.path.join(DATA_FOLDER, 'assigned.json')
DELETED_FILE = os.path.join(DATA_FOLDER, 'deleted.json')
ATTACHMENTS_FILE = os.path.join(DATA_FOLDER, 'attachments.json')
TICKET_MAP_FILE = os.path.join(DATA_FOLDER, 'ticket_map.json')
MESSAGES_FILE = os.path.join(DATA_FOLDER, 'messages.json')  # New file for storing message history

# Create data directory if it doesn't exist
if not os.path.exists(DATA_FOLDER):
    os.makedirs(DATA_FOLDER)

# Storage for ticket data
ticket_responses = {}  # admin responses stored by ticket ID
ticket_status = {}  # ticket status by subject
ticket_category = {}  # ticket category by subject
ticket_assigned = {}  # ticket assigned to by subject
deleted_tickets = set()  # set of deleted ticket IDs
ticket_attachments = {}  # {ticket_id: [{filename: str, path: str, url: str}]}
ticket_map = {}  # Maps email subjects and references to ticket IDs
ticket_messages = {}  # Store messages by ticket ID

# Load data from files if they exist
def load_data():
    global ticket_responses, ticket_status, ticket_category, ticket_assigned, deleted_tickets, ticket_attachments, ticket_map, ticket_messages
    
    if os.path.exists(RESPONSES_FILE):
        with open(RESPONSES_FILE, 'r') as f:
            ticket_responses = json.load(f)
    
    if os.path.exists(STATUS_FILE):
        with open(STATUS_FILE, 'r') as f:
            ticket_status = json.load(f)
    
    if os.path.exists(CATEGORY_FILE):
        with open(CATEGORY_FILE, 'r') as f:
            ticket_category = json.load(f)
    
    if os.path.exists(ASSIGNED_FILE):
        with open(ASSIGNED_FILE, 'r') as f:
            ticket_assigned = json.load(f)
    
    if os.path.exists(DELETED_FILE):
        with open(DELETED_FILE, 'r') as f:
            deleted_tickets = set(json.load(f))
    
    if os.path.exists(ATTACHMENTS_FILE):
        with open(ATTACHMENTS_FILE, 'r') as f:
            ticket_attachments = json.load(f)
            
    if os.path.exists(TICKET_MAP_FILE):
        with open(TICKET_MAP_FILE, 'r') as f:
            ticket_map = json.load(f)
            
    if os.path.exists(MESSAGES_FILE):
        with open(MESSAGES_FILE, 'r') as f:
            ticket_messages = json.load(f)

# Save data to files
def save_data():
    with open(RESPONSES_FILE, 'w') as f:
        json.dump(ticket_responses, f)
    
    with open(STATUS_FILE, 'w') as f:
        json.dump(ticket_status, f)
    
    with open(CATEGORY_FILE, 'w') as f:
        json.dump(ticket_category, f)
    
    with open(ASSIGNED_FILE, 'w') as f:
        json.dump(ticket_assigned, f)
    
    with open(DELETED_FILE, 'w') as f:
        json.dump(list(deleted_tickets), f)
    
    with open(ATTACHMENTS_FILE, 'w') as f:
        json.dump(ticket_attachments, f)
        
    with open(TICKET_MAP_FILE, 'w') as f:
        json.dump(ticket_map, f)
        
    with open(MESSAGES_FILE, 'w') as f:
        json.dump(ticket_messages, f)

# Load data at startup
load_data()

# File upload configuration
UPLOAD_FOLDER = 'uploads'
ALLOWED_EXTENSIONS = {'txt', 'pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'zip'}

if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def clean_subject(subject):
    # Remove Re:, Fwd:, and [Ticket #xyz] patterns
    subject = re.sub(r"^(Re:|Fwd:)\s*", "", subject.strip(), flags=re.IGNORECASE)
    subject = re.sub(r"\[Ticket #[^\]]*\]", "", subject.strip())
    return subject.strip()

def extract_ticket_id_from_subject(subject):
    # Try to extract ticket ID from subject like "Re: Original Subject [Ticket #123]"
    match = re.search(r'\[Ticket #([^\]]+)\]', subject)
    if match:
        return match.group(1)
    return None

def extract_message_id(msg):
    """Extract the Message-ID from an email"""
    if msg.get("Message-ID"):
        return msg.get("Message-ID")
    return None

def extract_references(msg):
    """Extract references from an email (used for threading)"""
    references = []
    
    # Check In-Reply-To header
    if msg.get("In-Reply-To"):
        references.append(msg.get("In-Reply-To"))
    
    # Check References header
    if msg.get("References"):
        refs = msg.get("References").split()
        references.extend(refs)
    
    return references

def send_email(to_email, subject, message_body, ticket_id):
    """Send an email response to the ticket submitter"""
    try:
        # Create message
        msg = MIMEMultipart()
        msg['From'] = EMAIL_USER
        msg['To'] = to_email
        msg['Subject'] = f"Re: {subject} [Ticket #{ticket_id}]"
        
        # Add message body
        msg.attach(MIMEText(message_body, 'plain'))
        
        # Connect to SMTP server and send
        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(EMAIL_USER, EMAIL_PASS)
        text = msg.as_string()
        server.sendmail(EMAIL_USER, to_email, text)
        server.quit()
        
        # Add this response to the message history
        if ticket_id not in ticket_messages:
            ticket_messages[ticket_id] = []
            
        # Add the response to message history
        timestamp = datetime.now().timestamp()
        admin_name = ticket_assigned.get(subject, "Support Team")
        if admin_name == "Unassigned":
            admin_name = "Support Team"
            
        ticket_messages[ticket_id].append({
            "from": f"Admin ({admin_name})",
            "body": message_body,
            "type": "admin",
            "timestamp": timestamp
        })
        
        # Save the updated messages
        save_data()
        
        return True
    except Exception as e:
        print(f"Error sending email: {str(e)}")
        return False

def parse_email_address(from_header):
    """Extract just the email address from the From header"""
    # Match pattern like: "Name <email@example.com>" or just "email@example.com"
    match = re.search(r'<([^>]+)>|([^\s<]+@[^\s>]+)', from_header)
    if match:
        return match.group(1) if match.group(1) else match.group(2)
    return from_header  # Return as is if pattern doesn't match

def fetch_emails():
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER, IMAP_PORT)
        mail.login(EMAIL_USER, EMAIL_PASS)
        mail.select("inbox")
        status, messages = mail.search(None, "ALL")
        email_ids = messages[0].split()

        tickets = {}  # Store tickets by ID
        message_id_to_ticket = {}  # Map message IDs to ticket IDs
        
        # First pass: Extract Message-IDs and ticket information
        for email_id in email_ids:
            email_id_str = email_id.decode()
            
            # Skip deleted tickets
            if email_id_str in deleted_tickets:
                continue
                
            status, msg_data = mail.fetch(email_id, "(RFC822)")

            for response_part in msg_data:
                if isinstance(response_part, tuple):
                    msg = email.message_from_bytes(response_part[1])
                    subject, encoding = decode_header(msg["Subject"])[0]
                    if isinstance(subject, bytes):
                        subject = subject.decode(encoding if encoding else "utf-8")

                    # Extract message IDs and references for threading
                    message_id = extract_message_id(msg)
                    references = extract_references(msg)
                    
                    # Check if this is a reply to an existing ticket
                    existing_ticket_id = None
                    
                    # First, try to extract ticket ID from the subject
                    ticket_id_from_subject = extract_ticket_id_from_subject(subject)
                    if ticket_id_from_subject and ticket_id_from_subject in tickets:
                        existing_ticket_id = ticket_id_from_subject
                    
                    # If no ticket ID in subject, try to find it by references
                    if not existing_ticket_id:
                        for ref in references:
                            if ref in message_id_to_ticket:
                                existing_ticket_id = message_id_to_ticket[ref]
                                break
                    
                    # If no ticket ID from references, try to find by cleaned subject
                    clean_subj = clean_subject(subject)
                    if not existing_ticket_id and clean_subj in ticket_map:
                        existing_ticket_id = ticket_map[clean_subj]
                    
                    # If still no match, this is a new ticket
                    if not existing_ticket_id:
                        ticket_id = email_id_str
                        # Store mapping of subject to ticket ID
                        ticket_map[clean_subj] = ticket_id
                    else:
                        ticket_id = existing_ticket_id
                    
                    # Map this message ID to the ticket ID for future reference
                    if message_id:
                        message_id_to_ticket[message_id] = ticket_id
                    
                    # Parse sender and body
                    sender = msg.get("From")
                    sender_email = parse_email_address(sender)
                    body = ""

                    if msg.is_multipart():
                        for part in msg.walk():
                            content_type = part.get_content_type()
                            content_disposition = str(part.get("Content-Disposition"))

                            if content_type == "text/plain" and "attachment" not in content_disposition:
                                body = part.get_payload(decode=True).decode("utf-8", errors="ignore")
                                break
                    else:
                        body = msg.get_payload(decode=True).decode("utf-8", errors="ignore")

                    # Process attachments
                    email_attachments = []
                    if msg.is_multipart():
                        for part in msg.walk():
                            if part.get_content_maintype() == 'multipart':
                                continue
                            if part.get('Content-Disposition') is None:
                                continue
                            
                            filename = part.get_filename()
                            if filename:
                                # Create unique ticket folder for attachments
                                if ticket_id not in ticket_attachments:
                                    ticket_attachments[ticket_id] = []
                                
                                # Only save if we haven't already processed this attachment
                                attachment_exists = False
                                for att in ticket_attachments[ticket_id]:
                                    if att['filename'] == filename:
                                        attachment_exists = True
                                        break
                                
                                if not attachment_exists:
                                    # Create safe filename and save path
                                    safe_filename = secure_filename(filename)
                                    unique_filename = f"{uuid.uuid4()}_{safe_filename}"
                                    filepath = os.path.join(app.config['UPLOAD_FOLDER'], unique_filename)
                                    
                                    # Save the file
                                    with open(filepath, 'wb') as f:
                                        f.write(part.get_payload(decode=True))
                                    
                                    # Add to attachments list with URL
                                    ticket_attachments[ticket_id].append({
                                        'filename': filename,
                                        'path': filepath,
                                        'url': f"/uploads/{unique_filename}"
                                    })
                    
                    # Initialize ticket if it doesn't exist
                    if ticket_id not in tickets:
                        status_value = ticket_status.get(clean_subj, "New")
                        category_value = ticket_category.get(clean_subj, "General")
                        assigned_to = ticket_assigned.get(clean_subj, "Unassigned")
                        
                        # Create initial ticket with empty messages array
                        tickets[ticket_id] = {
                            "id": ticket_id,
                            "subject": clean_subj,
                            "sender": sender,
                            "email": sender_email,
                            "body": body,
                            "status": status_value,
                            "category": category_value,
                            "assigned_to": assigned_to,
                            "messages": [],
                            "admin_response": ticket_responses.get(ticket_id, ""),
                            "attachments": ticket_attachments.get(ticket_id, [])
                        }
                    
                    # Add email message to ticket
                    message_type = "admin" if sender_email == EMAIL_USER else "user"
                    timestamp = datetime.now().timestamp() - (3600 * email_ids.index(email_id))  # Rough timestamp based on order
                    email_message = {
                        "from": sender,
                        "body": body,
                        "type": message_type,
                        "timestamp": timestamp
                    }
                    
                    # Check if this message is already in the list to avoid duplicates
                    message_exists = False
                    
                    # First check in existing ticket messages
                    for msg in tickets[ticket_id]["messages"]:
                        if msg["body"] == body and msg["from"] == sender:
                            message_exists = True
                            break
                    
                    # Also check in our persistent message store
                    if ticket_id in ticket_messages:
                        for msg in ticket_messages[ticket_id]:
                            if msg["body"] == body and msg["from"] == sender:
                                message_exists = True
                                break
                                
                    if not message_exists:
                        tickets[ticket_id]["messages"].append(email_message)
                        
                        # Also add to our persistent message store
                        if ticket_id not in ticket_messages:
                            ticket_messages[ticket_id] = []
                        ticket_messages[ticket_id].append(email_message)
        
        # Process all stored messages in our persistent message store
        for ticket_id, ticket in tickets.items():
            # Get messages from persistent storage
            stored_messages = ticket_messages.get(ticket_id, [])
            
            # Create a set of message signatures for quick comparison
            existing_signatures = set()
            for msg in ticket["messages"]:
                signature = f"{msg.get('from', '')}-{msg.get('body', '')}"
                existing_signatures.add(signature)
            
            # Add stored messages that aren't already in the ticket
            for msg in stored_messages:
                signature = f"{msg.get('from', '')}-{msg.get('body', '')}"
                if signature not in existing_signatures:
                    ticket["messages"].append(msg)
                    existing_signatures.add(signature)
            
            # Add historical admin responses if not already in messages
            response_text = ticket_responses.get(ticket_id, "")
            if response_text:
                response_exists = False
                for msg in ticket["messages"]:
                    if msg["type"] == "admin" and msg["body"] == response_text:
                        response_exists = True
                        break
                
                if not response_exists:
                    # Add the response with the admin tag
                    timestamp = datetime.now().timestamp()  # Current time as timestamp
                    admin_name = ticket['assigned_to'] if ticket['assigned_to'] != 'Unassigned' else 'Support Team'
                    admin_msg = {
                        "from": f"Admin ({admin_name})",
                        "body": response_text,
                        "type": "admin",
                        "timestamp": timestamp
                    }
                    ticket["messages"].append(admin_msg)
                    
                    # Also add to our persistent store
                    if ticket_id not in ticket_messages:
                        ticket_messages[ticket_id] = []
                    ticket_messages[ticket_id].append(admin_msg)
            
            # Sort messages chronologically
            ticket["messages"].sort(key=lambda x: x.get("timestamp", 0))

        # Save the updated mappings
        save_data()
        
        # Return tickets as a list
        return list(tickets.values())
    except Exception as e:
        print(f"Error fetching emails: {str(e)}")
        return []

@app.route("/tickets", methods=["GET"])
def get_tickets():
    tickets = fetch_emails()
    return jsonify(tickets)

# NEW ENDPOINT: Create a ticket from web form
@app.route("/create-ticket", methods=["POST"])
def create_ticket():
    try:
        # Get form data
        title = request.form.get("ticketTitle")
        description = request.form.get("ticketDescription")
        priority = request.form.get("ticketPriority", "medium")
        user_email = request.form.get("userEmail")
        user_name = request.form.get("userName", "Website User")
        
        # Validate required fields
        if not title or not description or not user_email:
            return jsonify({
                "success": False,
                "message": "Title, description and email are required"
            }), 400
        
        # Generate a ticket ID
        ticket_id = f"web-{int(time.time())}"
        
        # Create email to send to the helpdesk (this effectively creates the ticket)
        msg = MIMEMultipart()
        msg['From'] = f"{user_name} <{user_email}>"
        msg['To'] = EMAIL_USER
        msg['Subject'] = title
        
        # Create message body with priority information
        body = f"""
New ticket submitted from website:

Title: {title}
Priority: {priority}
Submitted by: {user_name} ({user_email})

Description:
{description}
        """
        
        msg.attach(MIMEText(body, 'plain'))
        
        # Send the email to create the ticket in the system
        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(EMAIL_USER, EMAIL_PASS)
        text = msg.as_string()
        server.sendmail(user_email, EMAIL_USER, text)
        server.quit()
        
        # Store the ticket in our local system as well
        clean_title = clean_subject(title)
        ticket_map[clean_title] = ticket_id
        ticket_status[clean_title] = "New"
        ticket_category[clean_title] = "Website"
        
        # Add initial message to ticket_messages
        timestamp = datetime.now().timestamp()
        ticket_messages[ticket_id] = [{
            "from": f"{user_name} <{user_email}>",
            "body": body,
            "type": "user",
            "timestamp": timestamp
        }]
        
        # Save changes
        save_data()
        
        # Send confirmation email to user
        confirmation_message = f"""
Dear {user_name},

Thank you for submitting a support ticket. Your request has been received and assigned ticket number: #{ticket_id}

Ticket Summary:
Title: {title}
Priority: {priority}
Status: New

We'll be in touch shortly regarding your request. Please keep this email for your records.

To add more information to this ticket, simply reply to this email.

Best regards,
IT Support Team
        """
        
        # Send confirmation
        conf_msg = MIMEMultipart()
        conf_msg['From'] = EMAIL_USER
        conf_msg['To'] = user_email
        conf_msg['Subject'] = f"Support Ticket Confirmation [Ticket #{ticket_id}]"
        conf_msg.attach(MIMEText(confirmation_message, 'plain'))
        
        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(EMAIL_USER, EMAIL_PASS)
        server.sendmail(EMAIL_USER, user_email, conf_msg.as_string())
        server.quit()
        
        # Add confirmation message to ticket history
        timestamp = datetime.now().timestamp()
        ticket_messages[ticket_id].append({
            "from": f"Admin (Support Team)",
            "body": confirmation_message,
            "type": "admin",
            "timestamp": timestamp
        })
        save_data()
        
        return jsonify({
            "success": True,
            "message": "Ticket created successfully",
            "ticket_id": ticket_id
        })
    except Exception as e:
        print(f"Error creating ticket: {str(e)}")
        return jsonify({
            "success": False,
            "message": f"Error creating ticket: {str(e)}"
        }), 500

@app.route("/tickets/<ticket_id>", methods=["GET"])
def get_ticket(ticket_id):
    tickets = fetch_emails()
    
    for ticket in tickets:
        if ticket["id"] == ticket_id:
            return jsonify(ticket)
    
    return jsonify({"error": "Ticket not found"}), 404

@app.route("/tickets/<ticket_id>", methods=["POST"])
def respond_to_ticket(ticket_id):
    data = request.json
    response_text = data.get("response", "")
    subject = data.get("subject", "")
    
    if not response_text:
        return jsonify({"error": "Response text is required"}), 400

    # Store the response for this ticket
    ticket_responses[ticket_id] = response_text
    
    # Save changes to disk
    save_data()
    
    # Get ticket details to find the user's email
    tickets = fetch_emails()
    
    for ticket in tickets:
        if ticket["id"] == ticket_id:
            # Send email response to user
            email_sent = send_email(
                ticket["email"], 
                subject or ticket["subject"], 
                response_text, 
                ticket_id
            )
            
            if email_sent:
                return jsonify({"message": "Response sent and saved successfully"})
            else:
                return jsonify({"message": "Response saved but email could not be sent"}), 500
    
    return jsonify({"message": "Response saved successfully"})

@app.route("/tickets/<ticket_id>/reply", methods=["POST"])
def add_admin_reply(ticket_id):
    """Add an admin reply to a ticket without sending an email"""
    data = request.json
    response_text = data.get("response", "")
    admin_name = data.get("admin_name", "Support Team")
    
    if not response_text:
        return jsonify({"error": "Response text is required"}), 400
    
    try:
        # Get current ticket
        tickets = fetch_emails()
        current_ticket = None
        
        for ticket in tickets:
            if ticket["id"] == ticket_id:
                current_ticket = ticket
                break
        
        if not current_ticket:
            return jsonify({"error": "Ticket not found"}), 404
        
        # Add reply to the messages array
        timestamp = datetime.now().timestamp()
        new_message = {
            "from": f"Admin ({admin_name})",
            "body": response_text,
            "type": "admin",
            "timestamp": timestamp
        }
        
        # Store the response for this ticket
        ticket_responses[ticket_id] = response_text
        
        # Add to persistent message store
        if ticket_id not in ticket_messages:
            ticket_messages[ticket_id] = []
        ticket_messages[ticket_id].append(new_message)
        
        # Save changes to disk
        save_data()
        
        return jsonify({
            "message": "Admin reply added successfully",
            "new_message": new_message
        })
    except Exception as e:
        print(f"Error adding admin reply: {str(e)}")
        return jsonify({
            "error": f"Failed to add reply: {str(e)}"
        }), 500

@app.route("/tickets/<ticket_id>", methods=["PATCH"])
def update_ticket(ticket_id):
    data = request.json
    tickets = fetch_emails()
    
    for ticket in tickets:
        if ticket["id"] == ticket_id:
            subject = ticket["subject"]
            
            if "category" in data:
                ticket_category[subject] = data["category"]
            
            if "status" in data:
                ticket_status[subject] = data["status"]
                
            if "assigned_to" in data:
                ticket_assigned[subject] = data["assigned_to"]
            
            # Save changes to disk
            save_data()
            
            return jsonify({"message": "Ticket updated successfully"})
    
    return jsonify({"error": "Ticket not found"}), 404

@app.route("/tickets/<ticket_id>", methods=["DELETE"])
def delete_ticket(ticket_id):
    # Add ticket ID to deleted set
    deleted_tickets.add(ticket_id)
    
    # Remove any attachments for this ticket
    if ticket_id in ticket_attachments:
        for attachment in ticket_attachments[ticket_id]:
            try:
                # Delete the file
                if os.path.exists(attachment['path']):
                    os.remove(attachment['path'])
            except Exception as e:
                print(f"Error deleting file {attachment['path']}: {str(e)}")
        
        # Remove from dictionary
        del ticket_attachments[ticket_id]
    
    # Remove from message store
    if ticket_id in ticket_messages:
        del ticket_messages[ticket_id]
    
    # Save changes to disk
    save_data()
    
    return jsonify({"message": "Ticket deleted successfully"})

@app.route("/tickets/<ticket_id>/attachments", methods=["POST"])
def add_attachment(ticket_id):
    if 'file' not in request.files:
        return jsonify({"error": "No file part"}), 400
        
    file = request.files['file']
    
    if file.filename == '':
        return jsonify({"error": "No selected file"}), 400
        
    if file and allowed_file(file.filename):
        filename = secure_filename(file.filename)
        unique_filename = f"{uuid.uuid4()}_{filename}"
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], unique_filename)
        file.save(filepath)
        
        # Add to attachments list
        if ticket_id not in ticket_attachments:
            ticket_attachments[ticket_id] = []
            
        ticket_attachments[ticket_id].append({
            'filename': filename,
            'path': filepath,
            'url': f"/uploads/{unique_filename}"
        })
        
        # Save changes to disk
        save_data()
        
        return jsonify({"message": "File uploaded successfully"})
    
    return jsonify({"error": "File type not allowed"}), 400

@app.route("/tickets/<ticket_id>/attachments/<int:attachment_index>", methods=["DELETE"])
def delete_attachment(ticket_id, attachment_index):
    if ticket_id not in ticket_attachments or attachment_index >= len(ticket_attachments[ticket_id]):
        return jsonify({"error": "Attachment not found"}), 404
        
    # Get attachment info
    attachment = ticket_attachments[ticket_id][attachment_index]
    
    # Delete the file
    try:
        if os.path.exists(attachment['path']):
            os.remove(attachment['path'])
    except Exception as e:
        print(f"Error deleting file {attachment['path']}: {str(e)}")
    
    # Remove from list
    ticket_attachments[ticket_id].pop(attachment_index)
    
    # Save changes to disk
    save_data()
    
    return jsonify({"message": "Attachment deleted successfully"})

@app.route("/uploads/<filename>")
def uploaded_file(filename):
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)

@app.route("/admin-stats", methods=["GET"])
def get_admin_stats():
    """
    Get statistics about admin ticket handling:
    - Number of tickets handled per admin in the last 24 hours, 7 days, and 30 days
    - Average response time for tickets
    """
    tickets = fetch_emails()
    now = datetime.now()
    
    # Stats containers
    daily_stats = {}   # Last 24 hours
    weekly_stats = {}  # Last 7 days
    monthly_stats = {} # Last 30 days
    
    # Response time tracking
    admin_response_times = {}
    
    for ticket in tickets:
        # Get the assigned admin for this ticket
        assigned_admin = ticket.get("assigned_to", "Unassigned")
        if assigned_admin == "Unassigned":
            assigned_admin = "Support Team"
        
        # Skip tickets with no messages
        if not ticket.get("messages"):
            continue
            
        # Track the first user message timestamp
        first_user_msg_time = None
        first_admin_response_time = None
        first_admin_responder = None
        has_admin_response = False
        
        # Analyze message thread
        messages = sorted(ticket.get("messages", []), key=lambda x: x.get("timestamp", 0))
        for msg in messages:
            # Skip if no timestamp in the message
            if "timestamp" not in msg:
                # For this implementation, we'll estimate a timestamp
                # In a real app, you would extract this from email headers
                # Here we'll estimate based on message order
                msg_idx = messages.index(msg)
                # Assume older messages are earlier (3 hours apart)
                msg["timestamp"] = (now - timedelta(hours=3 * (len(messages) - msg_idx))).timestamp()
            
            msg_time = datetime.fromtimestamp(msg["timestamp"]) if isinstance(msg["timestamp"], (int, float)) else now
            
            # If admin message, check if it's a response
            if msg.get("type") == "admin":
                has_admin_response = True
                admin_name = assigned_admin
                
                # Try to extract admin name from message if available
                if "Admin (" in msg.get("from", ""):
                    # Extract name from format "Admin (Name)"
                    match = re.search(r'Admin \(([^)]+)\)', msg.get("from", ""))
                    if match and match.group(1) != "Support Team":
                        admin_name = match.group(1)
                
                # Track admin handling ticket in time periods
                one_day_ago = now - timedelta(days=1)
                seven_days_ago = now - timedelta(days=7)
                thirty_days_ago = now - timedelta(days=30)
                
                # Count tickets by period - only count once per ticket per admin
                if msg_time >= one_day_ago and admin_name not in daily_stats:
                    daily_stats[admin_name] = daily_stats.get(admin_name, 0) + 1
                if msg_time >= seven_days_ago and admin_name not in weekly_stats:
                    weekly_stats[admin_name] = weekly_stats.get(admin_name, 0) + 1
                if msg_time >= thirty_days_ago and admin_name not in monthly_stats:
                    monthly_stats[admin_name] = monthly_stats.get(admin_name, 0) + 1
                
                # Track first admin response
                if first_user_msg_time and not first_admin_response_time:
                    first_admin_response_time = msg_time
                    first_admin_responder = admin_name
            
            # If user message, track the first one
            elif msg.get("type") != "admin" and not first_user_msg_time:
                first_user_msg_time = msg_time
        
        # If the ticket has been assigned but has no admin response yet,
        # still count it for the assigned admin in the stats
        if not has_admin_response and assigned_admin != "Support Team":
            one_day_ago = now - timedelta(days=1)
            seven_days_ago = now - timedelta(days=7)
            thirty_days_ago = now - timedelta(days=30)
            
            # Use ticket creation time (approximated by first message)
            if messages and "timestamp" in messages[0]:
                ticket_time = datetime.fromtimestamp(messages[0]["timestamp"])
                
                if ticket_time >= one_day_ago:
                    daily_stats[assigned_admin] = daily_stats.get(assigned_admin, 0) + 1
                if ticket_time >= seven_days_ago:
                    weekly_stats[assigned_admin] = weekly_stats.get(assigned_admin, 0) + 1
                if ticket_time >= thirty_days_ago:
                    monthly_stats[assigned_admin] = monthly_stats.get(assigned_admin, 0) + 1
        
        # Calculate response time if we have both timestamps
        if first_user_msg_time and first_admin_response_time and first_admin_responder:
            response_time = (first_admin_response_time - first_user_msg_time).total_seconds() / 3600  # hours
            
            if first_admin_responder not in admin_response_times:
                admin_response_times[first_admin_responder] = []
                
            admin_response_times[first_admin_responder].append(response_time)
    
    # Format stats for return
    def format_stats(stats_dict):
        return [{"admin": admin, "count": count} for admin, count in stats_dict.items()]
    
    # Calculate average response times
    avg_response_times = []
    for admin, times in admin_response_times.items():
        if times:
            avg_time = sum(times) / len(times)
            avg_response_times.append({
                "admin": admin,
                "avg_hours": round(avg_time, 2),
                "num_tickets": len(times)
            })
    
    return jsonify({
        "daily": format_stats(daily_stats),
        "weekly": format_stats(weekly_stats),
        "monthly": format_stats(monthly_stats),
        "response_times": avg_response_times
    })

# Serve static files
@app.route('/')
def index():
    return send_from_directory('.', 'index.html')

@app.route('/<path:path>')
def serve_static(path):
    return send_from_directory('.', path)

if __name__ == "__main__":
    app.run(debug=True)

# Add this new endpoint to fetch_emails.py
# Place at the end of the file before the 'if __name__ == "__main__":' line

@app.route("/email-count", methods=["GET"])
def get_email_count():
    email = request.args.get('email')
    
    if not email:
        return jsonify({"error": "Email parameter is required"}), 400
    
    try:
        # Count tickets where the specified email appears either:
        # 1. In assigned emails (when someone is assigned a ticket)
        # 2. In message sender emails (when someone replies to a ticket)
        
        tickets = fetch_emails()
        count = 0
        
        for ticket in tickets:
            # Check if this ticket is assigned to the email address
            assigned_email = ""
            if ticket.get("assigned_to") == "John" and email.lower() == "itmaphelpdesk@gmail.com":
                count += 1
                continue
                
            # Also check if the email appears in any message from field
            for message in ticket.get("messages", []):
                if email.lower() in message.get("from", "").lower():
                    count += 1
                    break  # Only count each ticket once
                    
        return jsonify({"count": count})
        
    except Exception as e:
        print(f"Error getting email count: {str(e)}")
        return jsonify({"error": str(e)}), 500
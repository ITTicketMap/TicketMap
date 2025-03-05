from flask import Flask, jsonify
import imaplib
import email
from email.header import decode_header
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Allows frontend to access API

EMAIL_USER = "itmaphelpdesk@zohomail.com"
EMAIL_PASS = "Itmap687!"
IMAP_SERVER = "imap.zoho.com"
IMAP_PORT = 993

def fetch_emails():
    try:
        mail = imaplib.IMAP4_SSL(IMAP_SERVER, IMAP_PORT)
        mail.login(EMAIL_USER, EMAIL_PASS)
        mail.select("inbox")

        status, messages = mail.search(None, "ALL")  # Fetch all emails
        email_ids = messages[0].split()
        
        tickets = []
        
        for email_id in email_ids:
            status, msg_data = mail.fetch(email_id, "(RFC822)")
            for response_part in msg_data:
                if isinstance(response_part, tuple):
                    msg = email.message_from_bytes(response_part[1])
                    
                    subject, encoding = decode_header(msg["Subject"])[0]
                    if isinstance(subject, bytes):
                        subject = subject.decode(encoding if encoding else "utf-8")
                    
                    sender = msg.get("From")
                    
                    # Extract email body (first text part)
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

                    tickets.append({
                        "id": email_id.decode(),  # Unique ID for the ticket
                        "subject": subject,
                        "sender": sender,
                        "body": body,
                        "status": "Open"
                    })
        
        mail.logout()
        return tickets
    except Exception as e:
        return str(e)

@app.route("/tickets", methods=["GET"])
def get_tickets():
    return jsonify(fetch_emails())

if __name__ == "__main__":
    app.run(debug=True)

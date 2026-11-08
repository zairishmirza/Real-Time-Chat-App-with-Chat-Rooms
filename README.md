# Real-Time Chat Application With Chat Rooms

A lightweight multi-user chatting application built using **HTML, CSS, jQuery, AJAX, PHP, and MySQL**. It supports one-to-one private messaging as well as group chat rooms with multiple participants.

## Features

- Username-based user identification
- One-to-one private messaging
- Multiple-user group chat rooms
- Private chat popup windows
- Clickable usernames for starting private conversations
- Prevention of duplicate popup windows
- Automatic message updates every two seconds
- Load More functionality for older messages
- Automatic chat scrolling
- Enter key support for sending messages
- Collapse and close private chat windows
- Automatic MySQL database and table creation

## Technologies Used

| Technology | Purpose |
|---|---|
| HTML | Frontend structure |
| CSS | User interface styling |
| jQuery | DOM manipulation and events |
| AJAX | Asynchronous requests |
| PHP | Backend functionality |
| MySQL | Database management |
| PDO | Database connectivity |

## Project Structure

```text
Chat-Room-Application/
│
├── index.html
└── api.php
```

## Database Structure

The application automatically creates the `chat_app` database with three tables:

1. **users** – Stores usernames and user IDs.
2. **rooms** – Stores chat room names and room IDs.
3. **messages** – Stores private and group messages, sender information, and timestamps.

## Installation and Setup

1. Install XAMPP or WAMP.
2. Start Apache and MySQL.
3. Create a project folder inside your server's web directory.
4. Place `index.html` and `api.php` inside the folder.
5. Open your browser and visit:

```text
http://localhost/Chat-Room-Application/
```

6. Enter a username.
7. Select One to One Chat or Chat Room.
8. Enter a friend's username or a room name.
9. Open another browser session with a different username to test messaging.

**Note:** The database and tables are created automatically when the PHP API is accessed.

## How It Works

- `createPanel()` generates reusable chat interfaces.
- `openPopup()` creates individual private chat windows.
- `$.post()` sends messages to the PHP backend.
- `$.getJSON()` retrieves messages in JSON format.
- `poll()` fetches newly received messages.
- `loadOld()` retrieves previous messages.
- `setInterval()` checks for updates every two seconds.
- `$.each()` iterates through all active private chat popups.

## Learning Objectives

This project demonstrates practical implementation of:

- jQuery event handling
- AJAX GET and POST requests
- JSON encoding and decoding
- PHP and MySQL integration
- PDO prepared statements
- Dynamic HTML generation
- JavaScript objects and functions
- Database relationships
- Message polling and pagination

## Security Notice

This project is intended for educational and local development purposes. It currently uses username-based identification without password authentication or session-based authorization.

Additional security measures, including authentication, authorization, input validation, output escaping, CSRF protection, and rate limiting, are necessary before public deployment.

## Important Technical Note

This application uses AJAX polling at two-second intervals rather than WebSockets. Therefore, it provides near-real-time message updates rather than true server-push communication.

## Author

**Zairish Mirza**

## License

This project is available for educational and learning purposes.
<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Client</title>
    <style>
        #notification-container {
            position: relative;
            display: inline-block;
        }
        #bell-icon {
            font-size: 24px;
            cursor: pointer;
        }
        #notification-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
        }
        #notification-content {
            display: none;
            position: absolute;
            top: 30px;
            right: 0;
            width: 300px;
            border: 1px solid #ccc;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
        }
        #notification-content p {
            padding: 10px;
            margin: 0;
            border-bottom: 1px solid #ccc;
        }
        #notification-content p:last-child {
            border-bottom: none;
        }
    </style>
    <script type="text/javascript">
        var conn;
        var notificationCount = 0;
        var notifications = [];

        function setupWebSocket() {
            conn = new WebSocket('ws://localhost:8016');
            var log = document.getElementById("log");

            conn.onopen = function(e) {
                console.log("Connection established!");
            };

            conn.onmessage = function(e) {
                try {
                    var data = JSON.parse(e.data);
                    console.log("Message received: ", data);
                    console.log(data.message);

                    if (data.status === "notification") {
                        notificationCount++;
                        notifications.push(data.message);
                        updateNotificationCount();
                        updateNotificationContent();
                    }
                } catch (err) {
                    console.error("Error parsing JSON: ", err);
                    console.log("Received non-JSON message: " + e.data);
                }
            };

            conn.onclose = function(e) {
                console.log("Connection closed!");
            };

            conn.onerror = function(e) {
                console.log("Connection error!");
            };
        }

        function updateNotificationCount() {
            var countElement = document.getElementById("notification-count");
            countElement.textContent = notificationCount;
        }

        function updateNotificationContent() {
            var contentElement = document.getElementById("notification-content");
            contentElement.innerHTML = '';
            notifications.forEach(function(notification) {
                var p = document.createElement("p");
                p.textContent = notification;
                contentElement.appendChild(p);
            });
        }

        function sendNotification() {
            if (conn && conn.readyState === WebSocket.OPEN) {
                var msg = document.getElementById("notificationInput").value;
                if (msg.trim() !== "") {
                    conn.send(msg);
                    console.log("Notification sent: " + msg);
                    document.getElementById("notificationInput").value = "";  // Clear the input field after sending
                } else {
                    console.log("Notification message cannot be empty.");
                }
            } else {
                console.log("WebSocket is not open.");
            }
        }

        function toggleNotificationContent() {
            var contentElement = document.getElementById("notification-content");
            if (contentElement.style.display === "none" || contentElement.style.display === "") {
                contentElement.style.display = "block";
            } else {
                contentElement.style.display = "none";
            }
        }

        window.onload = function () {
            setupWebSocket();

            document.getElementById("notifyBtn").addEventListener("click", function() {
                sendNotification();
            });

            document.getElementById("bell-icon").addEventListener("click", function() {
                toggleNotificationContent();
            });
        };
    </script>
</head>
<body style="text-align: center;margin-top: 20px">
    <div id="notification-container">
        <span id="bell-icon">&#128276;</span>
        <span id="notification-count">0</span>
        <div id="notification-content"></div>
    </div>
    <input type="text" id="notificationInput" placeholder="Type a notification...">
    <button id="notifyBtn">Send Notification</button>
    <div id="log"></div>
</body>
</html>

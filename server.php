<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;

        // Database connection
        $this->db = new mysqli('localhost', 'root', '', 'auditors');
        
        if ($this->db->connect_error) {
            die('Connect Error (' . $this->db->connect_errno . ') ' . $this->db->connect_error);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";

        // Send a welcome message
        $welcomeMessage = json_encode([
            'status' => 'success',
            'message' => 'Welcome! Connection established.'
        ]);
        $conn->send($welcomeMessage);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $notification = json_encode([
                    'status' => 'notification',
                    'message' => $msg
                ]);
                $client->send($notification);
            }
        }

        $this->saveNotification($msg);

        $response = json_encode([
            'status' => 'success',
            'message' => 'Notification saved successfully.'
        ]);
        $from->send($response);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $errorResponse = json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        $conn->send($errorResponse);
        $conn->close();
    }

    private function saveNotification($message) {
        $stmt = $this->db->prepare("INSERT INTO notifications (message) VALUES (?)");
        $stmt->bind_param('s', $message);
        $stmt->execute();
        $stmt->close();
    }
}

$chat = new Chat();

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $chat
        )
    ),
    8016
);

echo "WebSocket server started at ws://localhost:8016\n";
$server->run();
?>

<?php
// Import necessary Ratchet classes
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require __DIR__ . '/vendor/autoload.php';

class ChatServer implements MessageComponentInterface {
    // Store active connections
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage; // Create a storage to hold connections
    }

    // Called when a new connection is opened
    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n"; // Log connection ID
    }

    // Called when a message is received
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1; // Get the number of connected clients excluding the sender
        echo sprintf('Connection %d sending message "%s" to %d other connection(s)' . "\n",
            $from->resourceId, $msg, $numRecv);

        // Broadcast the message to all connected clients
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // Send the message to everyone except the sender
                $client->send($msg);
            }
        }
    }

    // Called when a connection is closed
    public function onClose(ConnectionInterface $conn) {
        // Remove the connection from the list
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    // Called when an error occurs
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close(); // Close the connection on error
    }
}

// Run the WebSocket server
$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new ChatServer()
        )
    ),
    8080 // The port number the WebSocket server will listen on
);

$server->run();
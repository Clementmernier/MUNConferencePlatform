<?php
namespace MUNSimulator\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class MUNWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        
        // Initialize database connection
        require_once __DIR__ . '/../config/database.php';
        $database = new \Database();
        $this->db = $database->getConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['action'])) {
            return;
        }

        switch ($data['action']) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
            case 'unsubscribe':
                $this->handleUnsubscribe($from, $data);
                break;
            case 'update':
                $this->handleUpdate($from, $data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        // Remove all subscriptions for this connection
        foreach ($this->subscriptions as $channel => $subscribers) {
            $this->subscriptions[$channel] = array_filter(
                $subscribers,
                function($subscriber) use ($conn) {
                    return $subscriber !== $conn->resourceId;
                }
            );
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleSubscribe($conn, $data) {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];
        if (!isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = [];
        }

        if (!in_array($conn->resourceId, $this->subscriptions[$channel])) {
            $this->subscriptions[$channel][] = $conn->resourceId;
        }

        // Send initial data for the subscription
        switch ($channel) {
            case 'resolutions':
                $this->sendResolutionsUpdate($conn);
                break;
            case 'amendments':
                $this->sendAmendmentsUpdate($conn);
                break;
        }
    }

    protected function handleUnsubscribe($conn, $data) {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];
        if (isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = array_filter(
                $this->subscriptions[$channel],
                function($id) use ($conn) {
                    return $id !== $conn->resourceId;
                }
            );
        }
    }

    protected function handleUpdate($from, $data) {
        if (!isset($data['channel']) || !isset($data['data'])) {
            return;
        }

        $channel = $data['channel'];
        if (!isset($this->subscriptions[$channel])) {
            return;
        }

        // Broadcast to all subscribers except sender
        foreach ($this->clients as $client) {
            if ($from !== $client && in_array($client->resourceId, $this->subscriptions[$channel])) {
                $client->send(json_encode([
                    'channel' => $channel,
                    'data' => $data['data']
                ]));
            }
        }
    }

    protected function sendResolutionsUpdate($conn) {
        $stmt = $this->db->prepare("SELECT * FROM resolutions ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $resolutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conn->send(json_encode([
            'channel' => 'resolutions',
            'data' => $resolutions
        ]));
    }

    protected function sendAmendmentsUpdate($conn) {
        $stmt = $this->db->prepare("SELECT * FROM amendments ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $amendments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conn->send(json_encode([
            'channel' => 'amendments',
            'data' => $amendments
        ]));
    }
}
?>

<?php
/**
 * ====================================================================
 * WebSocket Server for Real-time Notifications
 * خادم WebSocket للإشعارات الفورية
 * ====================================================================
 * Start: php websocket_server.php
 * Port: 8080 (default)
 * ====================================================================
 */

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        echo "✅ WebSocket Server Initialized\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // Parse query string for user_id
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $query);
        
        if (isset($query['user_id'])) {
            $userId = intval($query['user_id']);
            $conn->userId = $userId;
            
            // Store connection by user ID
            if (!isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = [];
            }
            $this->userConnections[$userId][] = $conn;
            
            echo "✅ User {$userId} connected (Connection #{$conn->resourceId})\n";
            
            // Send welcome message
            $conn->send(json_encode([
                'type' => 'connected',
                'message' => 'Connected to notification server',
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            
            // Save connection to database
            $this->saveConnectionToDb($userId, $conn->resourceId);
        } else {
            echo "⚠️ Connection without user_id (#{$conn->resourceId})\n";
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }
        
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'ping':
                $from->send(json_encode([
                    'type' => 'pong',
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
                break;
                
            case 'mark_read':
                // Handle mark as read
                $notificationId = $data['notification_id'] ?? 0;
                if ($notificationId > 0 && isset($from->userId)) {
                    $this->markNotificationRead($from->userId, $notificationId);
                }
                break;
                
            case 'broadcast':
                // Admin can broadcast to all
                if (isset($data['admin_key']) && $data['admin_key'] === 'YOUR_SECRET_KEY') {
                    $this->broadcastToAll($data['message']);
                }
                break;
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($conn->userId)) {
            $userId = $conn->userId;
            
            // Remove connection from user connections
            if (isset($this->userConnections[$userId])) {
                $this->userConnections[$userId] = array_filter(
                    $this->userConnections[$userId],
                    function($c) use ($conn) {
                        return $c !== $conn;
                    }
                );
                
                if (empty($this->userConnections[$userId])) {
                    unset($this->userConnections[$userId]);
                }
            }
            
            echo "❌ User {$userId} disconnected (Connection #{$conn->resourceId})\n";
            
            // Remove from database
            $this->removeConnectionFromDb($conn->resourceId);
        } else {
            echo "❌ Connection #{$conn->resourceId} disconnected\n";
        }
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "⚠️ Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    /**
     * Send notification to specific user
     */
    public function sendToUser($userId, $notification) {
        if (!isset($this->userConnections[$userId])) {
            echo "⚠️ User {$userId} not connected\n";
            return false;
        }
        
        $message = json_encode([
            'type' => 'notification',
            'data' => $notification,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        foreach ($this->userConnections[$userId] as $conn) {
            $conn->send($message);
        }
        
        echo "📤 Notification sent to User {$userId}\n";
        return true;
    }
    
    /**
     * Broadcast to all connected users
     */
    public function broadcastToAll($message) {
        $payload = json_encode([
            'type' => 'broadcast',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        foreach ($this->clients as $client) {
            $client->send($payload);
        }
        
        echo "📣 Broadcast sent to all users\n";
    }
    
    /**
     * Save connection to database
     */
    private function saveConnectionToDb($userId, $connectionId) {
        try {
            $conn = new mysqli('localhost', 'root', '', 'ibdaa_platform');
            
            if ($conn->connect_error) {
                return;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO websocket_connections (user_id, connection_id, ip_address, user_agent, connected_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $connIdStr = (string)$connectionId;
            
            $stmt->bind_param('isss', $userId, $connIdStr, $ip, $userAgent);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            error_log("Failed to save connection: " . $e->getMessage());
        }
    }
    
    /**
     * Remove connection from database
     */
    private function removeConnectionFromDb($connectionId) {
        try {
            $conn = new mysqli('localhost', 'root', '', 'ibdaa_platform');
            
            if ($conn->connect_error) {
                return;
            }
            
            $stmt = $conn->prepare("
                UPDATE websocket_connections 
                SET is_active = 0 
                WHERE connection_id = ?
            ");
            
            $connIdStr = (string)$connectionId;
            $stmt->bind_param('s', $connIdStr);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            error_log("Failed to remove connection: " . $e->getMessage());
        }
    }
    
    /**
     * Mark notification as read in database
     */
    private function markNotificationRead($userId, $notificationId) {
        try {
            $conn = new mysqli('localhost', 'root', '', 'ibdaa_platform');
            
            if ($conn->connect_error) {
                return;
            }
            
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->bind_param('ii', $notificationId, $userId);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            echo "✅ Notification #{$notificationId} marked as read for User {$userId}\n";
        } catch (Exception $e) {
            error_log("Failed to mark notification read: " . $e->getMessage());
        }
    }
}

// ============================================================================
// Start Server
// ============================================================================

$port = 8080;

echo "🚀 Starting WebSocket Server...\n";
echo "📡 Listening on ws://localhost:{$port}\n";
echo "📝 Press Ctrl+C to stop\n";
echo str_repeat("=", 60) . "\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    $port
);

$server->run();

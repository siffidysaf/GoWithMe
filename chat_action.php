<?php
session_start();
header('Content-Type: application/json');
require_once "DBConnect.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'fetch_contacts':
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $query = "SELECT u.ID, u.Name, u.Instituition, u.Gender 
                  FROM user u 
                  WHERE u.ID != ?";
        
        $params = [$current_user_id];
        $types = "i";

        if (!empty($search)) {
            $query .= " AND (u.Name LIKE ? OR u.Instituition LIKE ?)";
            $search_param = "%" . $search . "%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }

        $query .= " ORDER BY u.Name ASC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $contacts = [];
        while ($row = $result->fetch_assoc()) {
            $contact_id = (int)$row['ID'];

            // Fetch latest message exchanged with this user
            $msg_stmt = $conn->prepare("SELECT Message_ID, Message, Sender_ID 
                                       FROM texts 
                                       WHERE (Sender_ID = ? AND Receiver_ID = ?) 
                                          OR (Sender_ID = ? AND Receiver_ID = ?) 
                                       ORDER BY Message_ID DESC LIMIT 1");
            $msg_stmt->bind_param("iiii", $current_user_id, $contact_id, $contact_id, $current_user_id);
            $msg_stmt->execute();
            $msg_res = $msg_stmt->get_result();

            $last_msg = "";
            $last_msg_is_mine = false;
            $last_msg_id = 0;
            if ($msg_row = $msg_res->fetch_assoc()) {
                $last_msg = $msg_row['Message'];
                $last_msg_is_mine = ((int)$msg_row['Sender_ID'] === $current_user_id);
                $last_msg_id = (int)$msg_row['Message_ID'];
            }
            $msg_stmt->close();

            // Check if connected via accepted request
            $conn_stmt = $conn->prepare("
                SELECT 1 FROM accepts a 
                JOIN posts p ON a.Req_ID = p.Req_ID 
                WHERE (a.User_ID = ? AND p.User_ID = ?) OR (a.User_ID = ? AND p.User_ID = ?)
                LIMIT 1
            ");
            $conn_stmt->bind_param("iiii", $current_user_id, $contact_id, $contact_id, $current_user_id);
            $conn_stmt->execute();
            $isConnected = $conn_stmt->get_result()->num_rows > 0;
            $conn_stmt->close();

            $contacts[] = [
                'id'           => $contact_id,
                'name'         => $row['Name'],
                'institution'  => $row['Instituition'],
                'gender'       => $row['Gender'],
                'last_message' => $last_msg,
                'is_mine'      => $last_msg_is_mine,
                'last_msg_id'  => $last_msg_id,
                'is_connected' => $isConnected
            ];
        }
        $stmt->close();

        // Sort contacts: messaged users first (by last_msg_id DESC), then connected users, then alphabetical
        usort($contacts, function($a, $b) {
            if ($a['last_msg_id'] !== $b['last_msg_id']) {
                return $b['last_msg_id'] - $a['last_msg_id'];
            }
            if ($a['is_connected'] !== $b['is_connected']) {
                return $b['is_connected'] ? 1 : -1;
            }
            return strcmp($a['name'], $b['name']);
        });

        echo json_encode(['status' => 'success', 'contacts' => $contacts]);
        break;

    case 'fetch_messages':
        $receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

        if ($receiver_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid receiver ID.']);
            exit();
        }

        // Fetch user info for header display
        $user_stmt = $conn->prepare("SELECT ID, Name, Instituition FROM user WHERE ID = ?");
        $user_stmt->bind_param("i", $receiver_id);
        $user_stmt->execute();
        $user_info = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        if (!$user_info) {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            exit();
        }

        // Fetch messages sorted chronologically by Message_ID
        $stmt = $conn->prepare("SELECT Message_ID, Sender_ID, Receiver_ID, Message 
                                FROM texts 
                                WHERE (Sender_ID = ? AND Receiver_ID = ?) 
                                   OR (Sender_ID = ? AND Receiver_ID = ?) 
                                ORDER BY Message_ID ASC");
        $stmt->bind_param("iiii", $current_user_id, $receiver_id, $receiver_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                'message_id'  => (int)$row['Message_ID'],
                'sender_id'   => (int)$row['Sender_ID'],
                'receiver_id' => (int)$row['Receiver_ID'],
                'message'     => $row['Message'],
                'is_mine'     => ((int)$row['Sender_ID'] === $current_user_id)
            ];
        }
        $stmt->close();

        echo json_encode([
            'status'   => 'success',
            'receiver' => [
                'id'          => (int)$user_info['ID'],
                'name'        => $user_info['Name'],
                'institution' => $user_info['Instituition']
            ],
            'messages' => $messages
        ]);
        break;

    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
            exit();
        }

        $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
        $message     = isset($_POST['message']) ? trim($_POST['message']) : '';

        if ($receiver_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid message recipient.']);
            exit();
        }

        if ($message === '') {
            echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty.']);
            exit();
        }

        // Insert text into database
        $stmt = $conn->prepare("INSERT INTO texts (Sender_ID, Receiver_ID, Message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $current_user_id, $receiver_id, $message);

        if ($stmt->execute()) {
            $new_msg_id = $stmt->insert_id;
            $stmt->close();
            echo json_encode([
                'status'     => 'success',
                'message_id' => $new_msg_id,
                'message'    => 'Message sent successfully.'
            ]);
        } else {
            $error = $stmt->error;
            $stmt->close();
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . $error]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

$conn->close();
?>

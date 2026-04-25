<?php
require_once 'db.php'; // Ensure this correctly sets up $conn as mysqli object

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request to client_crud.php'];

// Check DB connection
if ($conn->connect_error) {
    $response = ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    echo json_encode($response);
    exit();
}

// GET: Fetch all clients
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT ClientID, Name, Email, Phone, Address FROM Client";
    $result = $conn->query($sql);
    $clients = [];

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clients[] = $row;
            }
            $response = ['success' => true, 'message' => 'Clients fetched successfully.', 'data' => $clients];
        } else {
            $response = ['success' => true, 'message' => 'No clients found.', 'data' => []];
        }
        $result->free();
    } else {
        $response = ['success' => false, 'message' => 'Error fetching clients: ' . $conn->error];
        error_log("Client GET Error: " . $conn->error); // Log GET errors
    }
}

// POST: Add or update client
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log all incoming POST data for debugging client-side submissions
    error_log("Received POST data: " . print_r($_POST, true));

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Sanitize inputs using mysqli_real_escape_string
        $name = $conn->real_escape_string(trim($_POST['Name'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['Email'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['Phone'] ?? '')); // Ensure 'Phone' matches your DB column
        $address = $conn->real_escape_string(trim($_POST['Address'] ?? ''));

        if (empty($name) || empty($email) || empty($phone) || empty($address)) {
            $response = ['success' => false, 'message' => 'All fields are required.'];
            error_log("Client ADD Error: Required fields missing. Name: '$name', Email: '$email', Phone: '$phone', Address: '$address'");
        } else {
            // Direct SQL query construction
            $sql = "INSERT INTO Client (Name, Email, Phone, Address) VALUES ('$name', '$email', '$phone', '$address')";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Client added successfully!', 'clientID' => $conn->insert_id];
            } else {
                $response = ['success' => false, 'message' => 'Error adding client: ' . $conn->error];
                error_log("Client ADD SQL Error: " . $conn->error . " SQL: " . $sql); // Log SQL error
            }
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        // Sanitize inputs using mysqli_real_escape_string
        $name = $conn->real_escape_string(trim($_POST['Name'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['Email'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['Phone'] ?? '')); // Ensure 'Phone' matches your DB column
        $address = $conn->real_escape_string(trim($_POST['Address'] ?? ''));

        if ($id <= 0 || empty($name) || empty($email) || empty($phone) || empty($address)) {
            $response = ['success' => false, 'message' => 'Invalid Client ID or missing fields.'];
            error_log("Client UPDATE Error: Invalid Client ID ($id) or missing fields. Name: '$name', Email: '$email', Phone: '$phone', Address: '$address'");
        } else {
            // Direct SQL query construction
            $sql = "UPDATE Client SET Name = '$name', Email = '$email', Phone = '$phone', Address = '$address' WHERE ClientID = $id";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Client updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error updating client: ' . $conn->error];
                error_log("Client UPDATE SQL Error: " . $conn->error . " SQL: " . $sql); // Log SQL error
            }
        }
    } else {
        $response['message'] = 'Unknown POST action.';
        error_log("Client POST Error: Unknown action '$action'");
    }
}

// DELETE: Delete client
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id <= 0) {
        $response = ['success' => false, 'message' => 'Invalid Client ID for deletion.'];
        error_log("Client DELETE Error: Invalid Client ID ($id) for deletion.");
    } else {
        // Direct SQL query construction
        $sql = "DELETE FROM Client WHERE ClientID = $id";
        if ($conn->query($sql) === TRUE) {
            // Check if any rows were actually affected by the deletion
            if ($conn->affected_rows > 0) {
                $response = ['success' => true, 'message' => 'Client deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Client not found for deletion.'];
                error_log("Client DELETE Warning: Client ID ($id) not found for deletion.");
            }
        } else {
            // Fixed syntax here: ['message'] = '...' instead of ['message'] = '...'
            $response = ['success' => false, 'message' => 'Error deleting client: ' . $conn->error];
            error_log("Client DELETE SQL Error: " . $conn->error . " SQL: " . $sql); // Log SQL error
        }
    }
}

// Final output
echo json_encode($response);
$conn->close();
?>

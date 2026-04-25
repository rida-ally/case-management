<?php
require_once 'db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Added for CORS if needed
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS"); // Added for CORS
header("Access-Control-Allow-Headers: Content-Type"); // Added for CORS

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request to cases_crud.php'];

if ($conn->connect_error) {
    $response = ['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error];
    echo json_encode($response);
    exit();
}

// Handle GET requests for fetching all cases
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT CaseID, CaseType, Status, FilingDate, ClientID, LawyerID FROM CaseInfo";
    $result = $conn->query($sql);
    $cases = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cases[] = $row;
        }
        $response = ['success' => true, 'message' => 'Cases fetched.', 'data' => $cases];
    } else {
        $response = ['success' => true, 'message' => 'No cases found.', 'data' => []];
    }
}
// Handle POST requests for adding or updating cases
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $caseType = trim($_POST['CaseType'] ?? '');
        // Handle ClientID and LawyerID as NULL if not provided or empty
        $clientId = !empty($_POST['ClientID']) ? intval($_POST['ClientID']) : null;
        $lawyerId = !empty($_POST['LawyerID']) ? intval($_POST['LawyerID']) : null;
        $caseStatus = trim($_POST['Status'] ?? '');
        $filingDate = date('Y-m-d'); // Auto-set filing date to today

        // Use prepared statement for security and proper NULL handling
        $stmt = $conn->prepare("INSERT INTO CaseInfo (CaseType, Status, FilingDate, ClientID, LawyerID) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            // 's' for string, 's' for status, 's' for date, 'i' for int (or 's' for null), 'i' for int (or 's' for null)
            // For nullable integers, it's often safer to bind them as 's' if they could be null,
            // or ensure your database column allows NULL and handle the PHP null value correctly.
            // MySQLi's bind_param will convert PHP null to SQL NULL if the column type allows it.
            $stmt->bind_param("sssii", $caseType, $caseStatus, $filingDate, $clientId, $lawyerId);

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Case added successfully!', 'caseID' => $conn->insert_id];
            } else {
                $response = ['success' => false, 'message' => 'Error adding case: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Add case prepare failed: ' . $conn->error];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $case_type = trim($_POST['CaseType'] ?? '');
        $status = trim($_POST['Status'] ?? '');
        $filing_date = trim($_POST['FilingDate'] ?? '');
        // Handle ClientID and LawyerID as NULL if not provided or empty
        $client_id = !empty($_POST['ClientID']) ? intval($_POST['ClientID']) : null;
        $lawyer_id = !empty($_POST['LawyerID']) ? intval($_POST['LawyerID']) : null;

        if ($id > 0) {
            // Use prepared statement for security and proper NULL handling
            $stmt = $conn->prepare("UPDATE CaseInfo SET CaseType = ?, Status = ?, FilingDate = ?, ClientID = ?, LawyerID = ? WHERE CaseID = ?");
            if ($stmt) {
                // 's' for string, 's' for status, 's' for date, 'i' for int (or 's' for null), 'i' for int (or 's' for null), 'i' for id
                $stmt->bind_param("sssiii", $case_type, $status, $filing_date, $client_id, $lawyer_id, $id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Case updated successfully!'];
                } else {
                    $response = ['success' => false, 'message' => 'Error updating case: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Update case prepare failed: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid Case ID for update.'];
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}
// Handle DELETE requests for deleting cases
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        // Use prepared statement for security
        $stmt = $conn->prepare("DELETE FROM CaseInfo WHERE CaseID = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Case deleted successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error deleting case: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Delete case prepare failed: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Case ID for deletion.'];
    }
}

echo json_encode($response);
$conn->close();
?>
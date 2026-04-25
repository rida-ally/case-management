<?php
require_once 'db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request to evidence_crud.php'];

// Handle GET requests for fetching all evidence
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {
    $sql = "SELECT EvidenceID, CaseID, EvidenceType, Description FROM Evidence";
    $result = $conn->query($sql);
    $evidence = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evidence[] = $row;
        }
        $response = ['success' => true, 'message' => 'Evidence fetched.', 'data' => $evidence];
    } else {
        $response = ['success' => true, 'message' => 'No evidence found.', 'data' => []];
    }
}
// Handle POST requests for adding or updating evidence
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $caseId = intval($_POST['CaseID'] ?? 0);
        $evidenceType = $conn->real_escape_string($_POST['EvidenceType'] ?? '');
        $description = $conn->real_escape_string($_POST['Description'] ?? '');

        $sql = "INSERT INTO Evidence (CaseID, EvidenceType, Description) VALUES ($caseId, '$evidenceType', '$description')";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Evidence added successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error adding evidence: ' . $conn->error];
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $case_id = intval($_POST['CaseID'] ?? 0);
        $evidence_type = $conn->real_escape_string($_POST['EvidenceType'] ?? '');
        $description = $conn->real_escape_string($_POST['Description'] ?? '');

        if ($id > 0) {
            $sql = "UPDATE Evidence SET CaseID = $case_id, EvidenceType = '$evidence_type', Description = '$description' WHERE EvidenceID = $id";
            if ($conn->query($sql) === TRUE) {
                $response = ['success' => true, 'message' => 'Evidence updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Error updating evidence: ' . $conn->error];
            }
        } else {
            $response = ['success' => false, 'message' => 'Invalid Evidence ID for update.'];
        }
    } else {
        $response['message'] = 'Unknown POST action.';
    }
}
// Handle DELETE requests for deleting evidence
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = intval($data['id'] ?? 0);

    if ($id > 0) {
        $sql = "DELETE FROM Evidence WHERE EvidenceID = $id";
        if ($conn->query($sql) === TRUE) {
            $response = ['success' => true, 'message' => 'Evidence deleted successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting evidence: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid Evidence ID for deletion.'];
    }
}

echo json_encode($response);
$conn->close();
?>
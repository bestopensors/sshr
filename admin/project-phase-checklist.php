<?php
/**
 * API endpoint to fetch checklist for a specific phase
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

header('Content-Type: application/json');

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$phaseName = isset($_GET['phase']) ? sanitize($_GET['phase']) : '';

if (!$projectId || !$phaseName) {
    echo json_encode(['error' => 'Nedostaju parametri.']);
    exit;
}

if (!dbAvailable()) {
    echo json_encode(['error' => 'Baza podataka nije dostupna.']);
    exit;
}

try {
    // Verify project exists and user has access
    $stmt = db()->prepare("SELECT id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Projekt nije pronađen.']);
        exit;
    }
    
    // Get checklist for this phase
    $stmt = db()->prepare("SELECT * FROM project_checklist WHERE project_id = ? AND phase_name = ? ORDER BY sort_order");
    $stmt->execute([$projectId, $phaseName]);
    $checklist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure completed is boolean (not string "1" or "0")
    foreach ($checklist as &$item) {
        $item['completed'] = (bool)(int)$item['completed'];
    }
    unset($item);
    
    echo json_encode(['checklist' => $checklist]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Greška: ' . $e->getMessage()]);
}


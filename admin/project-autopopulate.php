<?php
/**
 * Auto-populate checklist for a project based on tier
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

header('Content-Type: application/json');

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if (!$projectId) {
    echo json_encode(['error' => 'Nedostaje ID projekta.']);
    exit;
}

if (!dbAvailable()) {
    echo json_encode(['error' => 'Baza podataka nije dostupna.']);
    exit;
}

try {
    // Get project details
    $stmt = db()->prepare("SELECT package_type FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['error' => 'Projekt nije pronađen.']);
        exit;
    }
    
    $tier = $project['package_type'];
    
    // Load checklist template
    require_once __DIR__ . '/checklist-templates.php';
    $template = getChecklistTemplate($tier);
    
    if (empty($template)) {
        echo json_encode(['error' => 'Template za ovaj tier nije pronađen.']);
        exit;
    }
    
    // Delete existing checklist
    $stmt = db()->prepare("DELETE FROM project_checklist WHERE project_id = ?");
    $stmt->execute([$projectId]);
    
    // Insert checklist items from template (all marked as not completed/to do)
    $inserted = 0;
    foreach ($template as $phaseName => $tasks) {
        foreach ($tasks as $index => $task) {
            $stmt = db()->prepare("INSERT INTO project_checklist (project_id, phase_name, task, completed, completed_at, sort_order) VALUES (?, ?, ?, 0, NULL, ?)");
            $stmt->execute([$projectId, $phaseName, $task, $index]);
            $inserted++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Checklist je automatski popunjen za sve faze.',
        'inserted' => $inserted
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Greška: ' . $e->getMessage()]);
}


<?php
/**
 * Admin - Projects Management
 */
$pageTitle = 'Projekti';
require_once 'includes/header.php';

$message = '';
$error = '';

// Handle success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') $message = 'Projekt uspješno kreiran!';
    if ($_GET['success'] === 'updated') $message = 'Projekt uspješno ažuriran!';
}

// Handle delete
if (isset($_GET['delete']) && dbAvailable()) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = db()->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Projekt uspješno obrisan.';
    } catch (Exception $e) {
        $error = 'Greška pri brisanju projekta.';
    }
}

// Handle status change
if (isset($_GET['status']) && isset($_GET['id']) && dbAvailable()) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];
    if (in_array($status, ['future', 'current', 'past'])) {
        try {
            $stmt = db()->prepare("UPDATE projects SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            // If marking as completed (past), mark the final phase as completed
            if ($status === 'past') {
                $stmt = db()->prepare("UPDATE project_phases SET completed = 1, completed_at = NOW() WHERE project_id = ? AND phase_name = 'final'");
                $stmt->execute([$id]);
                
                // Also update project to final phase if not already
                $stmt = db()->prepare("UPDATE projects SET current_phase = 'final' WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $message = 'Status projekta ažuriran.';
        } catch (Exception $e) {
            $error = 'Greška pri ažuriranju statusa.';
        }
    }
}

// Handle phase advancement
if (isset($_GET['advance_phase']) && isset($_GET['id']) && dbAvailable()) {
    $id = (int)$_GET['id'];
    try {
        // Get current project
        $stmt = db()->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        
        if ($project) {
            $currentPhase = $project['current_phase'];
            $phaseOrder = ['agreement', 'planning', 'design', 'development', 'content', 'testing', 'final'];
            $currentIndex = array_search($currentPhase, $phaseOrder);
            
            // Check if all checklist items for current phase are completed
            $stmt = db()->prepare("SELECT COUNT(*) as total, SUM(completed) as completed FROM project_checklist WHERE project_id = ? AND phase_name = ?");
            $stmt->execute([$id, $currentPhase]);
            $checklistStatus = $stmt->fetch();
            
            if ($checklistStatus['total'] > 0 && $checklistStatus['completed'] < $checklistStatus['total']) {
                $error = 'Nisu svi zadaci u trenutnoj fazi završeni. Molimo završite sve zadatke prije prelaska na sljedeću fazu.';
            } else {
                // Move to next phase
                if ($currentIndex !== false && $currentIndex < count($phaseOrder) - 1) {
                    $nextPhase = $phaseOrder[$currentIndex + 1];
                    
                    // Mark current phase and ALL previous phases as completed
                    for ($i = 0; $i <= $currentIndex; $i++) {
                        $phaseToComplete = $phaseOrder[$i];
                        $stmt = db()->prepare("UPDATE project_phases SET completed = 1, completed_at = NOW() WHERE project_id = ? AND phase_name = ?");
                        $stmt->execute([$id, $phaseToComplete]);
                    }
                    
                    // Update project to next phase
                    $stmt = db()->prepare("UPDATE projects SET current_phase = ? WHERE id = ?");
                    $stmt->execute([$nextPhase, $id]);
                    
                    // Set start date and time for next phase to NOW() in CET when advancing (log the exact time)
                    $timezone = new DateTimeZone('Europe/Zagreb'); // CET
                    $now = new DateTime('now', $timezone);
                    $nowDate = $now->format('Y-m-d');
                    $nowDateTime = $now->format('Y-m-d H:i:s');
                    
                    // Update the phase with start date (date only) and log the exact datetime in completed_at
                    $stmt = db()->prepare("UPDATE project_phases SET start_date = ?, completed_at = ? WHERE project_id = ? AND phase_name = ?");
                    $stmt->execute([$nowDate, $nowDateTime, $id, $nextPhase]);
                    
                    // If next phase doesn't exist in project_phases, create it with current date
                    $stmt = db()->prepare("SELECT id FROM project_phases WHERE project_id = ? AND phase_name = ?");
                    $stmt->execute([$id, $nextPhase]);
                    if (!$stmt->fetch()) {
                        // Get the phase index for sort_order
                        $nextPhaseIndex = array_search($nextPhase, $phaseOrder);
                        $stmt = db()->prepare("INSERT INTO project_phases (project_id, phase_name, start_date, sort_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$id, $nextPhase, $nowDate, $nextPhaseIndex]);
                    }
                    
                    // Get Croatian phase name for message
                    $phaseNames = [
                        'agreement' => 'Dogovor',
                        'planning' => 'Planiranje',
                        'design' => 'Dizajn',
                        'development' => 'Razvoj',
                        'content' => 'Sadržaj',
                        'testing' => 'Testiranje',
                        'final' => 'Finalizacija'
                    ];
                    $nextPhaseName = $phaseNames[$nextPhase] ?? ucfirst($nextPhase);
                    $message = 'Projekt je prebačen na fazu: ' . $nextPhaseName;
                } else {
                    $error = 'Projekt je već u posljednjoj fazi.';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Greška pri prelasku na sljedeću fazu: ' . $e->getMessage();
    }
}

$projects = [];

// Get projects from database
if (dbAvailable()) {
    try {
        $stmt = db()->query("SELECT * FROM projects ORDER BY 
            CASE status 
                WHEN 'current' THEN 1 
                WHEN 'future' THEN 2 
                WHEN 'past' THEN 3 
            END, 
            deadline ASC");
        $projects = $stmt->fetchAll();
        
        // Calculate phase info for each project
        foreach ($projects as &$project) {
            $projectId = $project['id'];
            
            // Get phase durations
            $stmt = db()->prepare("SELECT phase_name, duration_days, start_date, end_date, completed FROM project_phases WHERE project_id = ? ORDER BY sort_order");
            $stmt->execute([$projectId]);
            $phases = $stmt->fetchAll();
            
            $project['phases'] = [];
            foreach ($phases as $phase) {
                $project['phases'][$phase['phase_name']] = $phase;
            }
            
            // Calculate next phase start time
            $currentPhase = $project['current_phase'];
            $phaseOrder = ['agreement', 'planning', 'design', 'development', 'content', 'testing', 'final'];
            $phaseNames = [
                'agreement' => 'Dogovor',
                'planning' => 'Planiranje',
                'design' => 'Dizajn',
                'development' => 'Razvoj',
                'content' => 'Sadržaj',
                'testing' => 'Testiranje',
                'final' => 'Finalizacija'
            ];
            $currentIndex = array_search($currentPhase, $phaseOrder);
            
            if ($currentIndex !== false && $currentIndex < count($phaseOrder) - 1) {
                $nextPhase = $phaseOrder[$currentIndex + 1];
                // Calculate countdown based on CURRENT phase start date + duration days in CET
                if (isset($project['phases'][$currentPhase])) {
                    $currentPhaseData = $project['phases'][$currentPhase];
                    // Check if current phase has start_date and duration
                    if (!empty($currentPhaseData['start_date']) && $currentPhaseData['duration_days'] > 0) {
                        // Use CET timezone for calculations
                        $timezone = new DateTimeZone('Europe/Zagreb'); // CET
                        // Calculate: start_date + duration_days = when current phase ends (next phase should start)
                        // Start from the beginning of the start date (00:00:00)
                        $startDate = new DateTime($currentPhaseData['start_date'] . ' 00:00:00', $timezone);
                        // Add the duration in days (this gives us the end date at 00:00:00)
                        $startDate->modify('+' . (int)$currentPhaseData['duration_days'] . ' days');
                        // Set to end of that day (23:59:59) in CET - this is when the current phase ends
                        $startDate->setTime(23, 59, 59);
                        // Format in ISO 8601 with timezone for JavaScript
                        $project['next_phase_start'] = $startDate->format('Y-m-d H:i:s');
                        $project['next_phase_start_iso'] = $startDate->format('c'); // ISO 8601 with timezone
                        $project['next_phase_name'] = $phaseNames[$nextPhase] ?? ucfirst($nextPhase);
                    }
                }
            }
        }
        unset($project);
    } catch (Exception $e) {
        $error = 'Greška pri učitavanju projekata: ' . $e->getMessage();
    }
}
?>

<?php if ($message): ?>
    <div class="alert alert--success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (!dbAvailable()): ?>
<div class="alert alert--error">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <span>Baza podataka nije povezana. Za korištenje projekata, konfigurirajte bazu podataka i pokrenite <code>install/projects_schema.sql</code>.</span>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Projekti</h2>
        <?php if (dbAvailable()): ?>
        <a href="project-edit.php" class="btn btn--primary btn--sm">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Dodaj projekt
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($projects)): ?>
    <div style="padding: 40px; text-align: center; color: var(--text-secondary);">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 16px; opacity: 0.5;">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        </svg>
        <p>Nema projekata. Kliknite "Dodaj projekt" da započnete.</p>
    </div>
    <?php else: ?>
    
    <!-- Current Projects -->
    <?php 
    $currentProjects = array_filter($projects, function($p) { return $p['status'] === 'current'; });
    if (!empty($currentProjects)): 
    ?>
    <div style="margin-bottom: 32px;">
        <h3 style="color: var(--primary); margin-bottom: 16px; font-size: 18px;">Trenutni projekti</h3>
        <div style="display: grid; gap: 16px;">
            <?php foreach ($currentProjects as $project): ?>
            <?php include __DIR__ . '/project-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Future Projects -->
    <?php 
    $futureProjects = array_filter($projects, function($p) { return $p['status'] === 'future'; });
    if (!empty($futureProjects)): 
    ?>
    <div style="margin-bottom: 32px;">
        <h3 style="color: var(--accent); margin-bottom: 16px; font-size: 18px;">Budući projekti</h3>
        <div style="display: grid; gap: 16px;">
            <?php foreach ($futureProjects as $project): ?>
            <?php include __DIR__ . '/project-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Past Projects -->
    <?php 
    $pastProjects = array_filter($projects, function($p) { return $p['status'] === 'past'; });
    if (!empty($pastProjects)): 
    ?>
    <div style="margin-bottom: 32px;">
        <h3 style="color: var(--text-secondary); margin-bottom: 16px; font-size: 18px;">Završeni projekti</h3>
        <div style="display: grid; gap: 16px;">
            <?php foreach ($pastProjects as $project): ?>
            <?php include __DIR__ . '/project-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>


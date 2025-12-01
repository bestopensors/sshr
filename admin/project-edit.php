<?php
/**
 * Admin - Project Edit
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && dbAvailable()) {
    try {
        $name = sanitize(trim($_POST['name'] ?? ''));
        $clientName = sanitize(trim($_POST['client_name'] ?? ''));
        $clientEmail = sanitize(trim($_POST['client_email'] ?? ''));
        $packageType = sanitize($_POST['package_type'] ?? 'basic');
        $agreementDate = $_POST['agreement_date'] ?? '';
        $deadline = $_POST['deadline'] ?? '';
        $status = sanitize($_POST['status'] ?? 'current');
        $currentPhase = sanitize($_POST['current_phase'] ?? 'planning');
        $notes = sanitize(trim($_POST['notes'] ?? ''));
        
        // Validate required fields
        if (empty($name) || empty($agreementDate) || empty($deadline)) {
            throw new Exception('Ime projekta, datum sporazuma i rok su obavezni.');
        }
        
        // Insert or update project
        if ($id > 0) {
            $stmt = db()->prepare("UPDATE projects SET name = ?, client_name = ?, client_email = ?, package_type = ?, agreement_date = ?, deadline = ?, status = ?, current_phase = ?, notes = ? WHERE id = ?");
            $stmt->execute([$name, $clientName, $clientEmail, $packageType, $agreementDate, $deadline, $status, $currentPhase, $notes, $id]);
        } else {
            $stmt = db()->prepare("INSERT INTO projects (name, client_name, client_email, package_type, agreement_date, deadline, status, current_phase, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $clientName, $clientEmail, $packageType, $agreementDate, $deadline, $status, $currentPhase, $notes]);
            $id = db()->lastInsertId();
        }
        
        // Handle phases
        $phases = ['planning', 'design', 'development', 'content', 'testing', 'final'];
        
        // Delete existing phases
        $stmt = db()->prepare("DELETE FROM project_phases WHERE project_id = ?");
        $stmt->execute([$id]);
        
        // Insert phases with durations
        $startDate = new DateTime($agreementDate);
        foreach ($phases as $index => $phaseName) {
            $durationDays = (int)($_POST['phase_duration_' . $phaseName] ?? 0);
            
            if ($durationDays > 0) {
                $phaseStartDate = clone $startDate;
                if ($index > 0) {
                    // Calculate start date based on previous phases
                    $prevPhases = array_slice($phases, 0, $index);
                    foreach ($prevPhases as $prevPhase) {
                        $prevDuration = (int)($_POST['phase_duration_' . $prevPhase] ?? 0);
                        if ($prevDuration > 0) {
                            $phaseStartDate->modify('+' . $prevDuration . ' days');
                        }
                    }
                }
                
                $phaseEndDate = clone $phaseStartDate;
                $phaseEndDate->modify('+' . $durationDays . ' days');
                
                $completed = isset($_POST['phase_completed_' . $phaseName]) ? 1 : 0;
                $completedAt = $completed ? date('Y-m-d H:i:s') : null;
                
                $stmt = db()->prepare("INSERT INTO project_phases (project_id, phase_name, duration_days, start_date, end_date, completed, completed_at, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $phaseName, $durationDays, $phaseStartDate->format('Y-m-d'), $phaseEndDate->format('Y-m-d'), $completed, $completedAt, $index]);
            }
        }
        
        // Handle checklist items
        $stmt = db()->prepare("DELETE FROM project_checklist WHERE project_id = ?");
        $stmt->execute([$id]);
        
        if (isset($_POST['checklist']) && is_array($_POST['checklist'])) {
            foreach ($_POST['checklist'] as $phaseName => $items) {
                if (is_array($items)) {
                    foreach ($items as $index => $task) {
                        $task = trim($task);
                        if (!empty($task)) {
                            $task = sanitize($task);
                            $completed = isset($_POST['checklist_completed'][$phaseName][$index]) ? 1 : 0;
                            $completedAt = $completed ? date('Y-m-d H:i:s') : null;
                            
                            $stmt = db()->prepare("INSERT INTO project_checklist (project_id, phase_name, task, completed, completed_at, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$id, $phaseName, $task, $completed, $completedAt, $index]);
                        }
                    }
                }
            }
        }
        
        header('Location: projects.php?success=' . ($id ? 'updated' : 'created'));
        exit;
        
    } catch (Exception $e) {
        $error = 'Greška: ' . $e->getMessage();
    }
}

$pageTitle = $id > 0 ? 'Uredi Projekt' : 'Novi Projekt';
require_once 'includes/header.php';

$project = null;
$phases = [];
$checklist = [];

// Load project data
if ($id > 0 && dbAvailable()) {
    try {
        $stmt = db()->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        
        if ($project) {
            // Load phases
            $stmt = db()->prepare("SELECT * FROM project_phases WHERE project_id = ? ORDER BY sort_order");
            $stmt->execute([$id]);
            $phasesData = $stmt->fetchAll();
            foreach ($phasesData as $phase) {
                $phases[$phase['phase_name']] = $phase;
            }
            
            // Load checklist
            $stmt = db()->prepare("SELECT * FROM project_checklist WHERE project_id = ? ORDER BY phase_name, sort_order");
            $stmt->execute([$id]);
            $checklistData = $stmt->fetchAll();
            foreach ($checklistData as $item) {
                if (!isset($checklist[$item['phase_name']])) {
                    $checklist[$item['phase_name']] = [];
                }
                $checklist[$item['phase_name']][] = $item;
            }
        }
    } catch (Exception $e) {
        $error = 'Greška pri učitavanju projekta: ' . $e->getMessage();
    }
}

// Default values
if (!$project) {
    $project = [
        'name' => '',
        'client_name' => '',
        'client_email' => '',
        'package_type' => 'basic',
        'agreement_date' => date('Y-m-d'),
        'deadline' => date('Y-m-d', strtotime('+30 days')),
        'status' => 'current',
        'current_phase' => 'planning',
        'notes' => ''
    ];
}

$phaseNames = [
    'planning' => 'Planiranje',
    'design' => 'Dizajn',
    'development' => 'Razvoj',
    'content' => 'Sadržaj',
    'testing' => 'Testiranje',
    'final' => 'Finalizacija'
];

$packageTypes = [
    'basic' => 'Osnovna Stranica',
    'professional' => 'Profesionalna Stranica',
    'premium' => 'Premium Stranica',
    'custom' => 'Custom Projekt'
];
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title"><?php echo $id > 0 ? 'Uredi Projekt' : 'Novi Projekt'; ?></h2>
            <a href="projects.php" class="btn btn--secondary btn--sm">← Natrag</a>
        </div>
        
        <div class="card__body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="name">Naziv projekta *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="package_type">Tip paketa *</label>
                    <select class="form-control" id="package_type" name="package_type" required>
                        <?php foreach ($packageTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $project['package_type'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="client_name">Ime klijenta</label>
                    <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="client_email">Email klijenta</label>
                    <input type="email" class="form-control" id="client_email" name="client_email" value="<?php echo htmlspecialchars($project['client_email']); ?>">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="agreement_date">Datum sporazuma *</label>
                    <input type="date" class="form-control" id="agreement_date" name="agreement_date" value="<?php echo $project['agreement_date']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="deadline">Rok *</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo $project['deadline']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="future" <?php echo $project['status'] === 'future' ? 'selected' : ''; ?>>Budući</option>
                        <option value="current" <?php echo $project['status'] === 'current' ? 'selected' : ''; ?>>Trenutni</option>
                        <option value="past" <?php echo $project['status'] === 'past' ? 'selected' : ''; ?>>Završen</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="current_phase">Trenutna faza</label>
                <select class="form-control" id="current_phase" name="current_phase">
                    <?php foreach ($phaseNames as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $project['current_phase'] === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">Napomene</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($project['notes']); ?></textarea>
            </div>
        </div>
    </div>
    
    <!-- Phases Configuration -->
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Konfiguracija faza</h2>
            <p style="color: var(--text-secondary); font-size: 13px; margin-top: 8px;">Postavite trajanje svake faze u danima</p>
        </div>
        
        <div class="card__body">
            <div style="display: grid; gap: 16px;">
                <?php foreach ($phaseNames as $phaseKey => $phaseLabel): ?>
                <div style="padding: 16px; background: var(--bg-input); border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label style="font-weight: 600; color: var(--text-primary);" for="phase_duration_<?php echo $phaseKey; ?>">
                            <?php echo $phaseLabel; ?>
                        </label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" 
                                   class="form-control" 
                                   id="phase_duration_<?php echo $phaseKey; ?>" 
                                   name="phase_duration_<?php echo $phaseKey; ?>" 
                                   value="<?php echo isset($phases[$phaseKey]) ? $phases[$phaseKey]['duration_days'] : ''; ?>" 
                                   min="0" 
                                   style="width: 80px;"
                                   placeholder="dana">
                            <span style="color: var(--text-secondary); font-size: 14px;">dana</span>
                            <?php if (isset($phases[$phaseKey]) && $phases[$phaseKey]['completed']): ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="phase_completed_<?php echo $phaseKey; ?>" checked>
                                <span style="color: var(--success); font-size: 12px;">Završeno</span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Checklist for this phase -->
                    <div style="margin-top: 12px;">
                        <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">Checklist za <?php echo $phaseLabel; ?>:</div>
                        <div id="checklist-<?php echo $phaseKey; ?>" style="display: grid; gap: 8px;">
                            <?php if (isset($checklist[$phaseKey])): ?>
                                <?php foreach ($checklist[$phaseKey] as $index => $item): ?>
                                <div style="display: flex; align-items: center; gap: 8px; padding: 8px; background: var(--bg-dark); border-radius: 6px;">
                                    <input type="checkbox" 
                                           name="checklist_completed[<?php echo $phaseKey; ?>][<?php echo $index; ?>]" 
                                           <?php echo $item['completed'] ? 'checked' : ''; ?>
                                           style="cursor: pointer;">
                                    <input type="text" 
                                           class="form-control" 
                                           name="checklist[<?php echo $phaseKey; ?>][]" 
                                           value="<?php echo htmlspecialchars($item['task']); ?>" 
                                           placeholder="Zadatak..."
                                           style="flex: 1;">
                                    <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn--secondary btn--sm" style="margin-top: 8px;" onclick="addChecklistItem('<?php echo $phaseKey; ?>')">
                            + Dodaj zadatak
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div style="display: flex; gap: 12px; margin-top: 24px;">
        <button type="submit" class="btn btn--primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Spremi projekt
        </button>
        <a href="projects.php" class="btn btn--secondary">Odustani</a>
    </div>
</form>

<script>
function addChecklistItem(phaseKey) {
    const container = document.getElementById('checklist-' + phaseKey);
    const index = container.children.length;
    const div = document.createElement('div');
    div.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 8px; background: var(--bg-dark); border-radius: 6px;';
    div.innerHTML = `
        <input type="checkbox" name="checklist_completed[${phaseKey}][${index}]" style="cursor: pointer;">
        <input type="text" class="form-control" name="checklist[${phaseKey}][]" placeholder="Zadatak..." style="flex: 1;" required>
        <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(div);
}
</script>

<?php require_once 'includes/footer.php'; ?>


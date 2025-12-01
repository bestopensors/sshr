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
        $currentPhase = sanitize($_POST['current_phase'] ?? 'agreement');
        $notes = sanitize(trim($_POST['notes'] ?? ''));
        
        // Validate required fields
        if (empty($name) || empty($agreementDate) || empty($deadline)) {
            throw new Exception('Ime projekta, datum sporazuma i rok su obavezni.');
        }
        
        // Handle agreement status and meeting
        $hasAgreement = isset($_POST['has_agreement']) ? 1 : 0;
        $meetingDate = !empty($_POST['meeting_date']) ? $_POST['meeting_date'] : null;
        
        // Insert or update project
        if ($id > 0) {
            $stmt = db()->prepare("UPDATE projects SET name = ?, client_name = ?, client_email = ?, package_type = ?, agreement_date = ?, deadline = ?, status = ?, current_phase = ?, notes = ?, has_agreement = ?, meeting_date = ? WHERE id = ?");
            $stmt->execute([$name, $clientName, $clientEmail, $packageType, $agreementDate, $deadline, $status, $currentPhase, $notes, $hasAgreement, $meetingDate, $id]);
        } else {
            $stmt = db()->prepare("INSERT INTO projects (name, client_name, client_email, package_type, agreement_date, deadline, status, current_phase, notes, has_agreement, meeting_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $clientName, $clientEmail, $packageType, $agreementDate, $deadline, $status, $currentPhase, $notes, $hasAgreement, $meetingDate]);
            $id = db()->lastInsertId();
        }
        
        // Handle phases
        $phases = ['agreement', 'planning', 'design', 'development', 'content', 'testing', 'final'];
        
        // Delete existing phases
        $stmt = db()->prepare("DELETE FROM project_phases WHERE project_id = ?");
        $stmt->execute([$id]);
        
        // Handle meeting scheduling
        if (!empty($meetingDate)) {
            // Delete existing meetings for this project
            $stmt = db()->prepare("DELETE FROM project_meetings WHERE project_id = ?");
            $stmt->execute([$id]);
            
            // Insert new meeting
            $stmt = db()->prepare("INSERT INTO project_meetings (project_id, meeting_date, meeting_type, notes) VALUES (?, ?, 'agreement', ?)");
            $meetingNotes = sanitize(trim($_POST['meeting_notes'] ?? ''));
            $stmt->execute([$id, $meetingDate, $meetingNotes]);
        }
        
        // Insert phases with durations
        $startDate = new DateTime($agreementDate);
        foreach ($phases as $index => $phaseName) {
            $durationDays = (int)($_POST['phase_duration_' . $phaseName] ?? 0);
            
            if ($durationDays > 0 || $phaseName === 'agreement') {
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
            
            // Load meeting
            $stmt = db()->prepare("SELECT * FROM project_meetings WHERE project_id = ? ORDER BY meeting_date DESC LIMIT 1");
            $stmt->execute([$id]);
            $meeting = $stmt->fetch();
            if ($meeting) {
                $project['meeting_date'] = $meeting['meeting_date'];
                $project['meeting_notes'] = $meeting['notes'];
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
        'current_phase' => 'agreement',
        'has_agreement' => 0,
        'meeting_date' => null,
        'notes' => ''
    ];
}

$phaseNames = [
    'agreement' => 'Dogovor',
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
                    <label for="client_email">Email klijenta (opcionalno)</label>
                    <input type="email" class="form-control" id="client_email" name="client_email" value="<?php echo htmlspecialchars($project['client_email'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- Agreement Status Section -->
            <div style="padding: 20px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--primary);">
                <h3 style="color: var(--primary); margin-bottom: 16px; font-size: 16px; font-weight: 600;">Status Dogovora</h3>
                <div style="display: grid; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; background: rgba(255, 255, 255, 0.05); border-radius: 6px;">
                        <input type="checkbox" name="has_agreement" value="1" <?php echo (isset($project['has_agreement']) && $project['has_agreement']) ? 'checked' : ''; ?>>
                        <span style="color: var(--text-primary); font-weight: 500;">Sporazum je potpisan</span>
                    </label>
                    
                    <div id="meeting-section" style="<?php echo (isset($project['has_agreement']) && $project['has_agreement']) ? 'display: none;' : ''; ?>">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 12px; background: rgba(255, 255, 255, 0.05); border-radius: 6px;">
                            <div class="form-group">
                                <label for="meeting_date" style="color: var(--text-primary); font-weight: 500; margin-bottom: 6px; display: block;">Datum i vrijeme sastanka</label>
                                <input type="datetime-local" class="form-control" id="meeting_date" name="meeting_date" value="<?php echo isset($project['meeting_date']) && $project['meeting_date'] ? date('Y-m-d\TH:i', strtotime($project['meeting_date'])) : ''; ?>" style="background: var(--bg-dark); border: 1px solid rgba(255, 255, 255, 0.1); color: var(--text-primary); color-scheme: dark;">
                            </div>
                            <div class="form-group">
                                <label for="meeting_notes" style="color: var(--text-primary); font-weight: 500; margin-bottom: 6px; display: block;">Napomene o sastanku</label>
                                <input type="text" class="form-control" id="meeting_notes" name="meeting_notes" value="<?php echo isset($project['meeting_notes']) ? htmlspecialchars($project['meeting_notes']) : ''; ?>" placeholder="Lokacija, tema, itd." style="background: var(--bg-dark); border: 1px solid rgba(255, 255, 255, 0.1);">
                            </div>
                        </div>
                    </div>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: var(--primary); margin: 0; font-size: 18px;">Faze projekta</h3>
                <?php if ($id > 0): ?>
                <button type="button" class="btn btn--accent btn--sm" onclick="autopopulateChecklist(<?php echo $id; ?>, '<?php echo $project['package_type']; ?>')" id="autopopulate-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;">
                        <path d="M12 2v20M2 12h20"></path>
                    </svg>
                    Auto-popuni checklist
                </button>
                <?php endif; ?>
            </div>
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
                                   value="<?php echo isset($phases[$phaseKey]) && $phases[$phaseKey]['duration_days'] > 0 ? $phases[$phaseKey]['duration_days'] : ''; ?>" 
                                   min="0" 
                                   style="width: 80px; background: var(--bg-dark); border: 1px solid rgba(255, 255, 255, 0.1); color: var(--text-primary);"
                                   placeholder="0">
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

// Toggle meeting section based on agreement checkbox
document.addEventListener('DOMContentLoaded', function() {
    const agreementCheckbox = document.querySelector('input[name="has_agreement"]');
    const meetingSection = document.getElementById('meeting-section');
    
    if (agreementCheckbox && meetingSection) {
        agreementCheckbox.addEventListener('change', function() {
            meetingSection.style.display = this.checked ? 'none' : 'block';
        });
    }
});

// Auto-populate checklist function
function autopopulateChecklist(projectId, tier) {
    if (!confirm('Želite li automatski popuniti checklist za sve faze? Ovo će zamijeniti postojeće zadatke.')) {
        return;
    }
    
    const btn = document.getElementById('autopopulate-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Učitavanje...';
    
    fetch('project-autopopulate.php?project_id=' + projectId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Greška: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }
            
            if (data.success) {
                alert(data.message + ' (' + data.inserted + ' zadataka dodano)');
                // Reload page to show new checklist items
                window.location.reload();
            }
        })
        .catch(error => {
            alert('Greška pri učitavanju: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>


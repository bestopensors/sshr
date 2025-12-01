<?php
// Project Card Component
$phaseNames = [
    'agreement' => 'Dogovor',
    'planning' => 'Planiranje',
    'design' => 'Dizajn',
    'development' => 'Razvoj',
    'content' => 'Sadržaj',
    'testing' => 'Testiranje',
    'final' => 'Finalizacija'
];

$packageNames = [
    'basic' => 'Osnovna',
    'professional' => 'Profesionalna',
    'premium' => 'Premium',
    'custom' => 'Custom'
];

$statusColors = [
    'current' => 'var(--success)',
    'future' => 'var(--accent)',
    'past' => 'var(--text-secondary)'
];

$currentPhase = $project['current_phase'];
$phaseProgress = 0;
$totalPhases = 7;
$phaseOrder = ['agreement', 'planning', 'design', 'development', 'content', 'testing', 'final'];
$currentPhaseIndex = array_search($currentPhase, $phaseOrder);
if ($currentPhaseIndex !== false) {
    $phaseProgress = (($currentPhaseIndex + 1) / $totalPhases) * 100;
}

$agreementDate = new DateTime($project['agreement_date']);
$deadline = new DateTime($project['deadline']);
$now = new DateTime();
$daysRemaining = $now->diff($deadline)->days;
$isOverdue = $now > $deadline;
?>

<div class="card" style="border-left: 4px solid <?php echo $statusColors[$project['status']]; ?>;">
    <div class="card__header" style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h3 style="margin: 0; color: var(--text-primary);"><?php echo htmlspecialchars($project['name']); ?></h3>
            <?php if ($project['client_name']): ?>
            <p style="margin: 4px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                Klijent: <?php echo htmlspecialchars($project['client_name']); ?>
            </p>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 8px;">
            <span class="badge badge--<?php echo $project['status'] === 'current' ? 'success' : ($project['status'] === 'future' ? 'warning' : 'danger'); ?>" style="<?php echo $project['status'] === 'past' ? 'background-color: var(--danger) !important; color: white !important;' : ''; ?>">
                <?php 
                echo $project['status'] === 'current' ? 'Trenutni' : 
                    ($project['status'] === 'future' ? 'Budući' : 'Završen'); 
                ?>
            </span>
            <span class="badge badge--info">
                <?php echo $packageNames[$project['package_type']]; ?>
            </span>
        </div>
    </div>
    
    <div class="card__body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Datum sporazuma</div>
                <div style="font-weight: 600; color: var(--text-primary);">
                    <?php echo $agreementDate->format('d.m.Y'); ?>
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Rok</div>
                <div style="font-weight: 600; color: <?php echo $isOverdue ? 'var(--danger)' : 'var(--text-primary)'; ?>;">
                    <?php echo $deadline->format('d.m.Y'); ?>
                    <?php if ($isOverdue): ?>
                        <span style="color: var(--danger); font-size: 12px;">(Zakasnilo!)</span>
                    <?php elseif ($daysRemaining <= 7): ?>
                        <span style="color: var(--warning); font-size: 12px;">(<?php echo $daysRemaining; ?> dana)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Trenutna faza</div>
                <div style="font-weight: 600; color: var(--primary);">
                    <?php echo $phaseNames[$currentPhase]; ?>
                </div>
            </div>
        </div>
        
        <!-- Phase Progress -->
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 12px; color: var(--text-secondary);">Napredak projekta</span>
                <span style="font-size: 12px; color: var(--text-secondary);"><?php echo round($phaseProgress); ?>%</span>
            </div>
            <div style="height: 8px; background: var(--bg-input); border-radius: 4px; overflow: hidden;">
                <div style="height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); width: <?php echo $phaseProgress; ?>%; transition: width 0.3s;"></div>
            </div>
        </div>
        
        <!-- Phase Timeline -->
        <div style="margin-bottom: 20px;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">Faze projekta</div>
            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                <?php 
                $phases = ['agreement', 'planning', 'design', 'development', 'content', 'testing', 'final'];
                foreach ($phases as $index => $phase): 
                    $isCurrent = $phase === $currentPhase;
                    $isCompleted = isset($project['phases'][$phase]) && $project['phases'][$phase]['completed'];
                    // Mark as completed if it's a previous phase (before current)
                    $isPast = $currentPhaseIndex !== false && $index < $currentPhaseIndex;
                    // If it's a past phase, it should be marked as completed (even if DB doesn't have it yet)
                    if ($isPast) {
                        $isCompleted = true;
                    }
                    // If project status is 'past', mark final phase as completed
                    if ($project['status'] === 'past' && $phase === 'final') {
                        $isCompleted = true;
                    }
                ?>
                <div style="
                    padding: 6px 12px; 
                    border-radius: 6px; 
                    font-size: 11px; 
                    font-weight: 500;
                    background: <?php echo $isCompleted ? 'var(--success)' : ($isCurrent ? 'var(--primary)' : 'var(--bg-input)'); ?>;
                    color: <?php echo $isCompleted || $isCurrent ? 'white' : 'var(--text-secondary)'; ?>;
                    border: 1px solid <?php echo $isCurrent ? 'var(--primary)' : 'transparent'; ?>;
                    cursor: pointer;
                    transition: all 0.2s;
                    user-select: none;
                " 
                onclick="showPhaseChecklist(<?php echo $project['id']; ?>, '<?php echo $phase; ?>', '<?php echo htmlspecialchars($phaseNames[$phase], ENT_QUOTES); ?>')"
                onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                    <?php echo $phaseNames[$phase]; ?>
                    <?php if (isset($project['phases'][$phase]) && $project['phases'][$phase]['duration_days']): ?>
                        <span style="opacity: 0.8;">(<?php echo $project['phases'][$phase]['duration_days']; ?>d)</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Next Phase Countdown -->
        <?php if (isset($project['next_phase_start']) && $project['status'] === 'current'): ?>
        <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; margin-bottom: 16px;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Sljedeća faza: <?php echo $project['next_phase_name']; ?></div>
            <div style="font-size: 18px; font-weight: 600; color: var(--accent);" id="countdown-<?php echo $project['id']; ?>">
                Učitavanje...
            </div>
            <script>
            (function() {
                // Parse the target date - use ISO format if available, otherwise parse as CET
                <?php if (isset($project['next_phase_start_iso'])): ?>
                const targetDate = new Date('<?php echo $project['next_phase_start_iso']; ?>').getTime();
                <?php else: ?>
                // If no timezone info, assume CET (Europe/Zagreb)
                const targetDateStr = '<?php echo $project['next_phase_start']; ?>';
                // Add CET timezone offset if not present
                const targetDate = new Date(targetDateStr + (targetDateStr.includes('T') ? '' : 'T00:00:00') + '+01:00').getTime();
                <?php endif; ?>
                const countdownEl = document.getElementById('countdown-<?php echo $project['id']; ?>');
                
                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = targetDate - now;
                    
                    if (distance < 0) {
                        countdownEl.textContent = 'Faza treba početi!';
                        countdownEl.style.color = 'var(--warning)';
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    
                    if (days > 0) {
                        countdownEl.textContent = days + ' dana, ' + hours + ' sati';
                    } else if (hours > 0) {
                        countdownEl.textContent = hours + ' sati, ' + minutes + ' minuta';
                    } else {
                        countdownEl.textContent = minutes + ' minuta';
                    }
                }
                
                updateCountdown();
                setInterval(updateCountdown, 60000); // Update every minute
            })();
            </script>
        </div>
        <?php endif; ?>
        
        <!-- Agreement Status -->
        <?php if ($currentPhase === 'agreement'): ?>
        <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--warning);">
            <?php 
            $hasAgreement = isset($project['has_agreement']) && $project['has_agreement'];
            $meetingDate = isset($project['meeting_date']) && $project['meeting_date'] ? new DateTime($project['meeting_date']) : null;
            ?>
            <?php if ($hasAgreement): ?>
                <div style="color: var(--success); font-weight: 600; margin-bottom: 4px;">✓ Sporazum je potpisan</div>
            <?php else: ?>
                <div style="color: var(--warning); font-weight: 600; margin-bottom: 8px;">⚠ Sporazum nije potpisan</div>
                <?php if ($meetingDate): ?>
                    <div style="font-size: 12px; color: var(--text-secondary);">
                        Sastanak zakazan: <?php echo $meetingDate->format('d.m.Y H:i'); ?>
                    </div>
                <?php else: ?>
                    <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn--primary btn--sm" style="margin-top: 8px;">
                        Zakazi sastanak
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Current Phase Checklist Status -->
        <?php 
        // Load checklist for current phase
        $currentPhaseChecklist = [];
        if (dbAvailable()) {
            try {
                $stmt = db()->prepare("SELECT * FROM project_checklist WHERE project_id = ? AND phase_name = ? ORDER BY sort_order");
                $stmt->execute([$project['id'], $currentPhase]);
                $currentPhaseChecklist = $stmt->fetchAll();
            } catch (Exception $e) {
                // Ignore
            }
        }
        $totalTasks = count($currentPhaseChecklist);
        $completedTasks = count(array_filter($currentPhaseChecklist, function($task) { return $task['completed']; }));
        $canAdvance = $totalTasks === 0 || $completedTasks === $totalTasks;
        ?>
        
        <?php if ($totalTasks > 0 && $project['status'] === 'current'): ?>
        <div style="padding: 12px; background: var(--bg-input); border-radius: 8px; margin-bottom: 16px;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">
                Checklist za <?php echo $phaseNames[$currentPhase]; ?>: <?php echo $completedTasks; ?>/<?php echo $totalTasks; ?> završeno
            </div>
            <div style="display: grid; gap: 6px;">
                <?php foreach ($currentPhaseChecklist as $task): ?>
                <div style="display: flex; align-items: center; gap: 8px; padding: 6px; background: var(--bg-dark); border-radius: 4px;">
                    <span style="color: <?php echo $task['completed'] ? 'var(--success)' : 'var(--text-secondary)'; ?>;">
                        <?php echo $task['completed'] ? '✓' : '○'; ?>
                    </span>
                    <span style="color: var(--text-primary); font-size: 13px; <?php echo $task['completed'] ? 'text-decoration: line-through; opacity: 0.6;' : ''; ?>">
                        <?php echo htmlspecialchars($task['task']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn--secondary btn--sm">Uredi</a>
            
            <?php if ($project['status'] === 'current' && $currentPhaseIndex !== false && $currentPhaseIndex < count($phaseOrder) - 1): ?>
            <a href="?advance_phase=1&id=<?php echo $project['id']; ?>" 
               class="btn btn--primary btn--sm <?php echo !$canAdvance ? 'btn--disabled' : ''; ?>" 
               style="<?php echo !$canAdvance ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
               onclick="<?php echo !$canAdvance ? 'alert(\'Molimo završite sve zadatke u trenutnoj fazi prije prelaska na sljedeću.\'); return false;' : 'return confirm(\'Jeste li sigurni da želite preći na sljedeću fazu?\');'; ?>">
                Pređi na sljedeću fazu →
            </a>
            <?php endif; ?>
            
            <a href="?status=<?php echo $project['status'] === 'current' ? 'past' : ($project['status'] === 'future' ? 'current' : 'current'); ?>&id=<?php echo $project['id']; ?>" class="btn btn--secondary btn--sm">
                <?php echo $project['status'] === 'current' ? 'Označi kao završen' : ($project['status'] === 'future' ? 'Započni' : 'Vrati u trenutne'); ?>
            </a>
            <a href="?delete=<?php echo $project['id']; ?>" class="btn btn--danger btn--sm" onclick="return confirm('Jeste li sigurni da želite obrisati ovaj projekt?')">Obriši</a>
        </div>
    </div>
</div>

<!-- Phase Checklist Modal -->
<div id="phase-checklist-modal-<?php echo $project['id']; ?>" class="phase-checklist-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;" onclick="if(event.target === this) closePhaseChecklist(<?php echo $project['id']; ?>)">
    <div style="background: var(--bg-dark); border-radius: 12px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; position: relative;" onclick="event.stopPropagation()">
        <button onclick="closePhaseChecklist(<?php echo $project['id']; ?>)" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: var(--text-secondary); font-size: 24px; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='var(--bg-input)'; this.style.color='var(--text-primary)'" onmouseout="this.style.background='transparent'; this.style.color='var(--text-secondary)'">×</button>
        <h3 style="margin: 0 0 20px 0; color: var(--text-primary);" id="phase-checklist-title-<?php echo $project['id']; ?>">Checklist</h3>
        <div id="phase-checklist-content-<?php echo $project['id']; ?>" style="color: var(--text-secondary);">
            Učitavanje...
        </div>
    </div>
</div>

<?php if (!defined('PHASE_CHECKLIST_SCRIPT_LOADED')): ?>
<?php define('PHASE_CHECKLIST_SCRIPT_LOADED', true); ?>
<script>
window.showPhaseChecklist = function(projectId, phaseName, phaseLabel) {
    console.log('showPhaseChecklist called', projectId, phaseName, phaseLabel);
    const modal = document.getElementById('phase-checklist-modal-' + projectId);
    const title = document.getElementById('phase-checklist-title-' + projectId);
    const content = document.getElementById('phase-checklist-content-' + projectId);
    
    if (!modal || !title || !content) {
        console.error('Modal elements not found', {modal, title, content});
        return;
    }
    
    title.textContent = 'Checklist: ' + phaseLabel;
    content.innerHTML = 'Učitavanje...';
    modal.style.display = 'flex';
    
    // Fetch checklist for this phase
    fetch('project-phase-checklist.php?project_id=' + projectId + '&phase=' + encodeURIComponent(phaseName))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                content.innerHTML = '<div style="color: var(--danger);">' + data.error + '</div>';
                return;
            }
            
            if (data.checklist && data.checklist.length > 0) {
                let html = '<div style="display: grid; gap: 12px;">';
                data.checklist.forEach(function(item) {
                    // Ensure completed is boolean
                    const isCompleted = item.completed === true || item.completed === 1 || item.completed === '1';
                    html += '<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-input); border-radius: 8px;">';
                    html += '<span style="color: ' + (isCompleted ? 'var(--success)' : 'var(--warning)') + '; font-size: 18px;">' + (isCompleted ? '✓' : '○') + '</span>';
                    html += '<span style="flex: 1; color: var(--text-primary); ' + (isCompleted ? 'text-decoration: line-through; opacity: 0.6;' : '') + '">' + escapeHtml(item.task) + '</span>';
                    if (!isCompleted) {
                        html += '<span style="font-size: 11px; color: var(--warning); background: rgba(251, 191, 36, 0.1); padding: 4px 8px; border-radius: 4px;">Za napraviti</span>';
                    }
                    if (isCompleted && item.completed_at) {
                        html += '<span style="font-size: 12px; color: var(--text-secondary);">' + new Date(item.completed_at).toLocaleDateString('hr-HR') + '</span>';
                    }
                    html += '</div>';
                });
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div style="color: var(--text-secondary); text-align: center; padding: 20px;">Nema zadataka za ovu fazu.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading checklist:', error);
            content.innerHTML = '<div style="color: var(--danger);">Greška pri učitavanju checkliste: ' + error.message + '</div>';
        });
};

window.closePhaseChecklist = function(projectId) {
    const modal = document.getElementById('phase-checklist-modal-' + projectId);
    if (modal) {
        modal.style.display = 'none';
    }
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('[id^="phase-checklist-modal-"]');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>
<?php endif; ?>


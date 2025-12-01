<?php
// Project Card Component
$phaseNames = [
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
$totalPhases = 6;
$currentPhaseIndex = array_search($currentPhase, ['planning', 'design', 'development', 'content', 'testing', 'final']);
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
            <span class="badge badge--<?php echo $project['status'] === 'current' ? 'success' : ($project['status'] === 'future' ? 'warning' : 'secondary'); ?>">
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
                $phases = ['planning', 'design', 'development', 'content', 'testing', 'final'];
                foreach ($phases as $index => $phase): 
                    $isCurrent = $phase === $currentPhase;
                    $isCompleted = isset($project['phases'][$phase]) && $project['phases'][$phase]['completed'];
                    $isPast = $currentPhaseIndex !== false && $index < $currentPhaseIndex;
                ?>
                <div style="
                    padding: 6px 12px; 
                    border-radius: 6px; 
                    font-size: 11px; 
                    font-weight: 500;
                    background: <?php echo $isCompleted ? 'var(--success)' : ($isCurrent ? 'var(--primary)' : 'var(--bg-input)'); ?>;
                    color: <?php echo $isCompleted || $isCurrent ? 'white' : 'var(--text-secondary)'; ?>;
                    border: 1px solid <?php echo $isCurrent ? 'var(--primary)' : 'transparent'; ?>;
                ">
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
                const targetDate = new Date('<?php echo $project['next_phase_start']; ?>').getTime();
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
        
        <!-- Actions -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn--secondary btn--sm">Uredi</a>
            <a href="?status=<?php echo $project['status'] === 'current' ? 'past' : ($project['status'] === 'future' ? 'current' : 'current'); ?>&id=<?php echo $project['id']; ?>" class="btn btn--secondary btn--sm">
                <?php echo $project['status'] === 'current' ? 'Označi kao završen' : ($project['status'] === 'future' ? 'Započni' : 'Vrati u trenutne'); ?>
            </a>
            <a href="?delete=<?php echo $project['id']; ?>" class="btn btn--danger btn--sm" onclick="return confirm('Jeste li sigurni da želite obrisati ovaj projekt?')">Obriši</a>
        </div>
    </div>
</div>


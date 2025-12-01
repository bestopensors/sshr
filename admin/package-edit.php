<?php
/**
 * Admin - Package Edit
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && dbAvailable()) {
    // Handle image upload
    $imagePath = $_POST['current_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../images/packages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'package_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'images/packages/' . $filename;
            }
        }
    }
    
    $data = [
        'slug' => sanitize($_POST['slug'] ?? ''),
        'title_hr' => sanitize($_POST['title_hr'] ?? ''),
        'title_en' => sanitize($_POST['title_en'] ?? ''),
        'description_hr' => sanitize($_POST['description_hr'] ?? ''),
        'description_en' => sanitize($_POST['description_en'] ?? ''),
        'eta_hr' => sanitize($_POST['eta_hr'] ?? ''),
        'eta_en' => sanitize($_POST['eta_en'] ?? ''),
        'price' => !empty($_POST['price']) ? (float)$_POST['price'] : null,
        'original_price' => !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null,
        'badge_hr' => sanitize($_POST['badge_hr'] ?? ''),
        'badge_en' => sanitize($_POST['badge_en'] ?? ''),
        'badge_type' => sanitize($_POST['badge_type'] ?? 'default'),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'show_discount' => isset($_POST['show_discount']) ? 1 : 0,
        'visit_url' => sanitize($_POST['visit_url'] ?? ''),
        'visit_url_2' => sanitize($_POST['visit_url_2'] ?? ''),
        'image' => $imagePath,
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'active' => isset($_POST['active']) ? 1 : 0
    ];
    
    try {
        if ($id > 0) {
            // Update existing package
            $sql = "UPDATE packages SET 
                slug = ?, title_hr = ?, title_en = ?, description_hr = ?, description_en = ?,
                eta_hr = ?, eta_en = ?, price = ?, original_price = ?, badge_hr = ?, badge_en = ?,
                badge_type = ?, is_featured = ?, show_discount = ?, visit_url = ?, visit_url_2 = ?,
                image = ?, sort_order = ?, active = ?, updated_at = NOW()
                WHERE id = ?";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                $data['slug'], $data['title_hr'], $data['title_en'], $data['description_hr'], $data['description_en'],
                $data['eta_hr'], $data['eta_en'], $data['price'], $data['original_price'], $data['badge_hr'], $data['badge_en'],
                $data['badge_type'], $data['is_featured'], $data['show_discount'], $data['visit_url'], $data['visit_url_2'],
                $data['image'], $data['sort_order'], $data['active'], $id
            ]);
        } else {
            // Insert new package
            $sql = "INSERT INTO packages (slug, title_hr, title_en, description_hr, description_en,
                eta_hr, eta_en, price, original_price, badge_hr, badge_en, badge_type, is_featured,
                show_discount, visit_url, visit_url_2, image, sort_order, active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                $data['slug'], $data['title_hr'], $data['title_en'], $data['description_hr'], $data['description_en'],
                $data['eta_hr'], $data['eta_en'], $data['price'], $data['original_price'], $data['badge_hr'], $data['badge_en'],
                $data['badge_type'], $data['is_featured'], $data['show_discount'], $data['visit_url'], $data['visit_url_2'],
                $data['image'], $data['sort_order'], $data['active']
            ]);
            $id = db()->lastInsertId();
        }
        
        // Handle features
        if (isset($_POST['features_hr']) && is_array($_POST['features_hr'])) {
            $stmt = db()->prepare("DELETE FROM package_features WHERE package_id = ?");
            $stmt->execute([$id]);
            
            $featuresHr = $_POST['features_hr'];
            $featuresEn = $_POST['features_en'] ?? [];
            
            for ($i = 0; $i < count($featuresHr); $i++) {
                if (!empty(trim($featuresHr[$i]))) {
                    $stmt = db()->prepare("INSERT INTO package_features (package_id, feature_hr, feature_en, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, trim($featuresHr[$i]), trim($featuresEn[$i] ?? ''), $i]);
                }
            }
        }
        
        // Handle details - always process if details_hr is set (even if empty array)
        if (isset($_POST['details_hr'])) {
            // Delete all existing details for this package
            $stmt = db()->prepare("DELETE FROM package_details WHERE package_id = ?");
            $stmt->execute([$id]);
            
            // Insert new details if provided
            if (is_array($_POST['details_hr'])) {
                $detailsHr = $_POST['details_hr'];
                $detailsEn = $_POST['details_en'] ?? [];
                $sortOrder = 0;
                
                for ($i = 0; $i < count($detailsHr); $i++) {
                    $detailHr = trim($detailsHr[$i] ?? '');
                    $detailEn = trim($detailsEn[$i] ?? '');
                    
                    // Only insert if at least one language has content
                    if (!empty($detailHr) || !empty($detailEn)) {
                        $stmt = db()->prepare("INSERT INTO package_details (package_id, detail_hr, detail_en, sort_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$id, $detailHr, $detailEn, $sortOrder]);
                        $sortOrder++;
                    }
                }
            }
        }
        
        header('Location: packages.php?success=' . ($id ? 'updated' : 'created'));
        exit;
        
    } catch (Exception $e) {
        $error = 'Greška: ' . $e->getMessage();
    }
}

$pageTitle = 'Uredi Paket';
require_once 'includes/header.php';

$message = '';
$error = $error ?? '';
$package = null;
$features = [];
$details = [];

// Check if database is available
if (!dbAvailable()) {
    $error = 'Baza podataka nije povezana.';
} else {
    // Load package data
    if ($id > 0) {
        try {
            $stmt = db()->prepare("SELECT * FROM packages WHERE id = ?");
            $stmt->execute([$id]);
            $package = $stmt->fetch();
            
            if ($package) {
                $stmt = db()->prepare("SELECT * FROM package_features WHERE package_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$id]);
                $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = db()->prepare("SELECT * FROM package_details WHERE package_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$id]);
                $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $error = 'Greška pri učitavanju paketa.';
        }
    }
}

// Default values for new package
if (!$package) {
    $package = [
        'slug' => '',
        'title_hr' => '',
        'title_en' => '',
        'description_hr' => '',
        'description_en' => '',
        'eta_hr' => '',
        'eta_en' => '',
        'price' => '',
        'original_price' => '',
        'badge_hr' => '',
        'badge_en' => '',
        'badge_type' => 'default',
        'is_featured' => 0,
        'show_discount' => 1,
        'visit_url' => '',
        'visit_url_2' => '',
        'image' => '',
        'sort_order' => 0,
        'active' => 1
    ];
}
?>

<?php if ($message): ?>
    <div class="alert alert--success"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert--error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($package['image'] ?? ''); ?>">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title"><?php echo $id ? 'Uredi paket' : 'Novi paket'; ?></h2>
            <a href="packages.php" class="btn btn--secondary btn--sm">← Natrag</a>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="title_hr">Naziv (HR) *</label>
                <input type="text" class="form-control" id="title_hr" name="title_hr" value="<?php echo htmlspecialchars($package['title_hr']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="title_en">Naziv (EN) *</label>
                <input type="text" class="form-control" id="title_en" name="title_en" value="<?php echo htmlspecialchars($package['title_en']); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="slug">Slug (URL-friendly naziv)</label>
            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($package['slug']); ?>" placeholder="npr. basic, professional">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="description_hr">Opis (HR)</label>
                <textarea class="form-control" id="description_hr" name="description_hr" rows="3"><?php echo htmlspecialchars($package['description_hr']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="description_en">Opis (EN)</label>
                <textarea class="form-control" id="description_en" name="description_en" rows="3"><?php echo htmlspecialchars($package['description_en']); ?></textarea>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="eta_hr">ETA (HR)</label>
                <input type="text" class="form-control" id="eta_hr" name="eta_hr" value="<?php echo htmlspecialchars($package['eta_hr']); ?>" placeholder="npr. ETA: 24-48 sati">
            </div>
            
            <div class="form-group">
                <label for="eta_en">ETA (EN)</label>
                <input type="text" class="form-control" id="eta_en" name="eta_en" value="<?php echo htmlspecialchars($package['eta_en']); ?>" placeholder="npr. ETA: 24-48 hours">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="price">Cijena (€)</label>
                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $package['price']; ?>" placeholder="Ostavite prazno za 'Po dogovoru'">
            </div>
            
            <div class="form-group">
                <label for="original_price">Originalna cijena (€)</label>
                <input type="number" step="0.01" class="form-control" id="original_price" name="original_price" value="<?php echo $package['original_price']; ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="badge_hr">Badge (HR)</label>
                <input type="text" class="form-control" id="badge_hr" name="badge_hr" value="<?php echo htmlspecialchars($package['badge_hr']); ?>" placeholder="npr. Preporučeno">
            </div>
            
            <div class="form-group">
                <label for="badge_en">Badge (EN)</label>
                <input type="text" class="form-control" id="badge_en" name="badge_en" value="<?php echo htmlspecialchars($package['badge_en']); ?>" placeholder="npr. Recommended">
            </div>
            
            <div class="form-group">
                <label for="badge_type">Tip badge-a</label>
                <select class="form-control" id="badge_type" name="badge_type">
                    <option value="default" <?php echo $package['badge_type'] === 'default' ? 'selected' : ''; ?>>Default</option>
                    <option value="featured" <?php echo $package['badge_type'] === 'featured' ? 'selected' : ''; ?>>Featured (Preporučeno)</option>
                    <option value="premium" <?php echo $package['badge_type'] === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    <option value="custom" <?php echo $package['badge_type'] === 'custom' ? 'selected' : ''; ?>>Custom</option>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="visit_url">Demo URL 1</label>
                <input type="url" class="form-control" id="visit_url" name="visit_url" value="<?php echo htmlspecialchars($package['visit_url']); ?>" placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label for="visit_url_2">Demo URL 2</label>
                <input type="url" class="form-control" id="visit_url_2" name="visit_url_2" value="<?php echo htmlspecialchars($package['visit_url_2']); ?>" placeholder="https://...">
            </div>
        </div>
        
        <div class="form-group">
            <label for="image">Slika paketa</label>
            <?php if (!empty($package['image'])): ?>
            <div style="margin-bottom: 10px;">
                <img src="../<?php echo htmlspecialchars($package['image']); ?>" alt="Trenutna slika" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
            </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            <small style="color: #666;">Ostavite prazno za zadržavanje trenutne slike</small>
        </div>
        
        <div class="form-group">
            <label for="sort_order">Redoslijed</label>
            <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo $package['sort_order']; ?>" style="width: 100px;">
        </div>
        
        <div style="display: flex; gap: 20px; margin-top: 10px;">
            <label class="checkbox-label">
                <input type="checkbox" name="active" <?php echo $package['active'] ? 'checked' : ''; ?>>
                <span>Aktivan</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="is_featured" <?php echo $package['is_featured'] ? 'checked' : ''; ?>>
                <span>Istaknuti (Featured)</span>
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="show_discount" <?php echo $package['show_discount'] ? 'checked' : ''; ?>>
                <span>Prikaži popust</span>
            </label>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Značajke paketa (checklist)</h2>
            <button type="button" class="btn btn--secondary btn--sm" onclick="addFeature()">+ Dodaj značajku</button>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">Ove značajke se prikazuju kao checklist na kartici paketa (✓)</p>
        
        <div id="features-container">
            <?php if (!empty($features)): ?>
                <?php foreach ($features as $i => $feature): ?>
                <div class="feature-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="features_hr[]" value="<?php echo htmlspecialchars($feature['feature_hr']); ?>" placeholder="Značajka (HR)">
                    <input type="text" class="form-control" name="features_en[]" value="<?php echo htmlspecialchars($feature['feature_en']); ?>" placeholder="Feature (EN)">
                    <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="feature-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="features_hr[]" placeholder="Značajka (HR)">
                    <input type="text" class="form-control" name="features_en[]" placeholder="Feature (EN)">
                    <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Dodatni detalji</h2>
            <button type="button" class="btn btn--secondary btn--sm" onclick="addDetail()">+ Dodaj detalj</button>
        </div>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 16px;">Ovi detalji se prikazuju kada korisnik klikne "Detalji" na kartici paketa</p>
        
        <div id="details-container">
            <?php if (!empty($details)): ?>
                <?php foreach ($details as $i => $detail): ?>
                <div class="detail-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="details_hr[]" value="<?php echo htmlspecialchars($detail['detail_hr']); ?>" placeholder="Detalj (HR)">
                    <input type="text" class="form-control" name="details_en[]" value="<?php echo htmlspecialchars($detail['detail_en']); ?>" placeholder="Detail (EN)">
                    <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="detail-row" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;">
                    <input type="text" class="form-control" name="details_hr[]" placeholder="Detalj (HR)">
                    <input type="text" class="form-control" name="details_en[]" placeholder="Detail (EN)">
                    <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn--primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Spremi paket
        </button>
        <a href="packages.php" class="btn btn--secondary">Odustani</a>
    </div>
</form>

<script>
function addFeature() {
    const container = document.getElementById('features-container');
    const row = document.createElement('div');
    row.className = 'feature-row';
    row.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;';
    row.innerHTML = `
        <input type="text" class="form-control" name="features_hr[]" placeholder="Značajka (HR)">
        <input type="text" class="form-control" name="features_en[]" placeholder="Feature (EN)">
        <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(row);
}

function addDetail() {
    const container = document.getElementById('details-container');
    const row = document.createElement('div');
    row.className = 'detail-row';
    row.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;';
    row.innerHTML = `
        <input type="text" class="form-control" name="details_hr[]" placeholder="Detalj (HR)">
        <input type="text" class="form-control" name="details_en[]" placeholder="Detail (EN)">
        <button type="button" class="btn btn--danger btn--sm" onclick="this.parentElement.remove()">×</button>
    `;
    container.appendChild(row);
}
</script>

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: var(--text-secondary);
}
.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>

<?php require_once 'includes/footer.php'; ?>

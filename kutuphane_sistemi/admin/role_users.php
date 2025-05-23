<?php
require_once '../config.php';
require_once 'includes/permissions.php';

if (!isset($_SESSION['admin_id'])) {
    exit('Unauthorized');
}

$permission->requirePermission('staff', 'read');

$role_id = $_GET['role_id'] ?? 0;

$users = $pdo->prepare("
    SELECT k.*, r.rol_adi 
    FROM kullanicilar k 
    JOIN roller r ON k.rol_id = r.id 
    WHERE k.rol_id = ? AND k.kullanici_tipi = 'admin'
    ORDER BY k.ad_soyad
");
$users->execute([$role_id]);
$user_list = $users->fetchAll();

if ($user_list): ?>
    <div class="list-group">
        <?php foreach ($user_list as $user): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($user['ad_soyad']) ?></strong>
                <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
            </div>
            <div>
                <?php if ($user['durum'] == 'aktif'): ?>
                    <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Pasif</span>
                <?php endif; ?>
                
                <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                    <span class="badge bg-info">Siz</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center text-muted py-3">
        <div style="font-size: 3rem;">👤</div>
        <p>Bu role sahip kullanıcı bulunmuyor.</p>
    </div>
<?php endif; ?>
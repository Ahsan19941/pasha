<?php 
// File: app/views/admin/dashboard.php
// Admin dashboard view for the PASHA Benefits Portal

// Set page title
$pageTitle = 'Admin Dashboard';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <div>
        <span class="text-muted">Today is <?= date('l, F j, Y') ?></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Members</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $memberStats['active'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= url('/admin/members?status=active') ?>" class="text-primary text-decoration-none small">
                    View Details <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Offers</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $offerStats['active'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-gift fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= url('/admin/offers?status=active') ?>" class="text-success text-decoration-none small">
                    View Details <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Partners</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $partnerStats['active'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-handshake fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= url('/admin/partners?status=active') ?>" class="text-info text-decoration-none small">
                    View Details <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Expiring Memberships</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $memberStats['expiring_soon'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-2">
                <a href="<?= url('/admin/reports/members?status=active') ?>" class="text-warning text-decoration-none small">
                    View Details <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">Recent Activity</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted">No recent activity found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td>
                                            <?= e($activity['first_name'] . ' ' . $activity['last_name']) ?>
                                            <div class="small text-muted"><?= e($activity['role']) ?></div>
                                        </td>
                                        <td><?= e($activity['action']) ?></td>
                                        <td>
                                            <?php if ($activity['entity_type']): ?>
                                                <?= e($activity['entity_type']) ?> #<?= e($activity['entity_id']) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatDate($activity['created_at'], 'd M Y, H:i') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('/admin/logs') ?>" class="text-decoration-none">View All Activity</a>
            </div>
        </div>
    </div>
    
    <!-- Expiring Memberships -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-white">
                <h6 class="m-0 font-weight-bold">Expiring Memberships</h6>
            </div>
            <div class="card-body">
                <?php if (empty($expiringMemberships)): ?>
                    <p class="text-muted">No expiring memberships in the next 30 days.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($expiringMemberships as $member): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= e($member['company_name']) ?></h6>
                                        <small class="text-muted">ID: <?= e($member['membership_id']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="badge bg-warning"><?= formatDate($member['expiry_date']) ?></div>
                                        <div class="small text-muted">
                                            <?php 
                                            $daysLeft = floor((strtotime($member['expiry_date']) - time()) / (60 * 60 * 24));
                                            echo $daysLeft > 0 ? $daysLeft . ' days left' : 'Expires today';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('/admin/members') ?>" class="text-decoration-none">View All Members</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Offer Categories -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h6 class="m-0 font-weight-bold">Offer Categories</h6>
            </div>
            <div class="card-body">
                <?php if (empty($offerStats['by_category'])): ?>
                    <p class="text-muted">No offer categories found.</p>
                <?php else: ?>
                    <canvas id="categoryChart" style="width: 100%; height: 250px;"></canvas>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('/admin/offers') ?>" class="text-decoration-none">View All Offers</a>
            </div>
        </div>
    </div>
    
    <!-- Top Partners -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white">
                <h6 class="m-0 font-weight-bold">Top Partners</h6>
            </div>
            <div class="card-body">
                <?php if (empty($partnerStats['top_partners'])): ?>
                    <p class="text-muted">No partners with offers found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Partner</th>
                                    <th>Offers</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partnerStats['top_partners'] as $index => $partner): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= e($partner['name']) ?></td>
                                        <td>
                                            <span class="badge bg-success"><?= $partner['offer_count'] ?> offers</span>
                                        </td>
                                        <td>
                                            <?php if (isset($partner['status'])): ?>
                                                <span class="badge bg-<?= $partner['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst(e($partner['status'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= url('/admin/partners/edit/' . $partner['id']) ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="<?= url('/admin/partners') ?>" class="text-decoration-none">View All Partners</a>
            </div>
        </div>
    </div>
</div>

<!-- Extra CSS -->
<?php $extraCss = '<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
</style>'; ?>

<!-- Extra JS -->
<?php $extraJs = '<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Category Chart
    const categoryChartElement = document.getElementById("categoryChart");
    if (categoryChartElement) {
        const categoryChart = new Chart(categoryChartElement, {
            type: "doughnut",
            data: {
                labels: [' . implode(', ', array_map(function($category) {
                    return '"' . addslashes($category) . '"';
                }, array_keys($offerStats['by_category']))) . '],
                datasets: [{
                    data: [' . implode(', ', array_values($offerStats['by_category'])) . '],
                    backgroundColor: [
                        "#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b",
                        "#5a5c69", "#6f42c1", "#20c9a6", "#2c9faf", "#f8f9fc"
                    ],
                    hoverBackgroundColor: [
                        "#2e59d9", "#17a673", "#2c9faf", "#dda20a", "#be2617",
                        "#3a3b45", "#4e2c94", "#13795f", "#226f7b", "#d8d9e7"
                    ],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            boxWidth: 12
                        }
                    }
                },
                cutout: "60%",
            },
        });
    }
});
</script>'; ?>

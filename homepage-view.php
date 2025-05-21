<?php 
// File: app/views/home/index.php
// Homepage view for the PASHA Benefits Portal

// Set page title
$pageTitle = 'Home';
?>

<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-3">
                    <?= getSetting('site_name', APP_NAME) ?>
                </h1>
                <p class="lead mb-4">
                    Exclusive benefits and special offers for PASHA members. Access discounts and promotions from our partner network, exclusively available to active members.
                </p>
                <div class="d-flex gap-3">
                    <a href="<?= url('/offers') ?>" class="btn btn-light btn-lg px-4">
                        View Benefits
                    </a>
                    <a href="<?= url('/verify') ?>" class="btn btn-outline-light btn-lg px-4">
                        Verify Membership
                    </a>
                </div>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0 text-center">
                <img src="<?= asset('images/benefits-illustration.svg') ?>" alt="Benefits Illustration" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Featured Benefits -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="mb-0">Featured Benefits</h2>
                <p class="text-muted">Exclusive offers for PASHA members</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= url('/offers') ?>" class="btn btn-outline-primary">
                    View All Benefits <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
        
        <div class="row">
            <?php if (empty($offers)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No benefits available at this time. Please check back later.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($offers as $index => $offer): ?>
                    <?php if ($index < 6): // Show only 6 offers on homepage ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <?php if ($offer['image_url']): ?>
                                    <img src="<?= e($offer['image_url']) ?>" class="card-img-top" alt="<?= e($offer['title']) ?>" style="height: 180px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light text-center py-5">
                                        <i class="fas fa-gift fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?= e($offer['title']) ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-tag me-1"></i> <?= e($offer['category']) ?>
                                    </p>
                                    <p class="card-text">
                                        <?= truncate(e($offer['description']), 100) ?>
                                    </p>
                                </div>
                                
                                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <img src="<?= e($offer['partner_logo'] ?: asset('images/placeholder-logo.png')) ?>" alt="<?= e($offer['partner_name']) ?>" class="me-2" style="height: 24px;">
                                        <small class="text-muted"><?= e($offer['partner_name']) ?></small>
                                    </div>
                                    <a href="<?= url('/offers/' . $offer['id']) ?>" class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Benefits Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="mb-4 text-center">Benefit Categories</h2>
        
        <div class="row">
            <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No categories available at this time.
                    </div>
                </div>
            <?php else: ?>
                <?php 
                // Category icons mapping 
                $categoryIcons = [
                    'Technology' => 'laptop',
                    'Software' => 'code',
                    'Hardware' => 'microchip',
                    'Training' => 'graduation-cap',
                    'Education' => 'book',
                    'Workspace' => 'building',
                    'Food' => 'utensils',
                    'Health' => 'heartbeat',
                    'Travel' => 'plane',
                    'Finance' => 'money-bill',
                    'Insurance' => 'shield-alt',
                    'Entertainment' => 'film'
                ];
                
                foreach ($categories as $category): 
                    // Get icon or use default
                    $icon = isset($categoryIcons[$category]) ? $categoryIcons[$category] : 'tag';
                ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="<?= url('/offers/category/' . urlencode($category)) ?>" class="text-decoration-none">
                            <div class="card text-center h-100 shadow-sm hover-card">
                                <div class="card-body py-4">
                                    <i class="fas fa-<?= $icon ?> fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title"><?= e($category) ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Membership Verification -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2>Verify Your Membership</h2>
                <p class="lead">Partners can quickly verify if a company is a PASHA member in good standing.</p>
                <p>Enter a membership ID or company name to verify membership status. This helps partners ensure benefits are only provided to eligible members.</p>
                <a href="<?= url('/verify') ?>" class="btn btn-primary">
                    Verify Now <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-3">Quick Verification</h4>
                        <form action="<?= url('/verify') ?>" method="post">
                            <?php csrfField(); ?>
                            
                            <div class="mb-3">
                                <label for="verification_type" class="form-label">Verification Method</label>
                                <select class="form-select" id="verification_type" name="verification_type" required>
                                    <option value="id">Membership ID</option>
                                    <option value="company_name">Company Name</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="verification_value" class="form-label">Enter Value</label>
                                <input type="text" class="form-control" id="verification_value" name="verification_value" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Verify</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Extra CSS -->
<?php $extraCss = '<style>
.hover-card {
    transition: all 0.3s ease;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}
</style>'; ?>

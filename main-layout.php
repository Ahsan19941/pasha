<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="<?= url('/') ?>">
                    <?php if (getSetting('logo_url')): ?>
                        <img src="<?= getSetting('logo_url') ?>" alt="<?= getSetting('site_name', APP_NAME) ?>" height="40">
                    <?php else: ?>
                        <?= getSetting('site_name', APP_NAME) ?>
                    <?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $_SERVER['REQUEST_URI'] == '/' ? 'active' : '' ?>" href="<?= url('/') ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/offers') === 0 ? 'active' : '' ?>" href="<?= url('/offers') ?>">Benefits</a>
                        </li>
                        <?php if (getSetting('enable_member_verification', '1') == '1'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/verify') === 0 ? 'active' : '' ?>" href="<?= url('/verify') ?>">Verify Membership</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $_SERVER['REQUEST_URI'] == '/about' ? 'active' : '' ?>" href="<?= url('/about') ?>">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $_SERVER['REQUEST_URI'] == '/contact' ? 'active' : '' ?>" href="<?= url('/contact') ?>">Contact</a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAccount" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['user_name'] ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownAccount">
                                    <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff'): ?>
                                        <li><a class="dropdown-item" href="<?= url('/admin') ?>">Admin Dashboard</a></li>
                                    <?php elseif ($_SESSION['user_role'] === 'partner'): ?>
                                        <li><a class="dropdown-item" href="<?= url('/partner') ?>">Partner Dashboard</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= url('/logout') ?>">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link btn btn-outline-light btn-sm ms-2" href="<?= url('/login') ?>">Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Flash Messages -->
    <?php $flashMessages = getFlashMessages(); ?>
    <?php if (!empty($flashMessages)): ?>
        <div class="container mt-3">
            <?php foreach ($flashMessages as $message): ?>
                <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $message['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="py-4">
        <?= $content ?>
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>About PASHA</h5>
                    <p class="text-muted">
                        <?= getSetting('site_description', 'The Pakistan Software Houses Association (PASHA) Benefits Portal provides exclusive offers and discounts to our members.') ?>
                    </p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= url('/') ?>" class="text-muted">Home</a></li>
                        <li><a href="<?= url('/offers') ?>" class="text-muted">Benefits</a></li>
                        <li><a href="<?= url('/verify') ?>" class="text-muted">Verify Membership</a></li>
                        <li><a href="<?= url('/about') ?>" class="text-muted">About</a></li>
                        <li><a href="<?= url('/contact') ?>" class="text-muted">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact</h5>
                    <address class="text-muted">
                        <?= nl2br(e(getSetting('address', 'PASHA, Karachi, Pakistan'))) ?><br>
                        <i class="fas fa-envelope me-2"></i> <?= getSetting('contact_email', 'info@pasha.org.pk') ?><br>
                        <i class="fas fa-phone me-2"></i> <?= getSetting('contact_phone', '+92 21 3123456') ?>
                    </address>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= getSetting('site_name', APP_NAME) ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-muted"><i class="fab fa-facebook-f"></i></a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted"><i class="fab fa-twitter"></i></a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted"><i class="fab fa-linkedin-in"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (some components might need it) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= asset('js/main.js') ?>"></script>
    
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>

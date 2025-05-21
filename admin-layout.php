<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Admin - ' . APP_NAME : 'Admin - ' . APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link href="<?= asset('css/admin.css') ?>" rel="stylesheet">
    
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4">
                <?php if (getSetting('logo_url')): ?>
                    <img src="<?= getSetting('logo_url') ?>" alt="<?= getSetting('site_name', APP_NAME) ?>" height="40" class="mb-2">
                <?php endif; ?>
                <div class="d-block"><?= getSetting('site_name', APP_NAME) ?></div>
                <div class="small text-muted">Administration</div>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= url('/admin') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= $_SERVER['REQUEST_URI'] == '/admin' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="<?= url('/admin/members') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/members') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i> Members
                </a>
                <a href="<?= url('/admin/offers') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/offers') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-gift me-2"></i> Offers
                </a>
                <a href="<?= url('/admin/partners') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/partners') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-handshake me-2"></i> Partners
                </a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?= url('/admin/users') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/users') === 0 ? 'active' : '' ?>">
                        <i class="fas fa-user-shield me-2"></i> Users
                    </a>
                <?php endif; ?>
                <a href="<?= url('/admin/reports') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports') === 0 ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="<?= url('/admin/logs') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/logs') === 0 ? 'active' : '' ?>">
                        <i class="fas fa-history me-2"></i> Activity Logs
                    </a>
                    <a href="<?= url('/admin/settings') ?>" class="list-group-item list-group-item-action bg-transparent text-white <?= strpos($_SERVER['REQUEST_URI'], '/admin/settings') === 0 ? 'active' : '' ?>">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                <?php endif; ?>
                <a href="<?= url('/') ?>" class="list-group-item list-group-item-action bg-transparent text-white">
                    <i class="fas fa-home me-2"></i> Public Site
                </a>
                <a href="<?= url('/logout') ?>" class="list-group-item list-group-item-action bg-transparent text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-sm btn-primary" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto d-flex">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['user_name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?= url('/logout') ?>">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Flash Messages -->
            <?php $flashMessages = getFlashMessages(); ?>
            <?php if (!empty($flashMessages)): ?>
                <div class="container-fluid mt-3">
                    <?php foreach ($flashMessages as $message): ?>
                        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                            <?= $message['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="container-fluid p-4">
                <?= $content ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="<?= asset('js/admin.js') ?>"></script>
    
    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    </script>
    
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>

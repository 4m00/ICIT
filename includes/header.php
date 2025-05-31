<header class="main-header">
    <div class="header-logo">
        <a href="dashboard.php"><?= SITE_NAME ?></a>
    </div>
    
    <div class="header-nav">
        <?php if (isLoggedIn()): ?>
            <div class="user-dropdown">
                <button class="dropdown-btn">
                    <?= htmlspecialchars($_SESSION['user_name']); ?>
                    <span class="arrow-down"></span>
                </button>
                <div class="dropdown-content">
                    <a href="profile.php">Profile</a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <nav>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$userStmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">MUN Chair</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>" 
                       href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'discussed_amendments' ? 'active' : ''; ?>" 
                       href="discussed_amendments.php">
                        <i class="fas fa-history me-2"></i>Discussed Amendments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'amendments' ? 'active' : ''; ?>" 
                       href="amendments.php">
                        <i class="fas fa-file-alt"></i> Amendments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'purposed_resolution' ? 'active' : ''; ?>" 
                       href="purposed_resolution.php">
                        <i class="fas fa-scroll"></i> Resolutions
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link">
                        <i class="fas fa-user-tie"></i> 
                        <?php echo htmlspecialchars($userData['firstname'] . ' ' . $userData['lastname']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

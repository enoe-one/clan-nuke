<nav class="bg-gradient-to-r from-red-900 to-blue-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <i class="fas fa-shield-alt text-3xl"></i>
                <span class="text-2xl font-bold">CFWT</span>
            </div>
            
            <button class="md:hidden" id="mobile-menu-btn">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <div class="hidden md:flex space-x-6" id="desktop-menu">
                <a href="<?php echo SITE_URL; ?>/index.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/recruitment.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-user-plus"></i>
                    <span>Recrutement</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/grades.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-award"></i>
                    <span>Grades</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/diplomes.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Diplômes</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/legions.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-shield-alt"></i>
                    <span>Légions</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/members.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-users"></i>
                    <span>Membres</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/game.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                    <i class="fas fa-gamepad"></i>
                    <span>Mini-Jeu</span>
                </a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                            <i class="fas fa-cogs"></i>
                            <span>Admin</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/member/dashboard.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                            <i class="fas fa-user"></i>
                            <span>Mon Profil</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/member_login.php" class="flex items-center space-x-2 hover:text-red-300 transition">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Connexion</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="md:hidden pb-4 space-y-2 hidden" id="mobile-menu">
            <a href="<?php echo SITE_URL; ?>/index.php" class="block py-2 hover:bg-red-800 px-2 rounded">Accueil</a>
            <a href="<?php echo SITE_URL; ?>/recruitment.php" class="block py-2 hover:bg-red-800 px-2 rounded">Recrutement</a>
            <a href="<?php echo SITE_URL; ?>/grades.php" class="block py-2 hover:bg-red-800 px-2 rounded">Grades</a>
            <a href="<?php echo SITE_URL; ?>/diplomes.php" class="block py-2 hover:bg-red-800 px-2 rounded">Diplômes</a>
            <a href="<?php echo SITE_URL; ?>/legions.php" class="block py-2 hover:bg-red-800 px-2 rounded">Légions</a>
            <a href="<?php echo SITE_URL; ?>/members.php" class="block py-2 hover:bg-red-800 px-2 rounded">Membres</a>
            <a href="<?php echo SITE_URL; ?>/game.php" class="block py-2 hover:bg-red-800 px-2 rounded">Mini-Jeu</a>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="block py-2 hover:bg-red-800 px-2 rounded">Admin</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/member/dashboard.php" class="block py-2 hover:bg-red-800 px-2 rounded">Mon Profil</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/member_login.php" class="block py-2 hover:bg-red-800 px-2 rounded">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>
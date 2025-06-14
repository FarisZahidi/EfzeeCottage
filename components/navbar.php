<?php
if (!isset($_SESSION)) {
    session_start();
}
?>

<nav class="navbar" style=" background-color:rgb(19 90 116 / 90%)">
    <div class="nav-brand">
        <img src="image/logo.png" alt="EFZEE COTTAGE" class="nav-logo" style="width: 15%; height: 15%">
    </div>
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>
    <div class="nav-links">
        <a href="homepage.php">Home</a>
        <a href="homepage.php#about">About Us</a>
        <a href="homepage.php#gallery">Gallery</a>
        <a href="homepage.php#booking">Book Now</a>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="mybooking.php" class="active">My Bookings</a>
        <?php endif; ?>
        <!-- <a href="#reviews">Reviews</a> -->
        <div class="nav-user-menu">
            <?php if (!isset($_SESSION['user'])): ?>
                <button id="loginBtn" class="nav-button">Login / Sign Up</button>
            <?php else: ?>
                <form id="logoutForm" action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="nav-button" style="background-color: #3498db;">Logout</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- Active Section Indicator -->
<div class="active-section">
    <a href="#home" class="active" data-section="Home"></a>
    <a href="#about" data-section="About"></a>
    <a href="#gallery" data-section="Gallery"></a>
    <a href="#booking" data-section="Booking"></a>
</div>
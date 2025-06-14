<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="tabs">
            <button class="tab-btn active" data-tab="login">Login</button>
            <button class="tab-btn" data-tab="signup">Sign Up</button>
        </div>
        <!-- Login Form -->
        <form id="loginForm" class="auth-form" method="POST" action="">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="action" value="login">
            <button type="submit">Login</button>
        </form>

        <!-- Sign Up Form -->
        <form id="signupForm" class="auth-form" style="display: none;" method="POST" action="">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Create Password" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="hidden" name="action" value="signup">
            <button type="submit">Create Account</button>
            <p class="text-center">By signing up, you agree to our <a href="#"
                    style="color: var(--secondary-color);">Terms of Service</a></p>
        </form>

    </div>
</div>
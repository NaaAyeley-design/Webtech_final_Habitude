<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../assets/css/1logincss.css">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
</head>
<body>
<div class="login-container">
        <h1 class="main-header">Habitude</h1>
        <h2>Log In</h2>
        <div id="error-message" class="error-message" style="display: none;"></div>
        <form id="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group" style="position: relative;">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="password-toggle" aria-label="Show password">👁️</button>
            </div>
            <button type="submit" class="login-btn">Log In</button>
        </form>
        <div class="signup-prompt">
            Don't have an account?<a href="1signinpage.php" class="signup-link">Sign up</a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('login-form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const submitButton = document.querySelector('.login-btn');
            const errorMessageDiv = document.getElementById('error-message');
            const passwordToggle = document.querySelector('.password-toggle');

            // Toggle password visibility
            passwordToggle.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordToggle.textContent = type === 'password' ? '👁️' : '🙈';
            });

            // Handle login form submission
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // Reset error message
                errorMessageDiv.style.display = 'none';
                errorMessageDiv.textContent = '';
                submitButton.disabled = true;
                submitButton.textContent = 'Logging in...';
                submitButton.classList.add('loading');

                // Validate inputs
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();

                if (!email || !email.includes('@')) {
                    displayError('Please enter a valid email address.');
                    return resetSubmitButton();
                }

                if (!password) {
                    displayError('Password cannot be empty.');
                    return resetSubmitButton();
                }

                try {
                    const response = await fetch('./html/login_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password }),
                    });

                    const data = await response.json();

                    if (data.success) {
            // Save user details to local storage
            localStorage.setItem('user_id', data.user.user_id);
            localStorage.setItem('email', data.user.email);
            localStorage.setItem('first_name', data.user.first_name);
            localStorage.setItem('last_name', data.user.last_name);
            localStorage.setItem('role_id', data.user.role_id);

            // Redirect to the appropriate dashboard
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                console.error('Redirect URL not provided.');
                displayError('Unable to determine dashboard. Please contact support.');
            }
        } else {
            // Handle errors returned from the server
            const errorMessage = data.errors?.form || 'Unexpected error occurred.';
            console.error('Login error:', errorMessage);
            displayError(errorMessage);
        }

                } catch (error) {
                    console.error('Login error:', error.message);
                    displayError(error.message);
                } finally {
                    resetSubmitButton();
                }
            });

            // Display error message
            function displayError(message) {
                errorMessageDiv.style.display = 'block';
                errorMessageDiv.textContent = message;
            }

            // Reset submit button
            function resetSubmitButton() {
                submitButton.disabled = false;
                submitButton.textContent = 'Log In';
                submitButton.classList.remove('loading');
            }
        });
    </script>
</body>
</html>

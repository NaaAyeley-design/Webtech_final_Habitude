<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/1signupcss.css">
    <title>Habitude - Sign Up</title>
    
</head>

<body>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="particle-background"></div>
    <div class="signup-container">
        <div class="signup-logo">Habitude</div>

        <form class="signup-form" id="signupForm">
            <input type="text" name="first_name" placeholder="First Name" class="form-input" required>
            <input type="text" name="last_name" placeholder="Last Name" class="form-input" required>
            <input type="email" name="email" placeholder="Email" class="form-input" required>
            <input type="password" name="password" placeholder="Password" class="form-input" required>
            <div id="errorContainer"></div>
            <button type="submit" class="signup-btn">Create Account</button>
        </form>

        <div class="divider">
            <div class="divider-line"></div>
            <span class="divider-text">or</span>
            <div class="divider-line"></div>
        </div>

        <div class="login-link">
            Already have an account? <a href="1loginpage.php">Log In</a>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Account Created Successfully!</h2>
            <p>Your account has been created. You can now log in.</p>
            <button class="modal-close" onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        const form = document.getElementById('signupForm');
        const errorContainer = document.getElementById('errorContainer');
        const successModal = document.getElementById('successModal');

        function closeModal() {
            successModal.style.display = 'none';
            window.location.href = '1loginpage.php'; // Redirect to login page
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorContainer.innerHTML = ''; // Clear previous error messages

            const formData = {
                email: form.email.value,
                password: form.password.value,
                first_name: form.first_name.value,
                last_name: form.last_name.value
            };

            try {
                const response = await fetch('../../actions/register_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                console.log('Server response:', data);

                if (data.success) {
                    successModal.style.display = 'flex'; // Show success modal
                    form.reset(); // Optionally reset the form
                } else {
                    const errorMessages = Object.values(data.errors)
                        .map(error => `<div class="error-message">${error}</div>`)
                        .join('');
                    errorContainer.innerHTML = errorMessages;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                errorContainer.innerHTML = '<div class="error-message">Network error. Please try again.</div>';
            }
        });

        // Close modal when clicking outside the modal content
        window.onclick = function (event) {
            if (event.target === successModal) {
                closeModal();
            }
        };
    </script>
</body>

</html>

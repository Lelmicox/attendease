<?php
include __DIR__ . "/includes/header.php";
?>

    <section class="contact">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We’d love to hear from you! Whether you’re a student, lecturer, or administrator, feel free to reach out
                with questions, feedback, or collaboration ideas.</p>

            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form">
                    <form>
                        <label for="name">Full Name</label>
                        <input type="text" id="name" placeholder="Enter your name" required>

                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="Enter your email" required>

                        <label for="message">Message</label>
                        <textarea id="message" rows="5" placeholder="Write your message..." required></textarea>

                        <button type="submit" class="btn">Send Message</button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <p><i class="fa-solid fa-envelope"></i> support@attendease.com</p>
                    <p><i class="fa-solid fa-phone"></i> +234 814 973 3351</p>
                    <p><i class="fa-solid fa-location-dot"></i> Enugu State University, Nigeria</p>
                    <p><i class="fa-solid fa-clock"></i> Mon - Fri: 9am - 5pm</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2026 AttendEase | Designed for University Attendance</p>
    </footer>

<script src="./assets/js/main.js"></script>
</body>

</html>
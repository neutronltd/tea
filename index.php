<?php
// Start a session to handle the success message
session_start();

// Include the database connection file
require_once "tea-admin/db.php";

$success_message = "";
$error_message = "";

// Check if a success message is set in the session (after a redirect)
if (isset($_SESSION['form_submitted_successfully']) && $_SESSION['form_submitted_successfully'] === true) {
    $success_message = "Your information has been submitted successfully. We'll be in touch soon.";
    // Unset the session variable so the message doesn't reappear on refresh
    unset($_SESSION['form_submitted_successfully']);
}

// Process form data when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $name = trim($_POST['name']);
    $company = trim($_POST['company']);
    $title = trim($_POST['title']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    // Check if the newsletter checkbox is checked
    $newsletter_subscribed = isset($_POST['newsletter']) ? 1 : 0; // 1 for true, 0 for false

    // Basic validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and Email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Prepare an insert statement
        $sql = "INSERT INTO members (name, company, title, phone, address, email, newsletter_subscribed) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssssssi", $name, $company, $title, $phone, $address, $email, $newsletter_subscribed);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Set a session flag for success and redirect to the same page
                // This prevents form resubmission on page refresh
                $_SESSION['form_submitted_successfully'] = true;
                header("Location: " . $_SERVER['PHP_SELF'] . "#form-section");
                exit();
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    // Close connection
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Texas Elevator Association</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('background-full.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            position: relative;
        }

        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 9999;
            transition: opacity 0.75s ease;
        }

        #progress-bar-container {
            width: 80%;
            max-width: 400px;
            height: 30px;
            border: 2px solid #2c3e50;
            border-radius: 5px;
            overflow: hidden;
        }

        #progress-bar {
            height: 100%;
            width: 0;
            background: repeating-linear-gradient(
                45deg,
                #c0392b,
                #c0392b 15px,
                #ffffff 15px,
                #ffffff 30px,
                #34568B 30px,
                #34568B 45px
            );
            transition: width 0.2s ease-out;
        }

        #loader-percentage {
            margin-top: 1rem;
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            color: #2c3e50;
        }

        .container {
            max-width: 800px;
            width: 90%;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            max-width: 220px;
            margin-bottom: 2rem;
        }

        .title-image {
            max-width: 700px;
            width: 100%;
            margin-bottom: 3rem;
        }

        .content-paragraph {
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 700px;
            margin: 3rem auto;
        }

        .call-to-action {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 2rem;
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            margin-bottom: 2rem;
        }

        .board-members {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 2rem 1rem;
            max-width: 750px;
            margin: 0 auto 4rem;
            text-align: center;
        }

        .member {
            padding: 1rem;
        }
        
        .member.placeholder {
            visibility: hidden;
        }

        .member-name {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #c0392b;
            margin: 0;
            text-transform: uppercase;
        }

        .member-title, .member-company {
            font-family: 'Lato', sans-serif;
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 700;
            margin-top: 0.25rem;
            line-height: 1.3;
        }
        
        .form-section {
            margin-top: 4rem;
            padding-top: 2.5rem;
            border-top: 1px solid #cccccc;
        }

        .join-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            text-align: left;
            max-width: 700px;
            margin: 0 auto;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            font-family: 'Lato', sans-serif;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.7);
            color: #2c3e50;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .checkbox-group {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
        }

        .checkbox-group input {
            margin-right: 0.75rem;
            width: 18px;
            height: 18px;
        }
        
        .checkbox-group label {
            font-size: 1rem;
            font-weight: 700;
        }
        
        .submit-btn {
            grid-column: 1 / -1;
            background-color: #c0392b;
            color: white;
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 1rem;
        }
        
        .submit-btn:hover {
            background-color: #a53125;
        }
        
        .thank-you-container {
            background-color: rgba(212, 237, 218, 0.95);
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 2rem 3rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem auto;
        }
        .thank-you-container h2 {
            font-family: 'Oswald', sans-serif;
            color: #155724;
            font-size: 2.5rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .thank-you-container p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #155724;
        }
        .thank-you-container .checkmark-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        .submit-another-btn {
            display: inline-block;
            margin-top: 2rem;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            font-family: 'Oswald', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .submit-another-btn:hover {
            background-color: #218838;
        }

        .error-message {
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem auto;
            font-weight: bold;
            text-align: center;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            text-align: left;
            margin-top: 6rem;
            padding: 2rem 0;
            position: relative;
        }
        
        .footer-left, .footer-right {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            line-height: 1.4;
            font-size: 1.2rem;
        }

        .footer-left p, .footer-right p {
            margin: 0;
        }

        .footer-left a {
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
            word-break: break-all;
            font-weight: 700;
        }

        .footer-right {
            text-align: right;
        }
        
        .footer-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 250px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            pointer-events: none;
            z-index: -1;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 1rem;
                margin: 2rem auto;
            }
            .call-to-action {
                font-size: 1.2rem;
            }
            .board-members, .join-form {
                grid-template-columns: 1fr 1fr;
            }
            .footer {
                margin-top: 4rem;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 2rem;
            }
            .footer-right {
                text-align: center;
            }
        }
        @media (max-width: 600px) {
             .join-form, .board-members {
                grid-template-columns: 1fr;
            }
            .member.placeholder {
                display: none;
            }
        }

    </style>
</head>
<body>

    <div id="loader-wrapper">
        <div id="progress-bar-container">
            <div id="progress-bar"></div>
        </div>
        <div id="loader-percentage">0%</div>
    </div>

    <div class="container">
        <img src="logo.png" alt="Texas Elevator Association Logo" class="logo" onerror="this.style.display='none'">
        <img src="tea-title.png" alt="Texas Elevator Association Title" class="title-image" onerror="this.style.display='none'">

        <p class="content-paragraph">
            We're proud to introduce the Texas Elevator Association (TEA), created by and for elevator professionals across the state. TEA unites, supports, and advocates for those who keep Texas moving, vertically. Whether you're a technician, inspector, contractor, consultant, or supplier, this is your platform for connection, education, and progress. Become a founding member to help shape our future and access valuable resources, networking, and events. Let's rise together and take the Texas elevator industry to new heights.
        </p>

        <p class="call-to-action">
            Join us today and be part of something built to last!
        </p>

        <h2 class="section-title">Founding Board Members</h2>
        <div class="board-members">
            <div class="member">
                <p class="member-name">Bruce Barbre</p>
                <p class="member-title">President</p>
                <p class="member-company">VDA</p>
            </div>
            <div class="member">
                <p class="member-name">Dan Baltzegar</p>
                <p class="member-title">Treasurer</p>
                <p class="member-company">Vantage</p>
            </div>
            <div class="member">
                <p class="member-name">Rich Fitzgerald</p>
                <p class="member-title">Board Member</p>
                <p class="member-company">SEES, Inc.</p>
            </div>
            <div class="member">
                <p class="member-name">Jason Laney</p>
                <p class="member-title">Vice President</p>
                <p class="member-company">Smartrise Engineering</p>
            </div>
            <div class="member">
                <p class="member-name">Ricky Peters</p>
                <p class="member-title">Secretary</p>
                <p class="member-company">Metro Elevator</p>
            </div>
            <div class="member">
                <p class="member-name">Brent Stark</p>
                <p class="member-title">Board Member</p>
                <p class="member-company">Integrity Elevator Solutions</p>
            </div>
            <div class="member placeholder"></div>
            <div class="member">
                <p class="member-name">Sam Patel</p>
                <p class="member-title">Board Member</p>
                <p class="member-company">Southwest Elevator Company</p>
            </div>
            <div class="member placeholder"></div>
        </div>

        <section id="form-section" class="form-section">
            <?php if (!empty($success_message)): ?>
                <div class="thank-you-container">
                    <svg class="checkmark-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle cx="26" cy="26" r="25" fill="none" stroke="#155724" stroke-width="2"/>
                        <path fill="none" stroke="#155724" stroke-width="3" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                    <h2>Thank You!</h2>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                    <a href="index.php" class="submit-another-btn">Submit Another Member</a>
                </div>
            <?php else: ?>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>#form-section" method="POST" class="join-form">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="company">Company:</label>
                        <input type="text" id="company" name="company">
                    </div>
                    <div class="form-group">
                        <label for="title">Title:</label>
                        <input type="text" id="title" name="title">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone number:</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group full-width">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address"></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="email">Email address:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter" value="yes" checked>
                        <label for="newsletter">Receive email newsletter and association information</label>
                    </div>
                    <button type="submit" class="submit-btn">Join the movement</button>
                </form>
            <?php endif; ?>
        </section>

        <footer class="footer">
            <div class="footer-left">
                <p>EMAIL US TO JOIN THE<br>NEWSLETTER AND MEMBER LIST</p>
                <a href="mailto:jason@texaselevatorassociation.org">jason@texaselevatorassociation.org</a>
            </div>
            <div class="footer-right">
                <p>PENDING<br>FINAL<br>APPROVAL</p>
            </div>
        </footer>

    </div>
    
    <div class="footer-overlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const progressBar = document.getElementById('progress-bar');
            const percentageText = document.getElementById('loader-percentage');
            const loaderWrapper = document.getElementById('loader-wrapper');

            const imageSources = [
                'background-full.jpg',
                'logo.png',
                'tea-title.png'
            ];

            let imagesLoaded = 0;
            const totalImages = imageSources.length;

            if (totalImages === 0) {
                hideLoader();
                return;
            }

            const updateLoader = () => {
                imagesLoaded++;
                const percent = Math.floor((imagesLoaded / totalImages) * 100);
                progressBar.style.width = percent + '%';
                percentageText.innerText = percent + '%';

                if (imagesLoaded === totalImages) {
                    setTimeout(() => {
                        hideLoader();
                    }, 250);
                }
            };

            const hideLoader = () => {
                loaderWrapper.style.opacity = '0';
                loaderWrapper.addEventListener('transitionend', () => {
                    loaderWrapper.style.display = 'none';
                }, { once: true });
            };

            imageSources.forEach(src => {
                const img = new Image();
                img.src = src;
                img.onload = updateLoader;
                img.onerror = updateLoader;
            });
        });
    </script>

</body>
</html>

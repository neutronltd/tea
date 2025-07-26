<?php
// Start a new session or resume the existing one
session_start();

// If the admin is already logged in, redirect them to the dashboard
if(isset($_SESSION["admin_logged_in"]) && $_SESSION["admin_logged_in"] === true){
    header("location: index.php");
    exit;
}

// Include the database connection script
require_once "db.php";

// Define variables and initialize with empty values
$email = $password = "";
$error_message = "";

// Process form data when the form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Get email and password from the form
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Prepare a select statement
    $sql = "SELECT id, email, password_hash FROM admins WHERE email = ?";

    if($stmt = $conn->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("s", $param_email);
        
        // Set parameters
        $param_email = $email;
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Store result
            $stmt->store_result();
            
            // Check if email exists, if yes then verify password
            if($stmt->num_rows == 1){                    
                // Bind result variables
                $stmt->bind_result($id, $email, $hashed_password);
                if($stmt->fetch()){
                    if(password_verify($password, $hashed_password)){
                        // Password is correct, so start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["admin_logged_in"] = true;
                        $_SESSION["admin_id"] = $id;
                        $_SESSION["admin_email"] = $email;                            
                        
                        // Redirect user to dashboard page
                        header("location: index.php");
                    } else{
                        // Display an error message if password is not valid
                        $error_message = "Invalid email or password.";
                    }
                }
            } else{
                // Display an error message if email doesn't exist
                $error_message = "Invalid email or password.";
            }
        } else{
            $error_message = "Oops! Something went wrong. Please try again later.";
        }

        // Close statement
        $stmt->close();
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
    <title>TEA Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('../background-full.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2rem 3rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .logo {
            max-width: 180px;
            margin-bottom: 1.5rem;
        }
        .login-container h2 {
            font-family: 'Oswald', sans-serif;
            margin-bottom: 2rem;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        .login-btn {
            background-color: #c0392b;
            color: white;
            font-family: 'Oswald', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .login-btn:hover {
            background-color: #a53125;
        }
        .error-message {
            color: #c0392b;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <img src="../logo.png" alt="Texas Elevator Association Logo" class="logo">
        <h2>Admin Panel Login</h2>

        <?php 
        if(!empty($error_message)){
            echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" class="login-btn" value="Login">
            </div>
        </form>
    </div>

</body>
</html>

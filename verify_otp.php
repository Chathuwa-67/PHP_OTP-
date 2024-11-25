<?php
session_start(); // Ensure session is started
 

// Include PHPMailer files
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Function to send OTP using PHPMailer
function sendOtp($email, $otp) {
    $mail = new PHPMailer(true);  // Passing `true` enables exceptions
    try {
        // Server settings
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';                         // Set the SMTP server to Gmail
        $mail->SMTPAuth = true;                                // Enable SMTP authentication
        $mail->Username = 'gihangunathilakavck@gmail.com'; // Your Gmail address
        $mail->Password = 'wjttymochrbhnljq';            // Your App Password              // SMTP password (your Gmail app password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;     // Enable TLS encryption
        $mail->Port = 587;                                     // TCP port to connect to

        // Recipients
        $mail->setFrom('gihangunathilakavck@gmail.com', 'Your Name');  // Sender's email address
        $mail->addAddress($email);                            // Recipient's email address

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP code is: <b>$otp</b>";  // OTP message body

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Check if OTP and email are set in session
if (!isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
    echo "Session expired or OTP not generated.";
    exit;
}

// Resend OTP functionality
if (isset($_GET['resend'])) {
    // Generate a new OTP
    $newOtp = rand(100000, 999999); // 6-digit OTP
    $_SESSION['otp'] = $newOtp;

    // Send new OTP to the email
    sendOtp($_SESSION['email'], $newOtp);

    // Redirect back to the OTP page with a message
    header("Location: verify_otp.php?otp_sent=true");
    exit;
}



// Process OTP verification
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['otp']) && is_array($_POST['otp'])) {
        // Combine OTP inputs into a single string and trim spaces
        $enteredOtp = implode('', array_map('trim', $_POST['otp']));
        $storedOtp = trim($_SESSION['otp']); // Ensure there are no spaces in the stored OTP

        // Compare the entered OTP with the stored OTP
        if ($enteredOtp === $storedOtp) {
            // OTP verified, redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $errorMessage = "Invalid OTP. Please try again.";
        }
    } else {
        $errorMessage = "Invalid OTP input.";
    }
}

// Resend OTP functionality
if (isset($_GET['resend'])) {
    // Generate a new OTP
    $newOtp = rand(100000, 999999); // 6-digit OTP
    $_SESSION['otp'] = $newOtp;

    // Send new OTP to the email
    sendOtp($_SESSION['email'], $newOtp);

    // Redirect back to the OTP page with a message
    header("Location: verify_otp.php?otp_sent=true");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Reggae+One&display=swap" rel="stylesheet">
    <style>
         
     
        body {
            font-family: "Reggae one";
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            text-align: center;
            padding: 30px;
            border-radius: 8px;
            background: #1e1e1e;
            width: 100%;
            max-width: 550px;
            height: 350px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
        }

        h2 {
            margin-bottom: 20px;
            color:red;
        }

        .otp-boxes {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .otp-boxes input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5em;
            background-color: #333;
            border: 2px solid red;
            border-radius: 8px;
            color: #fff;
            transition: all 0.3s ease;
        }

        .otp-boxes input:focus {
            outline: none;
            border: 2px solid rgb(131, 9, 9);
            background: linear-gradient(145deg, #0f0c0c, #d21c1c);
        }

        .otp-boxes input:not(:last-child) {
            margin-right: 10px;
        }

        .error-message {
            color: #ff5555;
            font-size: 1.1em;
            margin-top: 10px;
        }

        .timer {
            margin-top: 10px;
            font-size: 0.9em;
            color: #ff5555;
        }

        button {
            padding: 10px 20px;
            font-size: 1em;
            font-family: "Reggae one";
            color: #fff;
            background: linear-gradient(145deg, #ff3b3b, #e60000);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #010101;
            color:red;
        }

        button:disabled {
            background: #444;
            cursor: not-allowed;
        }

        .resend-code {
            margin-top: 10px;
            font-size: 0.9em;
            color: #ccc;
            cursor: pointer;
        }

        .resend-code:hover {
            color: #ff3b3b;
        }

        .success-message{
            color:red;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.otp-boxes input');
            const timerElement = document.getElementById('timer');
            const formButton = document.querySelector('button');
            const resendCodeLink = document.querySelector('.resend-code');
            let timeLeft = 120; // 2 minutes in seconds

            // Focus handling for OTP inputs
            inputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    if (input.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });

            // Countdown timer
            const timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    formButton.disabled = true;
                    timerElement.textContent = "Time expired. Please request a new OTP.";
                } else {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerElement.textContent = `  ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    timeLeft--;
                }
            }, 1000);

            // Resend OTP link functionality
            resendCodeLink.addEventListener('click', () => {
                window.location.href = 'verify_otp.php?resend=true'; // Triggers resend OTP process
            });
        });
        
    </script>
</head>
<body>
    <div class="container">
        <h2>OTP Verification</h2>
        <p>Enter the OTP you received at <strong><?php echo $_SESSION['email']; ?></strong></p>

        <?php if (isset($_GET['otp_sent'])): ?>
            <div class="success-message">A new OTP has been sent to your email.</div>
        <?php endif; ?>

        <form action="verify_otp.php" method="post">
            <div class="otp-boxes">
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
                <input type="text" name="otp[]" maxlength="1" required>
            </div>
            <button type="submit">Verify OTP</button>
        </form>
        <p class="resend-code">Didn't receive the code? <span>Resend Code <div class="timer" id="timer">2:00</div></span></p>
        
        <?php if ($errorMessage): ?>
            <div class="error-message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>

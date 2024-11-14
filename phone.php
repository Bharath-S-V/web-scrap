<?php
// Include Composer's autoload file
require_once 'vendor/autoload.php';

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the phone number from form input
    $phoneNumber = htmlspecialchars($_POST['phone_number']);

    // Initialize the phone number utility
    $phoneUtil = PhoneNumberUtil::getInstance();

    try {
        // Parse the phone number (input should be in international format)
        $number = $phoneUtil->parse($phoneNumber, null);

        // Check if the number is valid
        if ($phoneUtil->isValidNumber($number)) {
            // Extract country code, region, and type
            $countryCode = $number->getCountryCode();
            $regionCode = $phoneUtil->getRegionCodeForNumber($number);
            $type = $phoneUtil->getNumberType($number);

            // Formatting the number in international format
            $formattedNumber = $phoneUtil->format($number, PhoneNumberFormat::INTERNATIONAL);

            // Output the parsed and validated information
            $message = "Phone Number Info:<br>";
            $message .= "Formatted Number: " . $formattedNumber . "<br>";
            $message .= "Country Code: " . $countryCode . "<br>";
            $message .= "Region: " . $regionCode . "<br>";
            $message .= "Phone Type: " . ($type == PhoneNumberType::MOBILE ? 'Mobile' : 'Fixed Line') . "<br>";
        } else {
            $errorMessage = "Invalid phone number.";
        }
    } catch (Exception $e) {
        $errorMessage = "Error parsing phone number: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Number Location Finder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 50px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        .form-group button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .result {
            margin-top: 20px;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Phone Number Location Finder</h1>

        <!-- Form to input phone number -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="phone_number">Enter Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" placeholder="Enter phone number" required>
            </div>
            <div class="form-group">
                <button type="submit">Find Location</button>
            </div>
        </form>

        <!-- Display result or error message -->
        <div class="result">
            <?php
            if (isset($errorMessage)) {
                echo "<div class='error'>$errorMessage</div>";
            }

            if (isset($message)) {
                echo $message;
            }
            ?>
        </div>
    </div>

</body>

</html>
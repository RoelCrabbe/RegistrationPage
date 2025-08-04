<?php
// Include config file containing DB and SOAP credentials (gitignored for security)
require_once "config.php";

// Initialize variables for user inputs and error messages
$username = $password = $password_verify = "";
$username_err = $password_err = $password_verify_err = "";
$command = "account create {USERNAME} {PASSWORD}";
$result = "";

// Initialize SOAP client using credentials and connection info from config.php
$client = new SoapClient(
    null,
    array(
        "location" => "http://" . SOAP_HOST . ":" . SOAP_PORT, // SOAP server URL with host and port
        "uri"      => "urn:MaNGOS",                           // SOAP namespace URI
        "style"    => SOAP_RPC,                               // Use RPC style for SOAP calls
        'login'    => SOAP_REGNAME,                           // SOAP username from config
        'password' => SOAP_REGPASS                            // SOAP password from config
    )
);

// Process form data on POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username input
    $username_trimmed = trim($_POST["username"]);
    if (empty($username_trimmed)) {
        $username_err = "Required"; // Username cannot be empty
    } elseif (!ctype_alnum($username_trimmed)) {
        $username_err = "Must be letters and numbers only"; // Only alphanumeric allowed
    } elseif (strlen($username_trimmed) > 16) {
        $username_err = "Too long"; // Max 16 characters
    } elseif (strlen($username_trimmed) < 4) {
        $username_err = "Too short"; // Min 4 characters
    } else {
        // Check if username already exists in database
        $sql = "SELECT id FROM account WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = htmlspecialchars($username_trimmed);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "Taken"; // Username already exists
                } else {
                    $username = htmlspecialchars($username_trimmed);
                }
            } else {
                echo "Error executing query";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password input
    $password_trimmed = trim($_POST["password"]);
    if (empty($password_trimmed)) {
        $password_err = "Required"; // Password cannot be empty
    } elseif (strlen($password_trimmed) > 16) {
        $password_err = "Too long"; // Max 16 characters
    } elseif (strlen($password_trimmed) < 6) {
        $password_err = "Too short"; // Min 6 characters
    } else {
        $password = $password_trimmed;
        $password_hashed = password_hash($password, PASSWORD_DEFAULT); // Hash password securely
    }

    // Validate password confirmation (verify)
    $password_verify_trimmed = trim($_POST["password_verify"]);
    if (empty($password_verify_trimmed)) {
        $password_verify_err = "Required"; // Confirmation required
    } elseif ($password_trimmed !== $password_verify_trimmed) {
        $password_verify_err = "Mismatch"; // Passwords do not match
    } else {
        $password_verify = $password_verify_trimmed;
    }

    // If no errors, proceed to create account via SOAP command
    if (empty($username_err) && empty($password_err) && empty($password_verify_err)) {
        $command = str_replace('{USERNAME}', strtoupper($_POST["username"]), $command);
        $command = str_replace('{PASSWORD}', strtoupper($_POST["password"]), $command);

        try {
            // Execute SOAP command to create account
            $result = $client->executeCommand(new SoapParam($command, "command"));
        } catch (Exception $e) {
            echo "SOAP Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <link rel="stylesheet" href="style.css">
  <script>
    function copyRealmlist() {
        const text = document.getElementById("realmlist").innerText;
        navigator.clipboard.writeText(text).then(() => {
            const hint = document.querySelector(".copy-hint");
            hint.textContent = "[Copied]";
            setTimeout(() => {
            hint.textContent = "[Copy]";
            }, 1500);
        });
    }
  </script>
</head>
<body>
  <div class="register-wrapper">
    <h2>Sign Up</h2>
    <p>Please fill this form to create an account.</p>
    <div class="copy-container" onclick="copyRealmlist()" title="Click to copy to clipboard">
        <code id="realmlist">set realmlist moronbox.duckdns.org</code>
        <span class="copy-hint">[Copy]</span>
    </div>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="off">
      <table width="100%">
        <col style="width:25%">
        <col style="width:50%">
        <col style="width:25%">
        <tr>
          <td><label>Username:</label></td>
          <td>
            <input 
              type="text" 
              name="username" 
              autocomplete="username" 
              class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
              value="<?php echo htmlspecialchars($username); ?>">
          </td>
          <td><span class="invalid-feedback"><?php echo $username_err; ?></span></td>
        </tr>      
        <tr>
          <td><label>Password:</label></td>
          <td>
            <input 
              type="password" 
              name="password" 
              autocomplete="new-password" 
              class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
              value="">
          </td>
          <td><span class="invalid-feedback"><?php echo $password_err; ?></span></td>
        </tr>    
        <tr>
          <td><label>Verify:</label></td>
          <td>
            <input 
              type="password" 
              name="password_verify" 
              autocomplete="new-password" 
              class="form-control <?php echo (!empty($password_verify_err)) ? 'is-invalid' : ''; ?>" 
              value="">
          </td>
          <td><span class="invalid-feedback"><?php echo $password_verify_err; ?></span></td>
        </tr>           
      </table>
      <div class="form-group">
        <input type="submit" class="clicker" value="Submit">
        <div><span class="invalid-feedback"><?php echo $result; ?></span></div>
      </div>
    </form>
  </div>    
</body>
</html>

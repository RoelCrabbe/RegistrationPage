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
  <title>Join the Realm - Sign Up</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="overlay"></div>
  <div class="register-wrapper">
    <div class="header">
      <h1 class="title">Join the Realm</h1>
      <p class="subtitle">Create your account and begin your adventure</p>
    </div>

    <div class="copy-container" onclick="copyRealmlist()" title="Click to copy realmlist command">
      <div class="copy-header">
        <span class="copy-label">Realmlist Command</span>
        <span class="copy-hint" id="copyHint">Click to Copy</span>
      </div>
      <code class="copy-code" id="realmlist">set realmlist moronbox.duckdns.org</code>
    </div>

    <form class="form-container" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="off">
      <div class="form-group">
        <input
          type="text"
          name="username"
          class="form-input <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
          placeholder="Choose your username"
          autocomplete="username"
          value="<?php echo htmlspecialchars($username ?? ''); ?>">
        <?php if (!empty($username_err)): ?>
          <span class="invalid-feedback"><?php echo $username_err; ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <input
          type="password"
          name="password"
          class="form-input <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
          placeholder="Create a strong password"
          autocomplete="new-password">
        <?php if (!empty($password_err)): ?>
          <span class="invalid-feedback"><?php echo $password_err; ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <input
          type="password"
          name="password_verify"
          class="form-input <?php echo (!empty($password_verify_err)) ? 'is-invalid' : ''; ?>"
          placeholder="Confirm your password"
          autocomplete="new-password">
        <?php if (!empty($password_verify_err)): ?>
          <span class="invalid-feedback"><?php echo $password_verify_err; ?></span>
        <?php endif; ?>
      </div>

      <button type="submit" class="submit-btn">
        Create Account
      </button>

      <?php if (!empty($result) && strpos($result, 'created') !== false): ?>
        <div class="success-message">
          🎉 <?php echo $result; ?>
        </div>
      <?php elseif (!empty($result)): ?>
        <span class="invalid-feedback"><?php echo $result; ?></span>
      <?php endif; ?>
    </form>
  </div>

  <script>
    function copyRealmlist() {
      const text = document.getElementById("realmlist").textContent;
      const hint = document.getElementById("copyHint");
      
      navigator.clipboard.writeText(text).then(() => {
        hint.textContent = "Copied!";
        hint.style.background = "rgba(34, 197, 94, 0.2)";
        hint.style.color = "#4ade80";
        
        setTimeout(() => {
          hint.textContent = "Click to Copy";
          hint.style.background = "rgba(97, 166, 194, 0.1)";
          hint.style.color = "#8b949e";
        }, 2000);
      }).catch(() => {
        hint.textContent = "Failed to copy";
        hint.style.color = "#f87171";
        
        setTimeout(() => {
          hint.textContent = "Click to Copy";
          hint.style.color = "#8b949e";
        }, 2000);
      });
    }
  </script>
</body>
</html>
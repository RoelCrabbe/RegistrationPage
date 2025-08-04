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
    if (empty(trim($_POST["username"]))) {
        $username_err = "Required"; // Username cannot be empty
    } elseif (!ctype_alnum($_POST["username"])) {
        $username_err = "Must be letters and numbers only"; // Only alphanumeric allowed
    } elseif (strlen(trim($_POST["username"])) > 16) {
        $username_err = "Too long"; // Max 16 characters
    } elseif (strlen(trim($_POST["username"])) < 4) {
        $username_err = "Too short"; // Min 4 characters
    } else {
        // Check if username already exists in database
        $sql = "SELECT id FROM account WHERE username = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = htmlspecialchars(trim($_POST["username"]));

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "Taken"; // Username already exists
                } else {
                    $username = htmlspecialchars(trim($_POST["username"]));
                }
            } else {
                echo "Error executing query";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password input
    if (empty(trim($_POST["password"]))) {
        $password_err = "Required"; // Password cannot be empty
    } elseif (strlen(trim($_POST["password"])) > 16) {
        $password_err = "Too long"; // Max 16 characters
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Too short"; // Min 6 characters
    } else {
        $password = trim($_POST["password"]);
        $password_hashed = password_hash($password, PASSWORD_DEFAULT); // Hash password securely
    }

    // Validate password confirmation (verify)
    if (empty(trim($_POST["password_verify"]))) {
        $password_verify_err = "Required"; // Confirmation required
    } elseif ($_POST["password"] !== $_POST["password_verify"]) {
        $password_verify_err = "Mismatch"; // Passwords do not match
    } else {
        $password_verify = trim($_POST["password_verify"]);
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
  <!-- Overlay to soften background -->
  <div class="overlay"></div>
  
  <!-- Animated background particles -->
  <div class="particles" id="particles"></div>

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
    // Copy realmlist functionality
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

    // Create floating particles
    function createParticles() {
      const particles = document.getElementById('particles');
      const particleCount = 50;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 8 + 's';
        particle.style.animationDuration = (8 + Math.random() * 4) + 's';
        particles.appendChild(particle);
      }
    }

    // Initialize particles on load
    document.addEventListener('DOMContentLoaded', createParticles);

    // Add subtle hover effects to form inputs
    document.querySelectorAll('.form-input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-2px)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
      });
    });
  </script>
</body>
</html>
<?php
session_start();

if (isset($_POST['login']))
{
    // Database connection
    $username = "myinventory";
    $password = "mypassword123";
    $connectionString = "(DESCRIPTION =
        (ADDRESS_LIST =
            (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
        )
        (CONNECT_DATA =
            (SERVICE_NAME = FREEPDB1)
        )
    )";

    $conn = oci_connect($username, $password, $connectionString);

    if (!$conn) {
        $e = oci_error();
        die("Connection failed: " . $e['message']);
    }

    // Retrieve the username and password from the form submission
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];


    // Query the database to check if the credentials are valid
    $sql = "SELECT EMP_ID, EMP_NAME, GROUP_ID FROM EMPLOYEE
            WHERE USERNAME = :username AND PASSWORD = :password";

    // Check if a user was found
    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ":username", $inputUsername);
    oci_bind_by_name($stmt, ":password", $inputPassword);

    oci_execute($stmt);

    $row = oci_fetch_assoc($stmt);

    if ($row) {
        // User found, store session
        $_SESSION['emp_id'] = $row['EMP_ID'];
        $_SESSION['emp_name'] = $row['EMP_NAME'];
        $_SESSION['group_id'] = $row['GROUP_ID'];

        header("Location: home.php");
        exit();
    } else {
        echo "Invalid username or password.";
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>

<?php
if (isset($_POST['register']))
{
    // Database connection
    $username = "myinventory";
    $password = "mypassword123";
    $connectionString = "(DESCRIPTION =
        (ADDRESS_LIST =
            (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
        )
        (CONNECT_DATA =
            (SERVICE_NAME = FREEPDB1)
        )
    )";

    // Connect to Oracle
    $conn = oci_connect($username, $password, $connectionString);

    // Check if the connection was successful
    if (!$conn) {
        $e = oci_error();
        die("Connection failed: " . $e['message']);
    }

    // Retrieve the username and password from the form submission
    $fullName = $_POST['regFname'];
    $userName = $_POST['regUname'];
    $userPassword = $_POST['regPassword'];


    // Prepare the INSERT query
    $sql = "INSERT INTO EMPLOYEE (EMP_ID, EMP_NAME, USERNAME, PASSWORD, GROUP_ID)
            VALUES (EMPLOYEE_SEQ.NEXTVAL, :name, :username, :password, 2)";

    $stmt = oci_parse($conn, $sql);

    // Bind variables
    oci_bind_by_name($stmt, ":name", $fullName);
    oci_bind_by_name($stmt, ":username", $userName);
    oci_bind_by_name($stmt, ":password", $userPassword);

    // Execute
    $result = oci_execute($stmt);

    if ($result) {
        echo "Registration successful! You can now log in.";
    } else {
        $e = oci_error($stmt);
        echo "Registration failed: " . $e['message'];
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="layout/redo.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@300&display=swap" rel="stylesheet">
    <title>Login Page</title>
</head>
<body>
    <div class="body-container">
    <div class="container" id="container">
        <div>
        <div class="form-container sign-up-container">
            <form action="#login.php" method="post">
                <h1>Create Account</h1>
                <input name="regFname" type="text" placeholder="Full Name" required>
                <input name="regUname" type="text" placeholder="Username" required>
                <input name="regPassword" type="password" placeholder="Password" required>
                <input type="submit" class="btn-grad" value="Register" name="register">
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form action="#login.php" method="post">
                <h1>Sign In</h1>
                <input name="username" type="text" placeholder="Username" required>
                <input name="password" type="password" placeholder="Password" required>
                <input type="submit" class="btn-grad" value="Login" name="login">
            </form>
        </div>
        </div>

        <div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>
                        Already have an account?
                    </p>
                    <div class="btn-grad" id="signIn">Login</div>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello!</h1>
                    <p>Time for a new account?</p>
                    <p>Let's welcome you into City Bakery's Inventory System!</p>
                    <div class="btn-grad" id="signUp">Register</div>
                </div>
            </div>
        </div>
        </div>
    </div>

    </div>
    <script>
        
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpBtn.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        signInBtn.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });

    </script>
</body>
</html>
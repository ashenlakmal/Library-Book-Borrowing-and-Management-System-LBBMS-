<?php require_once("connect.php"); ?>

<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['initial_name'];
    $enrollment = $_POST['enrollment'];
    $email = $_POST['email'];
    $faculty = $_POST['faculty'];
    $department = $_POST['department'];
    $age = $_POST['age'];
    $address = $_POST['address'];
    $phone1 = $_POST['phone1'];
    $phone2 = $_POST['phone2'];
    $phone3 = $_POST['phone3'];
    $gender = $_POST['gender'];
    $password = $_POST['password'];
    $cpassword = $_POST['confirmPassword'];

    // Check if enrollment exists
    $checkenrollment = "SELECT * FROM student WHERE Enrollment = '$enrollment';";
    $check = mysqli_query($connect, $checkenrollment);
    $fetcharray = mysqli_fetch_assoc($check);

    if ($fetcharray == NULL) {
        $counter = 0;

        if (strlen($password) >= 8) {
            $counter += 1;

            for ($i = 0; $i < strlen($password); $i++) {
                if ($password[$i] >= 'A' && $password[$i] <= 'Z') {
                    $counter += 1;
                    break;
                }
            }

            for ($i = 0; $i < strlen($password); $i++) {
                if ($password[$i] >= 'a' && $password[$i] <= 'z') {
                    $counter += 1;
                    break;
                }
            }

            for ($i = 0; $i < strlen($password); $i++) {
                if (
                    $password[$i] === '!' || $password[$i] === '@' || $password[$i] === '#' || $password[$i] === '$' ||
                    $password[$i] === '%' || $password[$i] === '^' || $password[$i] === '&' || $password[$i] === '*' ||
                    $password[$i] === '(' || $password[$i] === ')' || $password[$i] === '?' || $password[$i] === '>' ||
                    $password[$i] === '<' || $password[$i] === ':' || $password[$i] === ';' || $password[$i] === '[' ||
                    $password[$i] === ']' || $password[$i] === '{' || $password[$i] === '}' || $password[$i] === '-' ||
                    $password[$i] === '+' || $password[$i] === '=' || $password[$i] === '~' || $password[$i] === '`' ||
                    $password[$i] === '/' || $password[$i] === '.' || $password[$i] === ',' || $password[$i] === '|'
                ) {
                    $counter += 1;
                    break;
                }
            }
        }

        if ($password === $cpassword) {
            if (strlen($password) >= 8) {
                if ($counter === 4) {
                    
                    // Hash password
                    $hashed = password_hash($password, PASSWORD_DEFAULT);

                    // Insert into student table
                    $insert = "INSERT INTO student 
                        (Enrollment, name, email, faculty, department, age, address, gender, password) 
                        VALUES 
                        ('$enrollment', '$name', '$email', '$faculty', '$department', '$age', '$address', '$gender', '$hashed')";

                    $q1 = mysqli_query($connect, $insert);

                    if ($q1) {
                        // Insert phone numbers
                        $phones = [$phone1, $phone2, $phone3];
                        foreach ($phones as $phone) {
                            if (!empty($phone)) {
                                $phone = mysqli_real_escape_string($connect, $phone);
                                $insertPhone = "INSERT INTO student_phone (Enrollment, phone) VALUES ('$enrollment', '$phone')";
                                mysqli_query($connect, $insertPhone);
                            }
                        }

                        echo '<script>alert("Successfully Signed Up !"); window.location.href="../login.html";</script>';
                    } else {
                        echo "<script>alert('Insert failed: " . mysqli_error($connect) . "');window.history.back();</script>";
                    }

                } else {
                    echo "<script>alert('Password must have special characters, uppercase and lowercase letters.');window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Password must be 8 or more characters.');window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Two passwords do not match.');window.history.back();</script>";
        }

    } else {
        echo '<script>alert("Enrollment number already exists. Try another!");window.history.back();</script>';
    }
}
?>

<?php mysqli_close($connect); ?>

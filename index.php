<?php
session_start();
include_once "classes/Aes.php";
include_once "classes/Page.php";
include_once "classes/Pdo_.php";
Page::display_header("Main page");

if (isset($_SESSION['CREATED']) && (time() - $_SESSION['CREATED'] > $timeout)) {
    echo '<script>alert("sesja wygasla zaloguj sie ponownie")</script>';
    session_unset();
    session_destroy();
} else {
    $_SESSION['CREATED'] = time();
}

$Pdo = new Pdo_();
if (isset($_SESSION['logged']))
    $Pdo->get_privileges($_SESSION['logged']);

if (isset($_SESSION['logged'])) {
    echo "</br>Zalogowany w sesji jako:" . $_SESSION['logged'];
} else {
    echo "</br>Wylogowany";
}
// adding new user
if (isset($_REQUEST['add_user'])) {
    $login = $_REQUEST['login'];
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];
    $password2 = $_REQUEST['password2'];
    $auth = $_REQUEST['auth'];
    if ($password == $password2) {
        $Pdo->add_user($login, $email, $password, $auth);
    } else {
        echo 'Passwords doesn\'t match';
    }
}


//edycja
if (isset($_REQUEST['edit_user'])) {
    $login = $_REQUEST['login'];
    $password = $_REQUEST['password'];
    $logged = $_SESSION['logged'];
    $Pdo->edit_user($login, $password, $logged);
}
//logout
if (isset($_REQUEST['logout'])) {
    $Pdo->logout();
    echo '<script>alert("Wylogowano pomyslnie")</script>';
}

//Logowanie 2f - krok 1
if (isset($_REQUEST['log_user_in'])) {
    $password = $_REQUEST['password'];
    $login = $_REQUEST['login'];
    if (isset($_SESSION['logged']) && $login == $_SESSION['logged']) {
        echo "</br>Jestes juz zalogowany!";
    } else {
        $result = $Pdo->log_2F_step1($login, $password);
        if ($result['result'] == 'success') {
            echo "</br>Success: " . $login . "</br>";
            $_SESSION['login'] = $login;
//$_SESSION['logged']='After first step';
            ?>
            <hr>
            <P> Please check your email account
                and type here the code you have been mailed.</P>
            <form method="post" action="index.php">
                <table>
                    <tr>
                        <td>CODE</td>
                        <td>
                            <label for="name"></label>
                            <input required type="text" name="code" id="code" size="40" />
                        </td>
                    </tr>
                </table>
                <input type="submit" id= "submit" value="Log in 2f" name="log_user_in2">
            </form>
            <?php
        } elseif ($result['result'] == '1') {
            echo "</br>Success: " . $login . "</br>";
            $_SESSION['login'] = $login;
            $_SESSION['logged'] = $_SESSION['login'];
            $_SESSION['CREATED'] = time();
        } else {
            echo "</br>Incorrect login or password.";
        }
    }
}



// Log user in - 2f krok 2
if (isset($_REQUEST['log_user_in2'])) {
    $code = $_REQUEST['code'];
    $login = $_SESSION['login'];
    if ($Pdo->log_2F_step2($login, $code)) {
        echo 'You are logged in as: ' . $_SESSION['login'];
        $_SESSION['logged'] = $login;
        $_SESSION['CREATED'] = time();
//$_SESSION['logged']='YES';
    }
}
?>
<H2> Main page</H2>

<!--rejestracja -->
<hr>
<P> Register new user</P>
<form method="post" action="index.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40"/>
            </td>
        </tr>
        <tr>
            <td>email</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="email" id="email" size="40"/>
            </td>
        </tr>
        <tr>
            <td>password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password" id="password" size="40"/>
            </td>
        </tr>
        <tr>
            <td>repeat password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password2" id="password2" size="40"/>
            </td>
        </tr>
        <tr>
            <td>rodzaj logowania</td>
            <td>
                <input type="radio" id="1f" name="auth" value=1 required>
                <label for="html">1F</label><br>
                <input type="radio" id="2f" name="auth" value=2 >
                <label for="css">2F</label><br>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit" value="Create account" name="add_user">
</form>

<!--logowanie zwykle -->
<hr>
<P> Log in</P>
<form method="post" action="index.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40" value="test123"/>
            </td>
        </tr>
        <tr>
            <td>password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password"
                       id="password" size="40" value="student"/>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit" value="Log in" name="log_user_in">
</form>

<!--edycja -->
<hr>
<P> Edytuj </P>
<form method="post" action="index.php">
    <table>
        <tr>
            <td>login</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="login" id="login" size="40" value=""/>
            </td>
        </tr>
        <tr>
            <td>new password</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="password"
                       id="password" size="40" value=""/>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit" value="Edytuj" name="edit_user">
</form>

<!--wyloguj
<form method="POST">
    <input type="submit" name="logout" value="Wyloguj">
</form>
-->
<form onsubmit='return confirm("Na pewno chcesz sie wylogowac? ")'>
    <input type="submit" name="logout" value="Wyloguj">
</form>


<?php
Page::display_navigation();
?>
</body>
</html>




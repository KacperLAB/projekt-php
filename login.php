<?php
session_start();
include_once "classes/Page.php";
include_once "classes/Pdo_.php";
Page::display_header("Main page");
$Pdo = new Pdo_();
$Pdo->get_privileges($_SESSION['logged']);
// Log user in – the first factor of autentication
if (isset($_REQUEST['log_user_in'])) {
    $password = $_REQUEST['password'];
    $login = $_REQUEST['login'];
    $result = $Pdo->log_2F_step1($login, $password);
    if ($result['result'] == 'success') {
        echo "Success: " . $login;
        $_SESSION['login'] = $login;
        $_SESSION['logged'] = 'After first step';
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
            <input type="submit" id= "submit" value="Log in" name="log_user_in">
        </form>
        <?php
    } else {
        echo 'Incorrect login or password.';
    }
}
Page::display_navigation();
?>
</body>
</html>
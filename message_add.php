

<?php
session_start();
include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Add message");

$priv = new Pdo_();
if (isset($_SESSION['logged']))
    $priv->get_privileges($_SESSION['logged']);

if (isset($_SESSION['CREATED']) && (time() - $_SESSION['CREATED'] > $timeout)) {
    echo '<script>alert("sesja wygasla zaloguj sie ponownie")</script>';
    session_unset();
    session_destroy();
}
if (isset($_SESSION['logged'])) {
    echo "</br>Zalogowany w sesji jako:" . $_SESSION['logged'];
} else {
    echo '<script>alert("musisz sie zalogowac")</script>';
    echo "<script> location.href='http://localhost/php/index.php'; </script>";
    exit;
}
?>
<hr>
<P> Add message</P>
<form method="post" action="messages.php">
    <table>
        <tr>
            <td>Name</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="name" id="name" size="56" />
            </td>
        </tr>
        <tr>
            <td>Type</td>
            <td>
                <label for="type"></label>
                <select name="type" id="type">
                    <option value="public">Public</option>
                    <option value="private">Private</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Message content</td>
            <td>
                <label for="content"></label>
                <textarea required type="text" name="content" id="content" rows="10" cols="40">
                </textarea>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit" value="Add message" name="add_message">
</form>
<hr>
<P>Navigation</P>
<?php
Page::display_navigation();
?>
</body>
</html>
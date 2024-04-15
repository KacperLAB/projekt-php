

<?php
session_start();
include_once "classes/Page.php";
include_once "classes/Db.php";
Page::display_header("Edit message");

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

$db = new Db(); // Create an instance of the Db class
// Get the message details from the database
if (isset($_POST['message_id'])) {
    $messageId = $_POST['message_id'];
    $message = $db->getMessageById($messageId);

    if ($message) {
        $id = $message['id'];
        $name = $message['name'];
        $type = $message['type'];
        $content = $message['message'];
    } else {
        echo "Failed to retrieve message details.";
        exit;
    }
}
?>

<hr>
<P> Edit message</P>


<form method="post" action="messages.php">
    <table>
        <tr>

            <td>
                <label for="id"></label>
                <input required type="hidden" name="id" id="id" size="56" value="<?php echo $id; ?>" />
            </td>
        </tr>
        <tr>
            <td>Name</td>
            <td>
                <label for="name"></label>
                <input required type="text" name="name" id="name" size="56" value="<?php echo $name; ?>" />
            </td>
        </tr>
        <tr>
            <td>Type</td>
            <td>
                <label for="type"></label>
                <select name="type" id="type">
                    <option value="public" <?php if ($type == 'public') echo 'selected'; ?>>Public</option>
                    <option value="private" <?php if ($type == 'private') echo 'selected'; ?>>Private</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>Message content</td>
            <td>
                <label for="content"></label>
                <textarea required name="content" id="content" rows="10" cols="40"><?php echo $content; ?></textarea>
            </td>
        </tr>
    </table>
    <input type="submit" id="submit" value="Edit message" name="edit_message">
</form>
<hr>
<P>Navigation</P>
<?php
Page::display_navigation();
?>
</body>
</html>
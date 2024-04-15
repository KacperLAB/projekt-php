<?php
session_start();
include_once "classes/Page.php";
include_once "classes/Db.php";
include_once "classes/Pdo_.php";
Page::display_header("Messages");
$conn = new Db();
$whitelist = array('private', 'public');

$priv = new Pdo_();
if (isset($_SESSION['logged']))
    $priv->get_privileges($_SESSION['logged']);

if (isset($_SESSION['CREATED']) && (time() - $_SESSION['CREATED'] > $timeout)) {
    echo '<script>alert("sesja wygasla zaloguj sie ponownie")</script>';
    session_unset();
    session_destroy();
    echo "<script> location.href='http://localhost/php/index.php'; </script>";
    exit;
} else {
    $_SESSION['CREATED'] = time();
}

if (isset($_SESSION['logged'])) {
    echo "</br>Zalogowany w sesji jako:" . $_SESSION['logged'];
} else {
    echo '<script>alert("musisz sie zalogowac")</script>';
    echo "<script> location.href='http://localhost/php/index.php'; </script>";
    exit;
}
//$db = new Db("localhost", "student", "student", "news");
// adding new message
if (isset($_REQUEST['add_message']) && in_array($_REQUEST['type'], $whitelist)) {
    //$name = filter_var($_REQUEST['name'],FILTER_SANITIZE_STRING);
    $name = $_REQUEST['name']; //bez zabezpieczenia
    $type = $_REQUEST['type'];
    $content = filter_var($_REQUEST['content'], FILTER_SANITIZE_STRING);
    //$content = addslashes($_REQUEST['content']); //brak zabezpieczenia
    if (!$conn->addMessage($name, $type, $content)) {
        echo "Adding new message failed";
    }
}
//editing message
if (isset($_REQUEST['edit_message2']) && in_array($_REQUEST['type'], $whitelist)) {

    $id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
    $name = filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING);
    $type = $_REQUEST['type'];
    $content = filter_var($_REQUEST['content'], FILTER_SANITIZE_STRING);

    if (!$conn->editMessage($_REQUEST['id'], $name, $type, $content)) {
        echo "Editing message failed";
    }
}

if (isset($_POST['delete_message']) && isset($_POST['message_id'])) {
    $id = $_POST['message_id'];
    if ($conn->delete_message($id)) {
        echo "Message deleted successfully";
        // Add any additional code or redirect as needed
    } else {
        echo "Failed to delete message";
    }
}

?>
<!-- ------------- -->
<hr>
<P> Messages</P>
<?php
$where_clause = "";
// filtering messages
if (isset($_REQUEST['filter_messages'])) {
    $string = $_REQUEST['string'];
    $where_clause = " and name LIKE '%" . $string . "%'";
}
    $clause = "'" . $_SESSION['logged'] . "'";
    //$sql = "SELECT * from message WHERE deleted=0 " . $where_clause;
    $sql = "SELECT m.id AS id,m.name,m.type,m.message,m.deleted,m.id_user,u.id AS u_id FROM message m INNER JOIN user u on m.id_user=u.id WHERE deleted=0 AND u.login=" . $clause . $where_clause;

echo $sql;
echo "<BR/><BR/>";

// POBIERANIE TYLKO WIADOMOSCI DANEGO UZYTKOWNIKA :
// SELECT * FROM message INNER JOIN user on message.id_user=user.id WHERE user.login = :login; 

$messages = $conn->select($sql);
$messages->execute();
$messages = $messages->fetchAll();
if (count($messages)) {
    echo '<table>';
    $counter = 1;
    foreach ($messages as $msg)://returned as objects
        ?>
        <tr>
            <td><?php echo $counter++ ?></td>
            <td><?php echo $msg['name'] ?></td>
            <td><?php echo $msg['message'] ?></td>


        <form method="post" action="message_edit.php">
            <input type="hidden" name="message_id" id="message_id" value="<?php echo $msg['id'] ?>"/>
        <?php
        if (isset($_SESSION['edit message']) || $msg['id_user']==$msg['u_id'])
            echo '<td><input type="submit" id= "submit" value="Edit" name="edit_message"></td>';
        ?>
        </form>

        <form method="post" action="my_messages.php">
            <input type="hidden" name="message_id" id="message_id" value="<?php echo $msg['id'] ?>"/>
            <?php
            if (isset($_SESSION['delete message']) || $msg['id_user']==$msg['u_id'])
                echo '<td><input type="submit" id= "submit" value="Delete" name="delete_message"></td>';
            ?>
        </form>
        </tr>
        <?php
    endforeach;
    echo '</table>';
} else {
    echo "No messages available</br>";
}
Page::display_navigation();
?>
<hr>
<P>Messages filtering</P>
<form method="post" action="my_messages.php">
    <table>
        <tr>
            <td>Title contains: </td>
            <td>
                <label for="name"></label>
                <input type="text" name="string" id="string" size="80"/>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit"
           value="Find messages" name="filter_messages">
</form>
<hr>

</body>
</html>
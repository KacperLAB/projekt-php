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
if (isset($_REQUEST['edit_message']) && in_array($_REQUEST['type'], $whitelist)) {

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


if (isset($_REQUEST['upr_role'])) {
    $name = $_REQUEST['name'];
    $sql = "SELECT * FROM privilege p"
            . " INNER JOIN role_privilege rp ON p.id=rp.id_privilege"
            . " INNER JOIN role r ON rp.id_role=r.id"
            . " WHERE r.role_name=:name";
    $stmt = $conn->select($sql);
    $stmt->execute(['name' => $name]);
    $data = $stmt->fetchAll();
    echo nl2br("\nUprawnienia dla tej roli:");
    foreach ($data as $row):
        echo nl2br("\n");
        echo $row['name'];
    endforeach;
}

if (isset($_REQUEST['upr_all'])) {
    //wyswietlanie wszystkich uprawnien w systemie
    $sql = "SELECT * from privilege";
    $priv = $conn->select($sql);
    $priv->execute();
    $priv = $priv->fetchAll();
    echo nl2br("\nWszystkie uprawnienia w systemie:");
    foreach ($priv as $p):
        echo nl2br("\n");
        echo "id: ", $p['id'], " name: ", $p['name'];
    endforeach;
}

if (isset($_REQUEST['upr_user'])) {
    //wyswietlanie uprawnien danego uzytkownika
    $login = $_SESSION['logged'];
    $sql = "SELECT p.name FROM privilege p"
            . " INNER JOIN user_privilege up ON p.id=up.id_privilege"
            . " INNER JOIN user u ON u.id=up.id_user"
            . " WHERE u.login=:login";
    $stmt = $conn->select($sql);
    $stmt->execute(['login' => $login]);
    $data = $stmt->fetchAll();
    echo nl2br("\nUprawnienia zalogowanego uzytkownika:");
    foreach ($data as $row):
        echo nl2br("\n");
        echo $row['name'];
    endforeach;
}

if (isset($_REQUEST['role_all'])) {
    //wyswietlanie wszystkich rol w systemie
    $sql = "SELECT * from role";
    $priv = $conn->select($sql);
    $priv->execute();
    $priv = $priv->fetchAll();
    echo nl2br("\nWszystkie role w systemie:");

    foreach ($priv as $p):
        echo nl2br("\n");
        echo "id: ", $p['id'], " name: ", $p['role_name'];
        echo " - ";
        echo $p['description'];
    endforeach;
}

if (isset($_REQUEST['role_user'])) {
    //wyswietlanie rol danego uzytkownika
    $login = $_SESSION['logged'];

    $sql = "SELECT r.role_name FROM role r"
            . " INNER JOIN user_role ur ON r.id=ur.id_role"
            . " INNER JOIN user u ON ur.id_user=u.id"
            . " WHERE u.login=:login";

    $stmt = $conn->select($sql);
    $stmt->execute(['login' => $login]);
    $data = $stmt->fetchAll();
    echo nl2br("\nRole zalogowanego uzytkownika:");
    foreach ($data as $row):
        echo nl2br("\n");
        echo $row['role_name'];
    endforeach;
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
if (isset($_SESSION['display private']))
    $sql = "SELECT * from message WHERE deleted=0 " . $where_clause;
else
    $sql = "SELECT * from message WHERE deleted=0 AND type='public'" . $where_clause;
echo $sql;
echo "<BR/><BR/>";
// POBIERANIE TYLKO WIADOMOSCI DANEGO UZYTKOWNIKA :
// SELECT * FROM message INNER JOIN user on message.id_user=user.id WHERE user.login = "test3"; 
// SELECT message.id,name,type,message,deleted,id_user FROM message INNER JOIN user on message.id_user=user.id WHERE user.login = "test3"; 

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
        if (isset($_SESSION['edit message']))
            echo '<td><input type="submit" id= "submit" value="Edit" name="edit_message"></td>';
        ?>
        </form>

        <form method="post" action="messages.php">
            <input type="hidden" name="message_id" id="message_id" value="<?php echo $msg['id'] ?>"/>
            <?php
            if (isset($_SESSION['delete message']))
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
<form method="post" action="messages.php">
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
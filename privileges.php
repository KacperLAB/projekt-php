<?php
session_start();
include_once "classes/Page.php";
include_once "classes/Db.php";
include_once "classes/Pdo_.php";
Page::display_header("Manage privileges");

$db = new Db();
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
//wyswietlanie uprawnien dla danej roli
if (isset($_REQUEST['upr_role'])) {
    $name = $_REQUEST['name'];
    $sql = "SELECT * FROM privilege p"
            . " INNER JOIN role_privilege rp ON p.id=rp.id_privilege"
            . " INNER JOIN role r ON rp.id_role=r.id"
            . " WHERE r.role_name=:name";
    $stmt = $db->select($sql);
    $stmt->execute(['name' => $name]);
    $data = $stmt->fetchAll();
    echo nl2br("\nUprawnienia dla tej roli:");
    foreach ($data as $row):
        echo nl2br("\n");
        echo $row['name'];
    endforeach;
}

//wyswietlanie wszystkich uprawnien w systemie
if (isset($_REQUEST['upr_all'])) {
    $sql = "SELECT * from privilege";
    $priv = $db->select($sql);
    $priv->execute();
    $priv = $priv->fetchAll();
    echo nl2br("\nWszystkie uprawnienia w systemie:");
    foreach ($priv as $p):
        echo nl2br("\n");
        echo "id: ", $p['id'], " name: ", $p['name'];
    endforeach;
}

//wyswietlanie uprawnien uzytkownika
if (isset($_REQUEST['upr_user'])) {
    $login = $_SESSION['logged'];
    if ($db) {
        // uprawnienia wynikajace z roli
        $rolePrivilegesSql = "SELECT p.name FROM privilege p
                              INNER JOIN role_privilege rp ON p.id = rp.id_privilege
                              INNER JOIN user_role ur ON rp.id_role = ur.id_role
                              INNER JOIN user u ON u.id = ur.id_user
                              WHERE u.login = :login_param";

        $stmt = $db->select($rolePrivilegesSql);
        $stmt->execute(['login_param' => $login]);
        $rolePrivileges = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // indywidualne uprawnienia uzytkownika
        $userPrivilegesSql = "SELECT p.name FROM privilege p
                              INNER JOIN user_privilege up ON p.id = up.id_privilege
                              INNER JOIN user u ON u.id = up.id_user
                              WHERE u.login = :login_param";

        $stmt = $db->select($userPrivilegesSql);
        $stmt->execute(['login_param' => $login]);
        $userPrivileges = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // polaczenie wynikow
        $privileges = array_merge($rolePrivileges, $userPrivileges);
        $privileges = array_unique($privileges);

        echo nl2br("\nUprawnienia zalogowanego użytkownika:");
        foreach ($privileges as $privilege) {
            echo nl2br("\n");
            echo $privilege;
        }
    } else {
        echo "Database connection failed.";
    }
}

//wyswietlanie wszystkich rol w systemie
if (isset($_REQUEST['role_all'])) {
    $sql = "SELECT * from role";
    $priv = $db->select($sql);
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

//wyswietlanie rol uzytkownika
if (isset($_REQUEST['role_user'])) {
    $login = $_SESSION['logged'];
    $sql = "SELECT r.role_name FROM role r"
            . " INNER JOIN user_role ur ON r.id=ur.id_role"
            . " INNER JOIN user u ON ur.id_user=u.id"
            . " WHERE u.login=:login";

    $stmt = $db->select($sql);
    $stmt->execute(['login' => $login]);
    $data = $stmt->fetchAll();
    echo nl2br("\nRole zalogowanego uzytkownika:");
    foreach ($data as $row):
        echo nl2br("\n");
        echo $row['role_name'];
    endforeach;
}

//dodawanie uprawnien dla uzytkownika
if (isset($_POST['addPrivilege'])) {
    $userId = $_POST['userId'];
    $privilegeId = $_POST['privilegeId'];
    $db->addUserPrivilege($userId, $privilegeId);
}

//usuwanie uprawnienia uzytkownika
if (isset($_POST['removePrivilege'])) {
    $userId = $_POST['userId'];
    $privilegeId = $_POST['privilegeId'];
    $db->removeUserPrivilege($userId, $privilegeId);
}

//tworzenie nowej roli
if (isset($_POST['createRole'])) {
    $roleName = $_POST['roleName'];
    $description = $_POST['description'];
    $db->createRole($roleName, $description);
}

//usuwanie roli
if (isset($_POST['deleteRole'])) {
    $roleId = $_POST['roleId'];
    $db->deleteRole($roleId);
}

//dodawanie uprawnienia do roli
if (isset($_POST['addPrivilegeToRole'])) {
    $roleId = $_POST['roleId'];
    $privilegeId = $_POST['privilegeId'];
    $db->addRolePrivilege($roleId, $privilegeId);
}

//usun uprawnienie z roli
if (isset($_POST['removePrivilegeFromRole'])) {
    $roleId = $_POST['roleId'];
    $privilegeId = $_POST['privilegeId'];
    $db->removeRolePrivilege($roleId, $privilegeId);
}

//przypisz role uzytkownikowi
if (isset($_POST['assignRole'])) {
    $roleId = $_POST['roleId'];
    $userId = $_POST['userId'];
    $db->assignUserRole($roleId, $userId);
}

//usun role uzytkownika
if (isset($_POST['removeRole'])) {
    $roleId = $_POST['roleId'];
    $userId = $_POST['userId'];
    $db->removeUserRole($roleId, $userId);
}
?>

<hr>
<P>Manage privileges</P>

<form method="post" action="privileges.php">
    <h3>Add Privilege to User</h3>
    <label for="userId">User ID:</label>
    <input type="text" name="userId" id="userId">
    <label for="privilegeId">Privilege ID:</label>
    <input type="text" name="privilegeId" id="privilegeId">
    <input type="submit" value="Add Privilege" name="addPrivilege">
</form>

<form method="post" action="privileges.php">
    <h3>Remove Privilege from User</h3>
    <label for="userId">User ID:</label>
    <input type="text" name="userId" id="userId">
    <label for="privilegeId">Privilege ID:</label>
    <input type="text" name="privilegeId" id="privilegeId">
    <input type="submit" value="Remove Privilege" name="removePrivilege">
</form>

<form method="post" action="privileges.php">
    <h3>Create Role</h3>
    <label for="roleName">Role Name:</label>
    <input type="text" name="roleName" id="roleName">
    <label for="description">Description:</label>
    <input type="text" name="description" id="description">
    <input type="submit" value="Create Role" name="createRole">
</form>

<form method="post" action="privileges.php">
    <h3>Delete Role</h3>
    <label for="roleId">Role ID:</label>
    <input type="text" name="roleId" id="roleId">
    <input type="submit" value="Delete Role" name="deleteRole">
</form>

<form method="post" action="privileges.php">
    <h3>Add Privilege to Role</h3>
    <label for="roleId">Role ID:</label>
    <input type="text" name="roleId" id="roleId">
    <label for="privilegeId">Privilege ID:</label>
    <input type="text" name="privilegeId" id="privilegeId">
    <input type="submit" value="Add Privilege" name="addPrivilegeToRole">
</form>

<form method="post" action="privileges.php">
    <h3>Remove Privilege from Role</h3>
    <label for="roleId">Role ID:</label>
    <input type="text" name="roleId" id="roleId">
    <label for="privilegeId">Privilege ID:</label>
    <input type="text" name="privilegeId" id="privilegeId">
    <input type="submit" value="Remove Privilege" name="removePrivilegeFromRole">
</form>

<form method="post" action="privileges.php">
    <h3>Assign Role to User</h3>
    <label for="roleId">Role ID:</label>
    <input type="text" name="roleId" id="roleId">
    <label for="userId">User ID:</label>
    <input type="text" name="userId" id="userId">
    <input type="submit" value="Assign Role" name="assignRole">
</form>

<form method="post" action="privileges.php">
    <h3>Remove Role from User</h3>
    <label for="roleId">Role ID:</label>
    <input type="text" name="roleId" id="roleId">
    <label for="userId">User ID:</label>
    <input type="text" name="userId" id="userId">
    <input type="submit" value="Remove Role" name="removeRole">
</form>

<P>Wyswietl uprawnienia</P>
<form method="post" action="privileges.php">
    <table>
        <tr>
            <td>Nazwa roli </td>
            <td>
                <label for="name"></label>
                <input type="text" name="name" id="name" size="80"/>
            </td>
        </tr>
    </table>
    <input type="submit" id= "submit"
           value="Uprawnienia danej roli" name="upr_role">
</form>


<form method="post" action="privileges.php">
    <input type="submit" value="Wszystkie uprawnienia" id= "submit" name="upr_all">
</form>


<form method="post" action="privileges.php">
    <input type="submit" value="Uprawnienia uzytkownika" id= "submit" name="upr_user">
</form>


<form method="post" action="privileges.php">
    <input type="submit" value="Wszytkie role" id= "submit" name="role_all">
</form>


<form method="post" action="privileges.php">
    <input type="submit" value="Role uzytkownika" id= "submit" name="role_user">
</form>

<hr>
<P>Navigation</P>
<?php
Page::display_navigation();
?>
</body>
</html>
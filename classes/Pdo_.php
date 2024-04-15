<?php

include_once "classes/Aes.php";
include_once "classes/Mail.php";
require './htmlpurifier-4.14.0/library/HTMLPurifier.auto.php';

//$Aes = new Aes();

class Pdo_ {

    private $pepper = 123;
    private $db;
    private $purifier;

    public function __construct() {
        $host = "localhost";
        $username = "root";
        $password = "";
        $dbname = "news";
        $config = HTMLPurifier_Config::createDefault();
        $this->purifier = new HTMLPurifier($config);
        try {
            $this->db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        } catch (PDOException $e) {
// add relevant code
            die();
        }
    }

    public function add_user($login, $email, $password, $auth) {
//generate salt
        $aes = new Aes();
        $salt = random_bytes(16);
        $login = $this->purifier->purify($login);
        $email = $this->purifier->purify($email);

        try {
            $sql = "INSERT INTO `user`( `login`, `email`, `hash`, `salt`, `id_status`, `password_form`, `auth_method`)
VALUES (:login,:email,:hash,:salt,:id_status,:password_form,:auth_method)";
//hash password with salt and pepper

            $password = hash('sha512', $password . $salt . $this->pepper);
//$password = $Aes->encrypt($password);
//$aes = new Aes();
//$password = $aes->encrypt($password);
//$password = $aes->encrypt($password);

            $data = [
                'login' => $login,
                'email' => $email,
                'hash' => $password,
                'salt' => $salt,
                'id_status' => '1',
                'password_form' => '1',
                'auth_method' => $auth
            ];
            $this->db->prepare($sql)->execute($data);
        } catch (Exception $e) {
//modify the code here
            print 'Exception' . $e->getMessage();
        }
    }

    public function log_user_in($login, $password) {
        $aes = new Aes();
        $login = $this->purifier->purify($login);
        try {
            $sql = "SELECT id,hash,login,salt FROM user
WHERE login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $user_data = $stmt->fetch();

            $password = hash('sha512', $password . $user_data['salt'] . $this->pepper);
//$password = $Aes->decrypt($password);
//$aes = new Aes();
//$password = $aes->decrypt($password);
//$password = (new Aes)->decrypt($password);

            if ($password == $user_data['hash']) {
                //if($password==$aes->decrypt($password)){
                echo "</br>login successfull<BR/>";

                echo "</br>You are logged in as: " . $user_data['login'] . '<BR/>';
            } else {
                echo "</br>login FAILED<BR/>";
            }
        } catch (Exception $e) {
//modify the code here
            print 'Exception' . $e->getMessage();
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
//$_SESSION['logged']=null;
        echo "</br>WYLOGOWANO";
    }

    public function edit_user($login, $password, $logged) {

//generate salt
        $salt = random_bytes(16);
        $login = $this->purifier->purify($login);

        if ($login == $logged) {
            try {
                $sql = "UPDATE user SET `hash`=:hash, `salt`=:salt WHERE login ='" . $login . "'";
//hash password with salt and pepper
                $password = hash('sha512', $password . $salt . $this->pepper);
//$password = $Aes->encrypt($password);
//$aes = new Aes();
//$password = $aes->encrypt($password);
//$password = (new Aes)->encrypt($password);

                $data = [
                    'hash' => $password,
                    'salt' => $salt,
                ];
                $this->db->prepare($sql)->execute($data);
            } catch (Exception $e) {
//modify the code here
                print 'Exception' . $e->getMessage();
            }
        } else {
            echo '<script>alert("musisz byc zalogowany na to konto")</script>';
        }
    }

    public function log_2F_step1($login, $password) {
        $login = $this->purifier->purify($login);
        try {
            $sql = "SELECT id,hash,login,salt,email,auth_method FROM user WHERE login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $user_data = $stmt->fetch();
            $password = hash('sha512', $password . $user_data['salt'] . $this->pepper);
            if ($password == $user_data['hash']) {
//generate and send OTP
                $otp = random_int(100000, 999999);
                $code_lifetime = date('Y-m-d H:i:s', time() + 300);
                try {
                    $sql = "UPDATE `user` SET `sms_code`=:code,
`code_timelife`=:lifetime WHERE login=:login";
                    $data = [
                        'login' => $login,
                        'code' => $otp,
                        'lifetime' => $code_lifetime
                    ];
                    $this->db->prepare($sql)->execute($data);
//add the code to send an e-mail with OTP
                    if ($user_data['auth_method'] == 1) {
                        $result = [
                            'result' => '1'
                        ];
                    } else {
                        $mail = new \PHPMailer\src\Exception\Mail();
                        $mail->send_email('s95516@pollub.edu.pl', $otp); //adres testowy
                        //$mail->send_email('kacper.papinski@gmail.com',$otp); //adres testowy
                        //$mail->send_email($user_data['email'],$otp); //do prawidlowego uzytkownika
                        $result = [
                            'result' => 'success'
                        ];
                    }

                    return $result;
                } catch (Exception $e) {
                    print 'Exception' . $e->getMessage();
//add necessary code here
                }
            } else {
                echo "</br>login FAILED<BR/>";
                $result = [
                    'result' => 'failed'
                ];
                return $result;
            }
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
//add necessary code here
        }
    }

    public function log_2F_step2($login, $code) {
        $login = $this->purifier->purify($login);
        $code = $this->purifier->purify($code);
        try {
            $sql = "SELECT id,login,sms_code,code_timelife
FROM user WHERE login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['login' => $login]);
            $user_data = $stmt->fetch();
            if ($code == $user_data['sms_code'] && time() < strtotime($user_data['code_timelife'])) {
//login successfull
                echo "</br>Login successfull<BR/>";
                return true;
            } else {
                echo "</br>login FAILED<BR/>";
                return false;
            }
        } catch (Exception $e) {
            print 'Exception' . $e->getMessage();
        }
    }

    /* public function get_privileges($login)
      {
      $login = $this->purifier->purify($login);
      try {
      $sql = "SELECT p.id,p.name FROM privilege p"
      ." INNER JOIN user_privilege up ON p.id=up.id_privilege"
      ." INNER JOIN user u ON u.id=up.id_user"
      ." WHERE u.login=:login";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['login' => $login]);
      $data = $stmt->fetchAll();
      foreach ($data as $row) {
      $privilege=$row['name'];
      $_SESSION[$privilege]='YES';
      }
      $data['status']='success';
      return $data;
      } catch (Exception $e) {
      print 'Exception' . $e->getMessage();
      }
      return [
      'status' => 'failed'
      ];
      } */

    /* public function get_privileges($login)
      {
      $login = $this->purifier->purify($login);
      try {
      $sql = "SELECT p.id, p.name FROM privilege p
      INNER JOIN role_privilege rp ON p.id = rp.id_privilege
      INNER JOIN user_role ur ON rp.id_role = ur.id_role
      INNER JOIN user u ON u.id = ur.id_user
      WHERE u.login = :login";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['login' => $login]);
      $data = $stmt->fetchAll();


      // Set session privileges based on roles assigned to the user
      foreach ($data as $row) {
      $privilege = $row['name'];
      $_SESSION[$privilege] = 'YES';
      }

      $data['status'] = 'success';
      return $data;
      } catch (Exception $e) {
      print 'Exception' . $e->getMessage();
      }

      return [
      'status' => 'failed'
      ];
      } */

    public function get_privileges($login) {
        $login = $this->purifier->purify($login);
        // uprwanienia wynikajace z przypisanych rol
        $sqlRole = "SELECT p.id, p.name FROM privilege p
            INNER JOIN role_privilege rp ON p.id = rp.id_privilege
            INNER JOIN user_role ur ON rp.id_role = ur.id_role
            INNER JOIN user u ON u.id = ur.id_user
            WHERE u.login = :login";

        //indywidualne uprawnienia
        $sqlIndividual = "SELECT p.id, p.name FROM privilege p
                  INNER JOIN user_privilege up ON p.id = up.id_privilege
                  INNER JOIN user u ON u.id = up.id_user
                  WHERE u.login = :login";

        $stmtRole = $this->db->prepare($sqlRole);
        $stmtRole->execute(['login' => $login]);
        $dataRole = $stmtRole->fetchAll();

        $stmtIndividual = $this->db->prepare($sqlIndividual);
        $stmtIndividual->execute(['login' => $login]);
        $dataIndividual = $stmtIndividual->fetchAll();

        $logged = $_SESSION['logged'];
// Resetowanie uprawnien
        //foreach ($_SESSION as $key => $value) {
        //    unset($_SESSION[$key]);
        //}
        session_unset();
        $_SESSION['logged'] = $logged;

// Ustawienie uprawnien na postawie roli
        foreach ($dataRole as $row) {
            $privilege = $row['name'];
            $_SESSION[$privilege] = 'YES';
        }

// Ustawienie uprawnien na podstawie indywidualnie nadanych
        foreach ($dataIndividual as $row) {
            $privilege = $row['name'];
            $_SESSION[$privilege] = 'YES';
        }
    }

}

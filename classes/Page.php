<?php
include_once "classes/Pdo_.php";
global $timeout;
$timeout = 3000;

class Page {

    static function display_header($title) {
        ?>
        <html lang="en-GB">
            <head>
                <title><?php echo $title ?></title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <!-- <link rel="stylesheet" href="style.css" type="text/css" /> -->
            </head>
            <body>
                <?php
            }

            static function display_navigation() {
                ?>
                <a href="index.php">index</a><br>
                <a href="messages.php">messages</a><br>
                <?php
                if (isset($_SESSION['add message']))
                    echo '<a href="message_add.php">add new message</a><br>';
                if (isset($_SESSION['edit message']))
                    echo '<a href="message_edit.php">edit message</a><br>';
                if (isset($_SESSION['logged']))
                    echo '<a href="my_messages.php">my messages</a><br>';
                ?>
                <a href="privileges.php">privileges</a><br>


                <?php
            }

        }
        
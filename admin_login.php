<?php
require_once 'config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$s=db()->prepare("SELECT * FROM admins WHERE username=?");$s->execute([trim($_POST['username']??'')]);$a=$s->fetch();if($a&&password_verify($_POST['password']??'',$a['password_hash'])){session_regenerate_id(true);$_SESSION['admin_id']=$a['id'];unset($_SESSION['user_id']);header('Location:admin.php');exit;}flash('Admin username ya password galat hai.');}
include 'header.php';
?><section class="panel"><h1>Admin login</h1><form method="post"><?=csrf_field()?><div class="field"><label>Admin username</label><input name="username" required></div><div class="field"><label>Password</label><input type="password" name="password" required></div><button>Admin login</button></form></section><?php include 'footer.php'; ?>

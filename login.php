<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $s=db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([strtolower(trim($_POST['email']??''))]); $u=$s->fetch();
 if ($u && password_verify($_POST['password']??'', $u['password_hash'])) {
  session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; $_SESSION['user_name']=$u['name']; $_SESSION['is_admin']=(bool)$u['is_admin'];
  header('Location: index.php'); exit;
 } flash('Email ya password galat hai.');
}
$pageTitle='Login'; require 'header.php';
?><div class="card narrow"><h1>Login</h1><form method="post"><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required><button>Login</button></form><p>Account nahi hai? <a href="register.php">Register</a></p></div><?php require 'footer.php'; ?>
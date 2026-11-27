<?php
require_once 'config.php'; require_login();
$s=db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$_SESSION['user_id']]); $u=$s->fetch();
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $name=trim($_POST['name']??''); $roll=trim($_POST['roll_no']??''); $room=trim($_POST['room_no']??''); $mobile=trim($_POST['mobile']??''); $email=strtolower(trim($_POST['email']??''));
 if (!$name||!$roll||!$room||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9+\-\s]{10,15}$/',$mobile)) flash('Details sahi bharein.');
 else {
  try { $s=db()->prepare('UPDATE users SET name=?,roll_no=?,room_no=?,mobile=?,email=? WHERE id=?'); $s->execute([$name,$roll,$room,$mobile,$email,$_SESSION['user_id']]); $_SESSION['user_name']=$name; flash('Profile updated.'); }
  catch(PDOException $e) { flash('Email kisi aur account mein registered hai.'); }
 }
 if (!empty($_POST['new_password'])) {
  if (strlen($_POST['new_password'])<8) flash('New password minimum 8 characters ka ho.');
  else { $s=db()->prepare('UPDATE users SET password_hash=? WHERE id=?'); $s->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),$_SESSION['user_id']]); flash('Profile aur password update ho gaye.'); }
 }
 header('Location: profile.php'); exit;
}
$pageTitle='My Profile'; require 'header.php';
?><div class="card narrow"><h1>My Profile</h1><form method="post"><label>Name</label><input name="name" value="<?=e($u['name'])?>" required><label>Roll Number</label><input name="roll_no" value="<?=e($u['roll_no'])?>" required><label>Room Number</label><input name="room_no" value="<?=e($u['room_no'])?>" required><label>Mobile</label><input name="mobile" value="<?=e($u['mobile'])?>" required><label>Email</label><input type="email" name="email" value="<?=e($u['email'])?>" required><label>New password (optional)</label><input type="password" name="new_password" minlength="8"><button>Save Profile</button></form></div><?php require 'footer.php'; ?>
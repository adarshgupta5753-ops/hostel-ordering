<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??''));
 $roll=trim($_POST['roll_no']??''); $room=trim($_POST['room_no']??'');
 $mobile=trim($_POST['mobile']??''); $pass=$_POST['password']??'';
 if (!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$roll||!$room||!preg_match('/^[0-9+\-\s]{10,15}$/',$mobile)||strlen($pass)<8) {
  flash('Details check karein. Password kam se kam 8 characters ka ho aur mobile valid ho.');
 } else {
  try {
   $s=db()->prepare('INSERT INTO users(name,email,roll_no,room_no,mobile,password_hash) VALUES(?,?,?,?,?,?)');
   $s->execute([$name,$email,$roll,$room,$mobile,password_hash($pass,PASSWORD_DEFAULT)]);
   flash('Registration successful. Ab login karein.'); header('Location: login.php'); exit;
  } catch(PDOException $e) { flash('Email ya roll number already registered ho sakta hai.'); }
 }
}
$pageTitle='Register'; require 'header.php';
?><div class="card narrow"><h1>Student Registration</h1><form method="post">
<label>Name</label><input name="name" required>
<label>Roll Number</label><input name="roll_no" required>
<label>Room Number</label><input name="room_no" required>
<label>Mobile Number</label><input name="mobile" required>
<label>Email</label><input type="email" name="email" required>
<label>Password (minimum 8 characters)</label><input type="password" name="password" minlength="8" required>
<button>Register</button></form></div><?php require 'footer.php'; ?>
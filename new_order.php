<?php require_once 'config.php';require_login();$pageTitle='Place Order';require 'header.php';?>
<div class="card narrow"><h1>Place Order</h1><p>Order place karne ka tareeka chunein:</p><div class="choice-grid"><a class="choice" href="automatic_order.php"><b>Automatic</b><span>System khud same hostel aur app ke orders ko group karega.</span></a><a class="choice" href="manual_order.php"><b>Manually</b><span>Students ki listings dekhein aur apni pasand se group join karein.</span></a></div></div>
<?php require 'footer.php';?>

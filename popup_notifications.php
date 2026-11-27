<?php
require_once 'config.php';header('Content-Type: application/json');if(!logged_in()){echo json_encode(['items'=>[]]);exit;}
$s=db()->prepare("SELECT id,title,body FROM notifications WHERE user_id=? AND seen=0 ORDER BY id");$s->execute([$_SESSION['user_id']]);$items=$s->fetchAll();
if($items){$ids=array_column($items,'id');$in=implode(',',array_map('intval',$ids));db()->exec("UPDATE notifications SET seen=1 WHERE id IN ($in)");}
echo json_encode(['items'=>$items],JSON_UNESCAPED_UNICODE);

<?php
require_once 'config.php'; require_student(); verify_csrf(); expire_pending();
$uid=(int)$_SESSION['user_id'];$pdo=db();
try{
 $pdo->beginTransaction();
 if(!empty($_POST['listing_id'])){
  $s=$pdo->prepare("SELECT * FROM manual_listings WHERE id=? AND status='pending' AND expires_at>NOW() FOR UPDATE");$s->execute([(int)$_POST['listing_id']]);$l=$s->fetch();
  if(!$l||$l['user_id']==$uid)throw new RuntimeException('Listing ab available nahi hai.');
  $pdo->prepare("INSERT INTO app_groups(hostel,app_name,target_threshold,total_amount,created_by) VALUES(?,?,?,0,?)")->execute([$l['hostel'],$l['app_name'],$l['threshold_amount'],$l['user_id']]);$gid=(int)$pdo->lastInsertId();
  $pdo->prepare("INSERT INTO group_members(group_id,user_id,threshold_amount) VALUES(?,?,?),(?,?,?)")->execute([$gid,$l['user_id'],$l['threshold_amount'],$gid,$uid,0.01]);
  $pdo->prepare("INSERT INTO orders(user_id,group_id,hostel,app_name,threshold_amount,purchase_amount,note,mode) SELECT user_id,?,hostel,app_name,threshold_amount,purchase_amount,note,'manual' FROM manual_listings WHERE id=?")->execute([$gid,$l['id']]);
  $pdo->prepare("INSERT INTO orders(user_id,group_id,hostel,app_name,threshold_amount,purchase_amount,note,mode) VALUES(?,?,?,?,?,?,?,'manual')")->execute([$uid,$gid,$l['hostel'],$l['app_name'],0.01,0.01,'']);
  $pdo->prepare("UPDATE manual_listings SET status='grouped',group_id=? WHERE id=?")->execute([$gid,$l['id']]);
 }elseif(!empty($_POST['group_id'])){
  $gid=(int)$_POST['group_id'];$s=$pdo->prepare("SELECT * FROM app_groups WHERE id=? AND status='open' FOR UPDATE");$s->execute([$gid]);$g=$s->fetch();if(!$g)throw new RuntimeException('Group available nahi hai.');
  $s=$pdo->prepare("SELECT id FROM group_members WHERE group_id=? AND user_id=?");$s->execute([$gid,$uid]);if(!$s->fetch())$pdo->prepare("INSERT INTO group_members(group_id,user_id,threshold_amount) VALUES(?,?,?)")->execute([$gid,$uid,$g['target_threshold']]);
 }else throw new RuntimeException('Invalid request.');
 $pdo->prepare("UPDATE app_groups SET target_threshold=(SELECT MIN(threshold_amount) FROM group_members WHERE group_id=?),total_amount=(SELECT COALESCE(SUM(purchase_amount),0) FROM orders WHERE group_id=?) WHERE id=?")->execute([$gid,$gid,$gid]);
 $pdo->commit();
 // Completion + history and notification for all members
 $s=$pdo->prepare("SELECT * FROM app_groups WHERE id=?");$s->execute([$gid]);$g=$s->fetch();
 if((float)$g['total_amount'] >= (float)$g['target_threshold']){
  $pdo->prepare("UPDATE app_groups SET status='completed',completed_at=NOW() WHERE id=?")->execute([$gid]);
  $m=$pdo->prepare("SELECT user_id FROM group_members WHERE group_id=?");$m->execute([$gid]);
  foreach($m->fetchAll() as $x){$id=(int)$x['user_id'];$pdo->prepare("INSERT IGNORE INTO group_history(group_id,user_id,result) VALUES(?,?,'completed')")->execute([$gid,$id]);create_notice($id,'Group-'. $gid .' complete','Aapka group threshold tak pahunch gaya hai.','success');}
 }
 flash('Group join ho gaya: Group-'.$gid);
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash($e->getMessage());}
header('Location:new_order.php');exit;

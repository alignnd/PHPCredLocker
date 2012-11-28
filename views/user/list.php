<?php
/** Authentication: List Users
*
* Copyright (c) 2012 B Tasker
* Released under GNU GPL V2
* See LICENSE
*
*/ 


defined('_CREDLOCK') or die;
BTMain::checkSuperAdmin();
global $notifications;




$auth = new AuthDB;

$users = $auth->listUsers();

$path = array(array('name'=>'Users','url'=>'index.php?option=viewUsers'));

$notifications->setBreadcrumb($path);

?>
<h1>System Users</h1>


<button class="btn btn-primary" onclick="window.location.href= 'index.php?option=addUser';">Add User</button><br /><br />

<table class="table table-hover">
<tr><th>Username</th><th>Name</th><th></th><th></th></tr>

<?php 
foreach ($users as $user){

?>

<tr id="User<?php echo $user->username; ?>">
  <td><?php echo $user->username; ?></td>
  <td><?php echo $user->Name;?></td>
  <td class="editicon"><i class='icon-pencil'></i></td>
  <td class="delicon"><i class='icon-remove'></i></td>
</tr>


<?php
}
?>

</table>

<button class="btn btn-primary" onclick="window.location.href= 'index.php?option=addUser';">Add User</button><br /><br />
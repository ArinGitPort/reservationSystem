<?php
require_once "../Model/db_Model.php";

if (isset($_POST['user_name'])){
  $user_name = ($_POST['user_name']);
  $user_password = ($_POST['user_password']);

  $newAdmin = "insert into admin (username, password, last_log_date) values ('$user_name', '$user_password', now())";
  save($newAdmin);
  redirect_to("admin_list.php");
}
?>


<html>
<head>
<title>Admin List</title>
<link rel="stylesheet" href="../style/style.css" type="text/css" media="screen" />
</head>

<body>
<div align="center" id="mainWrapper">
  <?php include_once("../header.php");?>
  <div id="pageContent"><br />
    <div align="right" style="margin-right:32px;"><a href="admin_list.php#adminForm">+ Add New Admin</a></div>
<div align="left" style="margin-left:24px;">
      <h2>Admin list</h2>
			Display the list of records here...
    </div>
    <hr />
    <a name="adminForm" id="adminForm"></a>
    <h3>
    &darr; Add New Admin &darr;
    </h3>
    <form action="admin_list.php" enctype="multipart/form-data" name="myForm" id="myform" method="post">
    <table width="90%" border="0" cellspacing="0" cellpadding="6">
      <tr>
        <td width="20%" align="right">Username</td>
        <td width="80%"><label>
          <input name="user_name" type="text" id="user_name" size="64" />
        </label></td>
      </tr>
      <tr>
        <td align="right">Password</td>
        <td> 
          <input name="user_password" type="text" id="user_password" size="12" />
        </td>
      </tr>   
      <tr>
        <td>&nbsp;</td>
        <td><label>
          <input type="submit" name="button" id="button" value="Create Admin" />
        </label></td>
      </tr>
    </table>
    </form>
    <br />
  <br />
  </div>
  <?php include_once("../footer.php");?>
</div>
</body>
</html>
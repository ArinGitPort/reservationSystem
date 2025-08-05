<?php
require_once "../Model/db_Model.php";

if (isset($_POST['fullname'])){
  $fullname = ($_POST['fullname']);
  $address = ($_POST['address']);

  $newCustomer = "insert into customer (fullname, adres, last_log_date) values ('$fullname', '$address', now())";
  save($newCustomer);
  redirect_to("customer_list.php");
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
      <h2>Customer list</h2>
			Display the list of records here...
    </div>
    <hr />
    <a name="customerForm" id="customerForm"></a>
    <h3>
    &darr; Add New Customer &darr;
    </h3>
    <form action="customer_list.php" enctype="multipart/form-data" name="myForm" id="myform" method="post">
    <table width="90%" border="0" cellspacing="0" cellpadding="6">
      <tr>
        <td width="20%" align="right">Fullname</td>
        <td width="80%"><label>
          <input name="fullname" type="text" id="fullname" size="64" />
        </label></td>
      </tr>
      <tr>
        <td align="right">Address</td>
        <td> 
          <input name="address" type="text" id="address" size="12" />
        </td>
      </tr>   
      <tr>
        <td>&nbsp;</td>
        <td><label>
          <input type="submit" name="button" id="button" value="Create Customer" />
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
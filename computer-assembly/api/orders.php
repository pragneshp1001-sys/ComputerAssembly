
<?php

include 'config.php';

$data=json_decode(
file_get_contents(
"php://input"
),
true
);

$customer=
$data['customer_name'];

$phone=
$data['phone'];

$address=
$data['address'];

$payment=
$data['payment_method'];

$total=
$data['total_amount'];

$sql="INSERT INTO orders(

customer_name,
phone,
shipping_address,
payment_method,
total_amount

)

VALUES(

?,?,?,?,?

)";

$stmt=
$conn->prepare(
$sql
);

$stmt->bind_param(

"ssssd",

$customer,
$phone,
$address,
$payment,
$total

);

$stmt->execute();

$orderId=
$conn->insert_id;

echo json_encode([

"success"=>true,
"order_id"=>$orderId

]);

?>

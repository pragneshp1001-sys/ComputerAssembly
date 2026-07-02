<?php

header("Content-Type: application/json");
require_once "config.php";


/* ==========================
   ADD PRODUCT
========================== */

if($_SERVER['REQUEST_METHOD']=="POST"){

$name=$_POST['name'] ?? '';
$description=$_POST['description'] ?? '';
$price=$_POST['price'] ?? 0;
$stock=$_POST['stock_quantity'] ?? 0;
$brand=$_POST['brand'] ?? '';
$category=$_POST['category_id'] ?? null;

$imagePath='uploads/products/default.png';


/* upload image */

if(isset($_FILES['image']) && $_FILES['image']['error']==0){

$uploadDir="../uploads/products/";

if(!file_exists($uploadDir)){
mkdir($uploadDir,0777,true);
}

$fileName=time().'_'.basename($_FILES['image']['name']);

$targetFile=$uploadDir.$fileName;

if(move_uploaded_file(
$_FILES['image']['tmp_name'],
$targetFile
)){

$imagePath='uploads/products/'.$fileName;

}

}


/* insert */

$stmt=$conn->prepare("
INSERT INTO products
(
name,
description,
price,
stock_quantity,
brand,
category_id,
image
)

VALUES
(?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssdisis",
$name,
$description,
$price,
$stock,
$brand,
$category,
$imagePath
);

if($stmt->execute()){

echo json_encode([
"success"=>true,
"message"=>"Product added successfully"
]);

}else{

echo json_encode([
"success"=>false,
"message"=>$conn->error
]);

}

exit;

}


/* ==========================
   GET ALL PRODUCTS
========================== */

if($_SERVER['REQUEST_METHOD']=="GET" && !isset($_GET['id'])){

$category_id=$_GET['category_id'] ?? null;
$search=$_GET['search'] ?? '';
$page=$_GET['page'] ?? 1;

$per_page=12;
$offset=($page-1)*$per_page;

$where=[];

if($category_id){
$where[]="p.category_id=$category_id";
}

if($search){

$search=$conn->real_escape_string($search);

$where[]="(
p.name LIKE '%$search%'
OR p.description LIKE '%$search%'
OR p.brand LIKE '%$search%'
)";
}

$where_clause='';

if(count($where)){
$where_clause='WHERE '.implode(' AND ',$where);
}

$count=$conn->query("
SELECT COUNT(*) total
FROM products p
$where_clause
")->fetch_assoc();

$total=$count['total'];

$query="
SELECT
p.*,
c.name category_name,

(
SELECT COALESCE(AVG(r.rating),0)
FROM reviews r
WHERE r.product_id=p.id
) average_rating,

(
SELECT COUNT(*)
FROM reviews r
WHERE r.product_id=p.id
) review_count

FROM products p

LEFT JOIN categories c
ON p.category_id=c.id

$where_clause

ORDER BY p.id DESC

LIMIT $per_page OFFSET $offset
";

$result=$conn->query($query);

$products=[];

while($row=$result->fetch_assoc()){

$products[]=$row;

}

echo json_encode([

"success"=>true,

"data"=>[

"products"=>$products,

"pagination"=>[

"page"=>$page,
"pages"=>ceil($total/$per_page),
"total"=>$total

]

]

]);

exit;

}



/* ==========================
   SINGLE PRODUCT
========================== */

if(isset($_GET['id'])){

$id=(int)$_GET['id'];

$stmt=$conn->prepare("

SELECT

p.*,
c.name category_name,

(
SELECT COALESCE(AVG(r.rating),0)
FROM reviews r
WHERE r.product_id=p.id
) rating,

(
SELECT COUNT(*)
FROM reviews r
WHERE r.product_id=p.id
) review_count

FROM products p

LEFT JOIN categories c
ON p.category_id=c.id

WHERE p.id=?

");

$stmt->bind_param("i",$id);

$stmt->execute();

$result=$stmt->get_result();

$product=$result->fetch_assoc();

echo json_encode([

"success"=>true,
"data"=>$product

]);

exit;

}

$conn->close();

?>
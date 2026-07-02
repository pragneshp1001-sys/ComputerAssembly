<?php
header('Content-Type: application/json');
require_once 'config.php';

$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action'] ?? '';

/* ================= DASHBOARD ================= */

if($method=="GET" && $action=="stats"){

$stats=[];

$stats['total_users']=$conn
->query("SELECT COUNT(*) total FROM users")
->fetch_assoc()['total'];

$orders=$conn
->query("
SELECT
COUNT(*) total,
COALESCE(SUM(total_amount),0) revenue
FROM orders
")
->fetch_assoc();

$stats['total_orders']=$orders['total'];
$stats['total_revenue']=$orders['revenue'];

$stats['total_products']=$conn
->query("SELECT COUNT(*) total FROM products")
->fetch_assoc()['total'];

$stats['pending_orders']=$conn
->query("
SELECT COUNT(*) total
FROM orders
WHERE order_status='pending'
")
->fetch_assoc()['total'];

$stats['low_stock_count']=$conn
->query("
SELECT COUNT(*) total
FROM products
WHERE stock_quantity<=10
")
->fetch_assoc()['total'];

$recent=$conn->query("
SELECT
o.id,
o.order_number,
o.total_amount,
o.order_status,
o.created_at,
u.username
FROM orders o
JOIN users u
ON o.user_id=u.id
ORDER BY o.created_at DESC
LIMIT 10
");

$stats['recent_orders']=$recent->fetch_all(MYSQLI_ASSOC);

echo json_encode($stats);
exit;
}


/* ================= ORDERS ================= */

if($method=="GET" && $action=="orders"){

$page=$_GET['page'] ?? 1;
$limit=20;
$offset=($page-1)*$limit;

$status=$_GET['status'] ?? '';

$where='';

if($status){
$where="WHERE o.order_status='$status'";
}

$q="
SELECT
o.*,
u.username,
u.email,
COUNT(oi.id) item_count
FROM orders o
JOIN users u
ON o.user_id=u.id
LEFT JOIN order_items oi
ON oi.order_id=o.id
$where
GROUP BY o.id
ORDER BY o.created_at DESC
LIMIT $limit OFFSET $offset
";

$result=$conn->query($q);

$count=$conn
->query("SELECT COUNT(*) total FROM orders")
->fetch_assoc()['total'];

echo json_encode([
'orders'=>$result->fetch_all(MYSQLI_ASSOC),
'total'=>$count,
'page'=>$page,
'pages'=>ceil($count/$limit)
]);

exit;
}


/* ================= USERS ================= */

if($method=="GET" && $action=="users"){

$page=$_GET['page'] ?? 1;
$limit=20;
$offset=($page-1)*$limit;

$search=$_GET['search'] ?? '';

$where='';

if($search){

$s=$conn->real_escape_string($search);

$where="
WHERE username LIKE '%$s%'
OR email LIKE '%$s%'
";
}

$q="
SELECT
u.id,
u.username,
u.email,
u.full_name,
u.role,
u.created_at,

(
SELECT COUNT(*)
FROM orders o
WHERE o.user_id=u.id
) total_orders,

(
SELECT COALESCE(SUM(total_amount),0)
FROM orders o
WHERE o.user_id=u.id
) total_spent

FROM users u
$where
ORDER BY created_at DESC
LIMIT $limit OFFSET $offset
";

$result=$conn->query($q);

$count=$conn
->query("SELECT COUNT(*) total FROM users")
->fetch_assoc()['total'];

echo json_encode([
'users'=>$result->fetch_all(MYSQLI_ASSOC),
'total'=>$count,
'page'=>$page,
'pages'=>ceil($count/$limit)
]);

exit;
}


/* ================= CATEGORIES ================= */

if($method=="GET" && $action=="categories"){

$q="
SELECT
c.*,
COUNT(p.id) product_count
FROM categories c
LEFT JOIN products p
ON p.category_id=c.id
GROUP BY c.id
";

$r=$conn->query($q);

echo json_encode([
'categories'=>$r->fetch_all(MYSQLI_ASSOC)
]);

exit;
}


/* ================= PRODUCTS ================= */

/* ================= PRODUCTS ================= */

/* CREATE PRODUCT */

if($method=="POST" && $action=="create_product"){

$name=$_POST['name'];
$brand=$_POST['brand'];
$category_id=$_POST['category_id'];
$price=$_POST['price'];
$stock_quantity=$_POST['stock_quantity'];
$description=$_POST['description'];

$image='default.jpg';

/* upload image */

if(isset($_FILES['image']) && $_FILES['image']['error']==0){

    $folder="../uploads/products/";

    if(!file_exists($folder)){
        mkdir($folder,0777,true);
    }

    $image=time().'_'.basename($_FILES['image']['name']);

    $success = move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $folder.$image
    );

    if(!$success){
        echo json_encode([
            "success"=>false,
            "message"=>"Failed to upload image"
        ]);
        exit;
    }
}

$stmt=$conn->prepare("
INSERT INTO products
(
name,
brand,
category_id,
price,
stock_quantity,
description,
image
)
VALUES(?,?,?,?,?,?,?)
");

$stmt->bind_param(
"ssidiss",
$name,
$brand,
$category_id,
$price,
$stock_quantity,
$description,
$image
);

$success=$stmt->execute();

if($success){
    $product_id = $conn->insert_id;
    echo json_encode([
        "success"=>true,
        "message"=>"Product added",
        "product_id"=>$product_id,
        "image"=>$image
    ]);
} else {
    echo json_encode([
        "success"=>false,
        "message"=>$conn->error
    ]);
}

exit;
}



/* UPDATE PRODUCT */

if($method=="POST" && $action=="update_product"){

$id=$_POST['id'];

$name=$_POST['name'];
$brand=$_POST['brand'];
$category_id=$_POST['category_id'];
$price=$_POST['price'];
$stock_quantity=$_POST['stock_quantity'];
$description=$_POST['description'];

$image=null;

if(isset($_FILES['image']) && $_FILES['image']['error']==0){

    $folder="../uploads/products/";

    if(!file_exists($folder)){
        mkdir($folder,0777,true);
    }

    $image=time().'_'.basename($_FILES['image']['name']);

    $success = move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $folder.$image
    );

    if(!$success){
        echo json_encode([
            "success"=>false,
            "message"=>"Failed to upload image"
        ]);
        exit;
    }
}

if($image !== null){

$stmt=$conn->prepare("
UPDATE products
SET
name=?,
brand=?,
category_id=?,
price=?,
stock_quantity=?,
description=?,
image=?
WHERE id=?
");

$stmt->bind_param(
"ssidissi",
$name,
$brand,
$category_id,
$price,
$stock_quantity,
$description,
$image,
$id
);

}else{

$stmt=$conn->prepare("
UPDATE products
SET
name=?,
brand=?,
category_id=?,
price=?,
stock_quantity=?,
description=?
WHERE id=?
");

$stmt->bind_param(
"ssidisi",
$name,
$brand,
$category_id,
$price,
$stock_quantity,
$description,
$id
);

}

$success=$stmt->execute();

echo json_encode([
"success"=>$success,
"message"=>$success ? "Product updated" : $conn->error,
"image"=>$image
]);

exit;
}



/* DELETE PRODUCT */

if($method=="POST" && $action=="delete_product"){

$data=json_decode(
file_get_contents("php://input"),
true
);

$stmt=$conn->prepare(
"DELETE FROM products WHERE id=?"
);

$stmt->bind_param(
"i",
$data['id']
);

$stmt->execute();

echo json_encode([
"success"=>true
]);

exit;
}

/* ================= REVIEWS ================= */

/* ================= REVIEWS ================= */

if($method=="GET" && $action=="reviews"){

$q="
SELECT
r.*,
p.name product_name,
u.username
FROM reviews r
LEFT JOIN products p
ON p.id=r.product_id
LEFT JOIN users u
ON u.id=r.user_id
ORDER BY r.created_at DESC
";

$r=$conn->query($q);

echo json_encode([
'reviews'=>$r->fetch_all(MYSQLI_ASSOC)
]);

exit;
}


/* ================= CREATE CATEGORY ================= */

if($method=="POST" && $action=="create_category"){

$data=json_decode(
file_get_contents("php://input"),
true
);

$stmt=$conn->prepare(
"INSERT INTO categories(name,description)
VALUES(?,?)"
);

$stmt->bind_param(
"ss",
$data['name'],
$data['description']
);

$stmt->execute();

echo json_encode([
"success"=>true
]);

exit;
}


/* ================= UPDATE CATEGORY ================= */

if($method=="POST" && $action=="update_category"){

$data=json_decode(
file_get_contents("php://input"),
true
);

$stmt=$conn->prepare(
"UPDATE categories
SET name=?,description=?
WHERE id=?"
);

$stmt->bind_param(
"ssi",
$data['name'],
$data['description'],
$data['id']
);

$stmt->execute();

echo json_encode([
"success"=>true
]);

exit;
}


/* ================= UPDATE ORDER STATUS ================= */

if($method=="POST" && $action=="update_order_status"){

$data=json_decode(
file_get_contents("php://input"),
true
);

$stmt=$conn->prepare(
"UPDATE orders
SET order_status=?
WHERE id=?"
);

$stmt->bind_param(
"si",
$data['status'],
$data['order_id']
);

$stmt->execute();

echo json_encode([
"success"=>true
]);

exit;
}


/* ================= INVALID ACTION ================= */

echo json_encode([
"success"=>false,
"message"=>"Invalid action"
]);

<?php
declare(strict_types=1);
session_set_cookie_params(['lifetime'=>0,'path'=>'/','httponly'=>true,'samesite'=>'Lax',
    'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
function out(bool $ok, string $message='', $data=null, int $status=200): never {
    http_response_code($status); echo json_encode(['success'=>$ok,'message'=>$message,'data'=>$data], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit;
}
function readData(string $name): array {
    global $dir; $path=$dir.'/'.$name;
    if (!is_file($path)) return [];
    $raw=file_get_contents($path); $data=json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}
function writeData(string $name, array $data): bool {
    global $dir; $path=$dir.'/'.$name;
    $json=json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if ($json===false) return false;
    $fp=fopen($path,'c+'); if (!$fp) return false;
    try { if (!flock($fp,LOCK_EX)) return false; ftruncate($fp,0); rewind($fp);
        $ok=fwrite($fp,$json.PHP_EOL)!==false; fflush($fp); flock($fp,LOCK_UN); return $ok;
    } finally { fclose($fp); }
}
function currentUser(): ?array { return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null; }
function requireLogin(): array { $u=currentUser(); if (!$u) out(false,'Please login first.',null,401); return $u; }
function requireAdmin(): array { $u=requireLogin(); if (($u['role']??'')!=='admin') out(false,'Administrator access required.',null,403); return $u; }
function cleanText($value, int $max=1000): string { return mb_substr(trim((string)$value),0,$max); }
$dir=dirname(__DIR__).'/data'; if (!is_dir($dir)) mkdir($dir,0750,true);
if ($_SERVER['REQUEST_METHOD']!=='POST') out(false,'POST requests only.',null,405);
$input=json_decode(file_get_contents('php://input') ?: '',true);
if (!is_array($input)) out(false,'Invalid request.',null,400);
$action=(string)($input['action']??'');

if ($action==='products') {
    $products=readData('products.json');
    out(true,'',$products);
}
if ($action==='message_create') {
    $name=cleanText($input['name']??'',80); $email=strtolower(cleanText($input['email']??'',150));
    $message=cleanText($input['message']??'',3000);
    if (mb_strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($message)<5) out(false,'Please enter a valid name, email and message.',null,422);
    $messages=readData('messages.json');
    $messages[]=['id'=>'MSG-'.strtoupper(bin2hex(random_bytes(4))),'name'=>$name,'email'=>$email,'message'=>$message,
        'status'=>'new','created_at'=>date(DATE_ATOM)];
    if (!writeData('messages.json',$messages)) out(false,'Could not save your message.',null,500);
    out(true,'Your message has been sent to Alberto support.');
}
if ($action==='checkout') {
    $user=requireLogin();
    if (($user['role']??'')!=='customer') out(false,'Only customer accounts can place orders.',null,403);
    $items=$input['items']??[]; $customerName=cleanText($input['customer_name']??$user['name'],100);
    $phone=cleanText($input['phone']??'',40); $address=cleanText($input['address']??'',500);
    if (!is_array($items)||count($items)<1||count($items)>30||$phone===''||mb_strlen($address)<8) out(false,'Add products, phone number and a complete delivery address.',null,422);
    $catalog=readData('products.json'); $lines=[]; $total=0;
    foreach ($items as $entry) {
        $id=(int)($entry['id']??0); $qty=(int)($entry['qty']??0); if ($qty<1||$qty>20) out(false,'Invalid product quantity.',null,422);
        $product=null; foreach ($catalog as $p) if ((int)($p['id']??0)===$id) {$product=$p;break;}
        if (!$product) out(false,'A product in your cart is no longer available.',null,422);
        $price=(float)$product['price']; $line=$price*$qty; $total+=$line;
        $lines[]=['product_id'=>$id,'name'=>$product['name'],'ref'=>$product['ref']??'','price'=>$price,'quantity'=>$qty,'line_total'=>$line];
    }
    $orders=readData('orders.json');
    $order=['id'=>'AW-'.strtoupper(bin2hex(random_bytes(4))),'user_email'=>$user['email'],'customer_name'=>$customerName,
        'phone'=>$phone,'address'=>$address,'items'=>$lines,'total'=>round($total,2),'currency'=>'USD',
        'status'=>'Pending','created_at'=>date(DATE_ATOM)];
    array_unshift($orders,$order);
    if (!writeData('orders.json',$orders)) out(false,'Could not save order.',null,500);
    out(true,'Order placed successfully. Your order ID is '.$order['id'],$order,201);
}
if ($action==='my_orders') {
    $user=requireLogin(); if (($user['role']??'')!=='customer') out(false,'Customer account required.',null,403);
    $mine=array_values(array_filter(readData('orders.json'),fn($o)=>strtolower($o['user_email']??'')===strtolower($user['email'])));
    out(true,'',$mine);
}

$admin=requireAdmin();
if ($action==='admin_summary') {
    $orders=readData('orders.json'); $messages=readData('messages.json'); $users=readData('users.json'); $products=readData('products.json');
    out(true,'',['orders'=>count($orders),'messages'=>count(array_filter($messages,fn($m)=>($m['status']??'new')==='new')),
        'customers'=>count($users),'products'=>count($products),'revenue'=>array_sum(array_map(fn($o)=>($o['status']??'')==='Cancelled'?0:(float)($o['total']??0),$orders))]);
}
if ($action==='admin_list') {
    $type=(string)($input['type']??'');
    $allowed=['orders'=>'orders.json','messages'=>'messages.json','customers'=>'users.json','products'=>'products.json'];
    if (!isset($allowed[$type])) out(false,'Unknown list type.',null,400);
    $rows=readData($allowed[$type]);
    if ($type==='customers') foreach ($rows as &$row) unset($row['password_hash']);
    out(true,'',$rows);
}
if ($action==='admin_message_status') {
    $id=cleanText($input['id']??'',80); $status=(string)($input['status']??'');
    if (!in_array($status,['new','read','replied','closed'],true)) out(false,'Invalid message status.',null,422);
    $rows=readData('messages.json'); $found=false;
    foreach ($rows as &$row) if (($row['id']??'')===$id) {$row['status']=$status;$found=true;break;}
    if (!$found) out(false,'Message not found.',null,404);
    if (!writeData('messages.json',$rows)) out(false,'Could not update message.',null,500);
    out(true,'Message status updated.');
}
if ($action==='admin_order_status') {
    $id=cleanText($input['id']??'',80); $status=(string)($input['status']??'');
    if (!in_array($status,['Pending','Processing','Shipped','Completed','Cancelled'],true)) out(false,'Invalid order status.',null,422);
    $rows=readData('orders.json'); $found=false;
    foreach ($rows as &$row) if (($row['id']??'')===$id) {$row['status']=$status;$found=true;break;}
    if (!$found) out(false,'Order not found.',null,404);
    if (!writeData('orders.json',$rows)) out(false,'Could not update order.',null,500);
    out(true,'Order status updated.');
}
if ($action==='admin_product_save') {
    $p=$input['product']??[]; if (!is_array($p)) out(false,'Invalid product.',null,422);
    $name=cleanText($p['name']??'',120); $brand=cleanText($p['brand']??'',80); $category=cleanText($p['category']??'',30);
    $price=(float)($p['price']??0); $image=cleanText($p['image']??'',1000);
    if ($name===''||$brand===''||$price<=0||!in_array($category,['luxury','vintage','smart','classic','clocks'],true)||!filter_var($image,FILTER_VALIDATE_URL)) out(false,'Enter a name, brand, valid category, positive price and image URL.',null,422);
    $rows=readData('products.json'); $id=(int)($p['id']??0);
    $record=['id'=>$id,'name'=>$name,'brand'=>$brand,'category'=>$category,'tag'=>cleanText($p['tag']??ucfirst($category),40),
        'ref'=>cleanText($p['ref']??('AW-'.str_pad((string)(count($rows)+1),3,'0',STR_PAD_LEFT)),40),'price'=>$price,
        'priceLabel'=>'$'.number_format($price,2),'description'=>cleanText($p['description']??'',1000),'image'=>$image,
        'movement'=>cleanText($p['movement']??'Quartz',80),'case'=>cleanText($p['case']??'Stainless steel',100),'water'=>cleanText($p['water']??'50 m',40)];
    $found=false;
    if ($id>0) foreach ($rows as &$row) if ((int)($row['id']??0)===$id) {$row=$record;$found=true;break;}
    if (!$found) { $record['id']=max(array_map(fn($r)=>(int)($r['id']??0),$rows) ?: [0])+1; $rows[]=$record; }
    if (!writeData('products.json',$rows)) out(false,'Could not save product.',null,500);
    out(true,$found?'Product updated.':'Product added.',$record);
}
if ($action==='admin_product_delete') {
    $id=(int)($input['id']??0); $rows=readData('products.json'); $new=array_values(array_filter($rows,fn($p)=>(int)($p['id']??0)!==$id));
    if (count($new)===count($rows)) out(false,'Product not found.',null,404);
    if (!writeData('products.json',$new)) out(false,'Could not delete product.',null,500);
    out(true,'Product deleted.');
}
out(false,'Unknown action.',null,400);

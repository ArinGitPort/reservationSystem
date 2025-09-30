<?php
require_once '../config/db_model.php';

if (!isset($_GET['order_id'])) {
    echo '<p class="text-danger">Invalid order ID</p>';
    exit;
}

$orderId = intval($_GET['order_id']);
$order = getOrderById($orderId);
$orderItems = getOrderItemsById($orderId);

if (!$order) {
    echo '<p class="text-danger">Order not found</p>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6>Order Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Order ID:</strong></td>
                <td>EFH-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><span class="badge bg-primary"><?= ucfirst($order['order_status']) ?></span></td>
            </tr>
            <tr>
                <td><strong>Type:</strong></td>
                <td><?= ucfirst(str_replace('-', ' ', $order['order_type'])) ?></td>
            </tr>
            <tr>
                <td><strong>Date:</strong></td>
                <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
            </tr>
            <tr>
                <td><strong>Total Amount:</strong></td>
                <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6>Customer Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Name:</strong></td>
                <td><?= htmlspecialchars($order['customer_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td><?= htmlspecialchars($order['customer_phone']) ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?= htmlspecialchars($order['customer_email'] ?: 'N/A') ?></td>
            </tr>
            <?php if ($order['delivery_address']): ?>
            <tr>
                <td><strong>Address:</strong></td>
                <td><?= htmlspecialchars($order['delivery_address']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($order['special_instructions']): ?>
            <tr>
                <td><strong>Instructions:</strong></td>
                <td><?= htmlspecialchars($order['special_instructions']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<hr>

<h6>Order Items</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['menu_name']) ?></td>
                <td>₱<?= number_format($item['price'], 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>₱<?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="table-dark">
                <th colspan="3">Total</th>
                <th>₱<?= number_format($order['total_amount'], 2) ?></th>
            </tr>
        </tfoot>
    </table>
</div>
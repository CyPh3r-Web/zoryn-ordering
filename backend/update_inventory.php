<?php
require_once 'dbconn.php';
require_once 'inventory_manager.php';
require_once 'fifo_stock_helper.php';

class InventoryUpdater {
    private $conn;
    private $inventoryManager;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->inventoryManager = new InventoryManager($conn);
    }

    // Convert quantity based on unit
    private function convertQuantity($quantity, $fromUnit, $toUnit) {
        // First convert to base unit (grams for weight, ml for volume)
        $baseQuantity = $this->convertToBaseUnit($quantity, $fromUnit);
        
        // Then convert from base unit to target unit
        return $this->convertFromBaseUnit($baseQuantity, $toUnit);
    }

    // Convert to base unit (grams for weight, ml for volume)
    private function convertToBaseUnit($quantity, $unit) {
        $unit = strtolower($unit);
        
        // Weight conversions to grams
        switch ($unit) {
            case 'kg':
                return $quantity * 1000;
            case 'g':
                return $quantity;
            case 'mg':
                return $quantity / 1000;
            case 'oz':
                return $quantity * 28.3495;
            case 'lb':
                return $quantity * 453.592;
            
            // Volume conversions to ml
            case 'liters':
            case 'l':
                return $quantity * 1000;
            case 'ml':
                return $quantity;
            case 'cup':
                return $quantity * 236.588;
            case 'tbsp':
                return $quantity * 14.7868;
            case 'tsp':
                return $quantity * 4.92892;
            case 'fl oz':
                return $quantity * 29.5735;
            
            // Count-based units
            case 'pcs':
            case 'pieces':
            case 'units':
                return $quantity;
            
            default:
                throw new Exception("Unknown unit: {$unit}");
        }
    }

    // Convert from base unit to target unit
    private function convertFromBaseUnit($quantity, $unit) {
        $unit = strtolower($unit);
        
        // Weight conversions from grams
        switch ($unit) {
            case 'kg':
                return $quantity / 1000;
            case 'g':
                return $quantity;
            case 'mg':
                return $quantity * 1000;
            case 'oz':
                return $quantity / 28.3495;
            case 'lb':
                return $quantity / 453.592;
            
            // Volume conversions from ml
            case 'liters':
            case 'l':
                return $quantity / 1000;
            case 'ml':
                return $quantity;
            case 'cup':
                return $quantity / 236.588;
            case 'tbsp':
                return $quantity / 14.7868;
            case 'tsp':
                return $quantity / 4.92892;
            case 'fl oz':
                return $quantity / 29.5735;
            
            // Count-based units
            case 'pcs':
            case 'pieces':
            case 'units':
                return $quantity;
            
            default:
                throw new Exception("Unknown unit: {$unit}");
        }
    }

    // Check if units are compatible (both weight or both volume)
    private function areUnitsCompatible($unit1, $unit2) {
        $weightUnits = ['kg', 'g', 'mg', 'oz', 'lb'];
        $volumeUnits = ['liters', 'l', 'ml', 'cup', 'tbsp', 'tsp', 'fl oz'];
        $countUnits = ['pcs', 'pieces', 'units'];
        
        $unit1 = strtolower($unit1);
        $unit2 = strtolower($unit2);
        
        if (in_array($unit1, $weightUnits) && in_array($unit2, $weightUnits)) return true;
        if (in_array($unit1, $volumeUnits) && in_array($unit2, $volumeUnits)) return true;
        if (in_array($unit1, $countUnits) && in_array($unit2, $countUnits)) return true;
        
        return false;
    }

    // Update inventory for a specific order
    public function updateInventoryForOrder($order_id) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Get all items in the order
            $stmt = $this->conn->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($order_item = $result->fetch_assoc()) {
                // Get ingredients for this product
                $stmt = $this->conn->prepare("
                    SELECT pi.ingredient_id, pi.quantity, pi.unit, i.stock, i.ingredient_name, i.unit as stock_unit
                    FROM product_ingredients pi 
                    JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                    WHERE pi.product_id = ?
                ");
                $stmt->bind_param("i", $order_item['product_id']);
                $stmt->execute();
                $ingredients = $stmt->get_result();
                
                while ($ingredient = $ingredients->fetch_assoc()) {
                    // Check if units are compatible
                    if (!$this->areUnitsCompatible($ingredient['unit'], $ingredient['stock_unit'])) {
                        throw new Exception("Incompatible units for ingredient {$ingredient['ingredient_name']}: {$ingredient['unit']} and {$ingredient['stock_unit']}");
                    }
                    
                    // Calculate total quantity needed (product quantity * ingredient quantity)
                    $total_quantity = $order_item['quantity'] * $ingredient['quantity'];
                    
                    // Convert quantity to match stock unit
                    $converted_quantity = $this->convertQuantity($total_quantity, $ingredient['unit'], $ingredient['stock_unit']);
                    
                    // Log the update details
                    error_log("Updating stock for order {$order_id} - Product {$order_item['product_id']} - Ingredient {$ingredient['ingredient_name']}:
                        Current stock: {$ingredient['stock']}{$ingredient['stock_unit']}
                        Quantity to deduct: {$total_quantity}{$ingredient['unit']} (converted: {$converted_quantity}{$ingredient['stock_unit']})");

                    if (fifo_lots_table_exists($this->conn)) {
                        fifo_deduct_for_sale(
                            $this->conn,
                            (int) $ingredient['ingredient_id'],
                            (float) $converted_quantity,
                            (int) $order_id,
                            date('Y-m-d')
                        );
                    } else {
                        $update_stmt = $this->conn->prepare("
                            UPDATE ingredients 
                            SET stock = stock - ? 
                            WHERE ingredient_id = ?
                        ");
                        $update_stmt->bind_param("di", $converted_quantity, $ingredient['ingredient_id']);
                        $update_stmt->execute();

                        $log_stmt = $this->conn->prepare("
                            INSERT INTO inventory_movements
                                (ingredient_id, movement_type, quantity, unit_cost,
                                 reference_type, reference_id, notes, movement_date)
                            VALUES (?, 'sale', ?, 0, 'order', ?, ?, CURDATE())
                        ");
                        $saleNote = "Sale deduction for order #{$order_id}";
                        $log_stmt->bind_param("idis", $ingredient['ingredient_id'], $converted_quantity, $order_id, $saleNote);
                        $log_stmt->execute();
                    }

                    // Verify the update
                    $verify_stmt = $this->conn->prepare("
                        SELECT stock FROM ingredients WHERE ingredient_id = ?
                    ");
                    $verify_stmt->bind_param("i", $ingredient['ingredient_id']);
                    $verify_stmt->execute();
                    $new_stock = $verify_stmt->get_result()->fetch_assoc()['stock'];
                    
                    error_log("New stock for {$ingredient['ingredient_name']}: {$new_stock}{$ingredient['stock_unit']}");
                }
            }

            // Commit transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Error updating inventory for order {$order_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse ingredient deductions for one or more cart lines (cancel line or whole order).
     * $lines: list of [ 'product_id' => int, 'quantity' => int ]
     */
    public function restockIngredientsForLines($lines, $order_id, $notesPrefix = 'Order cancellation restock') {
        try {
            $this->conn->begin_transaction();

            foreach ($lines as $line) {
                $product_id = (int) $line['product_id'];
                $qty = (int) $line['quantity'];
                if ($product_id <= 0 || $qty <= 0) {
                    continue;
                }

                $stmt = $this->conn->prepare("
                    SELECT pi.ingredient_id, pi.quantity, pi.unit, i.stock, i.ingredient_name, i.unit as stock_unit
                    FROM product_ingredients pi
                    JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                    WHERE pi.product_id = ?
                ");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $ingredients = $stmt->get_result();

                while ($ingredient = $ingredients->fetch_assoc()) {
                    if (!$this->areUnitsCompatible($ingredient['unit'], $ingredient['stock_unit'])) {
                        throw new Exception("Incompatible units for ingredient {$ingredient['ingredient_name']}: {$ingredient['unit']} and {$ingredient['stock_unit']}");
                    }

                    $total_quantity = $qty * $ingredient['quantity'];
                    $converted_quantity = $this->convertQuantity($total_quantity, $ingredient['unit'], $ingredient['stock_unit']);

                    if (fifo_lots_table_exists($this->conn)) {
                        fifo_return_from_sale(
                            $this->conn,
                            (int) $ingredient['ingredient_id'],
                            (float) $converted_quantity,
                            (int) $order_id,
                            date('Y-m-d')
                        );
                    } else {
                        $update_stmt = $this->conn->prepare("
                            UPDATE ingredients
                            SET stock = stock + ?
                            WHERE ingredient_id = ?
                        ");
                        $update_stmt->bind_param("di", $converted_quantity, $ingredient['ingredient_id']);
                        $update_stmt->execute();

                        $log_stmt = $this->conn->prepare("
                            INSERT INTO inventory_movements
                                (ingredient_id, movement_type, quantity, unit_cost,
                                 reference_type, reference_id, notes, movement_date)
                                VALUES (?, 'return_in', ?, 0, 'order', ?, ?, CURDATE())
                        ");
                        $note = "{$notesPrefix} for order #{$order_id}";
                        $log_stmt->bind_param("idis", $ingredient['ingredient_id'], $converted_quantity, $order_id, $note);
                        $log_stmt->execute();
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error restocking for order {$order_id}: " . $e->getMessage());
            return false;
        }
    }

    // Check if there's enough stock for an order
    public function checkStockForOrder($items) {
        try {
            foreach ($items as $item) {
                // Get ingredients for this product
                $stmt = $this->conn->prepare("
                    SELECT pi.ingredient_id, pi.quantity, pi.unit, i.stock, i.ingredient_name, i.unit as stock_unit
                    FROM product_ingredients pi 
                    JOIN ingredients i ON pi.ingredient_id = i.ingredient_id
                    WHERE pi.product_id = ?
                ");
                $stmt->bind_param("i", $item['product_id']);
                $stmt->execute();
                $ingredients = $stmt->get_result();
                
                while ($ingredient = $ingredients->fetch_assoc()) {
                    // Calculate total quantity needed
                    $total_quantity = $item['quantity'] * $ingredient['quantity'];
                    
                    // Convert quantity to match stock unit
                    $converted_quantity = $this->convertQuantity($total_quantity, $ingredient['unit'], $ingredient['stock_unit']);

                    $available = fifo_lots_table_exists($this->conn)
                        ? fifo_cluster_available($this->conn, (int) $ingredient['ingredient_id'])
                        : (float) $ingredient['stock'];
                    if ($available + 1e-6 < $converted_quantity) {
                        return array(
                            'success' => false,
                            'message' => 'Not enough stock for ' . $ingredient['ingredient_name']
                                . ". Required: {$total_quantity}{$ingredient['unit']} (converted: {$converted_quantity}{$ingredient['stock_unit']}), Available: {$available}{$ingredient['stock_unit']}"
                        );
                    }
                }
            }
            
            return array('success' => true);
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error checking stock: ' . $e->getMessage()
            );
        }
    }

    // Update inventory for product ingredients
    public function updateInventoryForProduct($updates) {
        try {
            $this->conn->begin_transaction();

            foreach ($updates as $update) {
                $ingredient_id = $update['ingredient_id'];
                $quantity = $update['quantity'];
                $unit = $update['unit'];

                // Get the ingredient's current stock unit
                $stmt = $this->conn->prepare("SELECT unit FROM ingredients WHERE ingredient_id = ?");
                $stmt->bind_param("i", $ingredient_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $ingredient = $result->fetch_assoc();
                
                if (!$ingredient) {
                    throw new Exception("Ingredient not found: $ingredient_id");
                }

                // Convert quantity to match the ingredient's stock unit
                $converted_quantity = $this->convertQuantity($quantity, $unit, $ingredient['unit']);

                if (fifo_lots_table_exists($this->conn)) {
                    fifo_manual_stock_out($this->conn, (int) $ingredient_id, (float) $converted_quantity);
                } else {
                    $update_stmt = $this->conn->prepare("UPDATE ingredients SET stock = stock - ?, updated_at = NOW() WHERE ingredient_id = ?");
                    $update_stmt->bind_param("di", $converted_quantity, $ingredient_id);
                    $update_stmt->execute();

                    if ($update_stmt->affected_rows === 0) {
                        throw new Exception("Failed to update stock for ingredient ID: $ingredient_id");
                    }
                }
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Ingredient stocks updated successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Handle AJAX requests only when this file is the entrypoint (not when included).
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'update_inventory.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $inventoryUpdater = new InventoryUpdater($conn);
    
    if (($data['action'] ?? '') === 'update_stocks') {
        $result = $inventoryUpdater->updateInventoryForProduct($data['updates']);
        echo json_encode($result);
    } else if (($data['action'] ?? '') === 'check_stock') {
        $order_id = $data['order_id'] ?? 0;
        $result = $inventoryUpdater->checkStockForOrder($order_id);
        echo json_encode($result);
    } else if (($data['action'] ?? '') === 'update_inventory') {
        $order_id = $data['order_id'] ?? 0;
        $result = $inventoryUpdater->updateInventoryForOrder($order_id);
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}
?> 
-- Create product_categories table
CREATE TABLE IF NOT EXISTS product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial product categories
INSERT INTO product_categories (category_name, description) VALUES
('Hot Coffee', 'Traditional hot coffee beverages'),
('Cold Coffee', 'Iced and cold coffee drinks'),
('Milky Series', 'Coffee drinks with milk variations'),
('Rookie Series', 'Beginner-friendly coffee options'),
('Choco-ey Series', 'Chocolate-based coffee drinks'),
('Gold Series', 'Premium coffee selections'); 
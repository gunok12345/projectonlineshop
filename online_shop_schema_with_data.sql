
-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS online_shop DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_shop;

-- ตารางผู้ใช้
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin','member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- เพิ่มข้อมูลผู้ใช้ (admin และ member)
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin1', 'admin_pass', 'admin1@example.com', 'Admin One', 'admin'),
('member1', 'member_pass', 'member1@example.com', 'John Doe', 'member'),
('member2', 'member_pass', 'member2@example.com', 'Jane Smith', 'member');

-- ตารางหมวดหมู่สินค้า
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
);

-- เพิ่มข้อมูลหมวดหมู่
INSERT INTO categories (category_name) VALUES
('อิเล็กทรอนิกส์'),
('เครื่องเขียน'),
('เสื้อผ้า');

-- ตารางสินค้า
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- เพิ่มข้อมูลสินค้า
INSERT INTO products (product_name, description, price, stock, category_id) VALUES
('หูฟังไร้สาย', 'หูฟัง Bluetooth คุณภาพเสียงดี', 599.00, 50, 1),
('สมุดโน้ต', 'สมุดโน้ตขนาด A5', 35.00, 100, 2),
('เสื้อยืดคอกลม', 'เสื้อยืดสีขาวคอกลม', 199.00, 80, 3);

-- ตารางคำสั่งซื้อ
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- เพิ่มข้อมูลคำสั่งซื้อ
INSERT INTO orders (user_id, total_amount, status) VALUES
(2, 834.00, 'processing');

-- ตารางรายการสินค้าที่สั่งซื้อ
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- เพิ่มรายการสินค้าที่สั่งซื้อ
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 599.00),
(1, 2, 2, 35.00),
(1, 3, 1, 199.00);

-- ตารางตะกร้าสินค้า
CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- เพิ่มข้อมูลในตะกร้า
INSERT INTO cart (user_id, product_id, quantity) VALUES
(2, 2, 1),
(3, 1, 2);

-- ตารางการจัดส่ง
CREATE TABLE IF NOT EXISTS shipping (
    shipping_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    shipping_status ENUM('not_shipped','shipped','delivered') DEFAULT 'not_shipped',
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- เพิ่มข้อมูลการจัดส่ง
INSERT INTO shipping (order_id, address, city, postal_code, phone, shipping_status) VALUES
(1, '123 ถนนหลัก เขตเมือง', 'กรุงเทพมหานคร', '10100', '0812345678', 'shipped');
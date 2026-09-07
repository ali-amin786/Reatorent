-- Database creation & schema for A1 Peshawari Chapli Kabab (MASHAALLAH Naan House)
-- Location: Gulzar-e-Hijri Scheme 33, Karachi

CREATE DATABASE IF NOT EXISTS `kabab_restaurant` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kabab_restaurant`;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Items Table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Delivery Areas Table
CREATE TABLE IF NOT EXISTS delivery_areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_area_id INT,
    order_notes TEXT,
    payment_method ENUM('easypaisa','jazzcash','bank_transfer','cod') NOT NULL,
    transaction_id VARCHAR(100),
    payment_proof VARCHAR(255),
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status ENUM('Pending','Payment Verified','Preparing','Out for Delivery','Completed','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_area_id) REFERENCES delivery_areas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Order Items Table (Snapshotting item_title & price)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_title VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Order Status Log (Audit Trail)
CREATE TABLE IF NOT EXISTS order_status_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Categories
INSERT INTO categories (id, name) VALUES
(1, 'Chapli Kabab Specialties'),
(2, 'MASHAALLAH Naan & Breads'),
(3, 'Tawa & Shinwari Specials'),
(4, 'Chutneys, Raita & Sides'),
(5, 'Chilled Beverages')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed Delivery Areas in Karachi
INSERT INTO delivery_areas (id, area_name, delivery_fee, is_active) VALUES
(1, 'Gulzar-e-Hijri Scheme 33 (Local Sector)', 60.00, 1),
(2, 'Safoora Chowrangi & Goth', 80.00, 1),
(3, 'University Road / NED / Karachi University', 120.00, 1),
(4, 'Gulshan-e-Iqbal (Block 1 to 7)', 150.00, 1),
(5, 'Gulshan-e-Iqbal (Block 8 to 19)', 180.00, 1),
(6, 'Gulistan-e-Johar (Block 1 to 10)', 150.00, 1),
(7, 'Malir Cantt (Gates 1 - 5)', 170.00, 1),
(8, 'Sohrab Goth & Super Highway Bypass', 130.00, 1)
ON DUPLICATE KEY UPDATE area_name=VALUES(area_name), delivery_fee=VALUES(delivery_fee);

-- Seed Menu Items
INSERT INTO items (id, category_id, title, description, price, image_url, is_available) VALUES
(1, 1, 'Peshawari Beef Chapli Kabab (2 Pcs)', 'Authentic hand-minced beef patties spiced with crushed coriander, pomegranate seeds (anardana), fried on iron tawa in real bone marrow & tallow for unmatched smoky flavor.', 480.00, 'beef_chapli_double.jpg', 1),
(2, 1, 'Peshawari Beef Chapli Kabab (Single Large)', 'Single sizzling beef chapli kabab with sliced tomatoes, green chilies, and fried egg topping cooked to golden perfection.', 250.00, 'beef_chapli_single.jpg', 1),
(3, 1, 'Special Chicken Chapli Kabab (2 Pcs)', 'Tender minced chicken blended with garlic, ginger, crushed chilies, scrambled egg and fresh mint, griddled soft and juicy.', 420.00, 'chicken_chapli.jpg', 1),
(4, 1, 'A1 Feast Platter (3 Kababs + 2 Roghani Naan)', 'Family sharing box: 2 Beef Chapli Kababs, 1 Chicken Chapli Kabab, 2 fresh Roghani Naans, large zeera raita, imli chutney & fresh onion salad.', 1150.00, 'feast_platter.jpg', 1),
(5, 2, 'MASHAALLAH Special Roghani Naan', 'Freshly baked tandoori naan brushed with pure butter, sesame seeds (til), and kalonji. Crispy outside, fluffy inside.', 90.00, 'roghani_naan.jpg', 1),
(6, 2, 'Crispy Kandahari Naan', 'Traditional large thin crust Kandahari flatbread baked in blistering hot tandoor, perfect pairing for chapli kabab.', 70.00, 'kandahari_naan.jpg', 1),
(7, 2, 'Garlic Butter Tandoori Naan', 'Tandoor naan layered with minced roasted garlic, coriander and salted melted butter.', 120.00, 'garlic_naan.jpg', 1),
(8, 2, 'Peshawari Sada Tandoori Roti', 'Wholesome whole-wheat roti straight from the tandoor.', 30.00, 'sada_roti.jpg', 1),
(9, 3, 'Peshawari Beef Shinwari Karahi (Half KG)', 'Tender beef chunks cooked in pure fat, ripe tomatoes, green chillies and coarse black pepper with no heavy artificial spices.', 1350.00, 'shinwari_karahi.jpg', 1),
(10, 4, 'Fresh Mint & Zeera Raita', 'Thick fresh yogurt whisked with roasted cumin seeds, fresh garden mint, and mild green chilies.', 80.00, 'zeera_raita.jpg', 1),
(11, 4, 'Special Imli & Poodina Chutney', 'Tangy tamarind and crushed mint dipping sauce made with Peshawar street recipe.', 60.00, 'imli_chutney.jpg', 1),
(12, 4, 'Crisp Kachumber Salad Bowl', 'Freshly sliced crunchy onions, cucumber, radishes and lemon wedges sprinkled with chaat masala.', 70.00, 'kachumber_salad.jpg', 1),
(13, 5, 'Pakola Ice Cream Soda (Can 250ml)', 'Iconic chilled green ice cream soda, the classic pairing for Pakistani street food.', 90.00, 'pakola.jpg', 1),
(14, 5, 'Chilled Soft Drink (500ml)', 'Choice of Pepsi, 7Up, or Mirinda served ice cold.', 120.00, 'pepsi_500ml.jpg', 1),
(15, 5, 'Mineral Water (500ml Bottle)', 'Chilled purified drinking water.', 60.00, 'mineral_water.jpg', 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price);

-- Seed Default Admin User: admin / admin123
INSERT INTO admins (id, username, password_hash, role) VALUES
(1, 'admin', '$2y$12$u34f1HnSpSFgiQarGAQVLu3pPNHLPCt8yLDHfptVsS0XnxV5Bjlni', 'super_admin')
ON DUPLICATE KEY UPDATE username=VALUES(username), password_hash=VALUES(password_hash);

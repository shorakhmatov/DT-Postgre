-- Тестовые данные для системы управления заказами

-- Вставка категорий
INSERT INTO categories (name, description) VALUES
('Электроника', 'Электронные устройства и гаджеты'),
('Одежда', 'Мужская и женская одежда'),
('Книги', 'Художественная и техническая литература'),
('Спорт', 'Спортивные товары и инвентарь'),
('Дом и сад', 'Товары для дома и садоводства');

-- Вставка продуктов
INSERT INTO products (category_id, name, description, price, stock_quantity, sku) VALUES
-- Электроника
(1, 'Смартфон iPhone 15', 'Новейший смартфон Apple', 89990.00, 50, 'IPHONE15-128'),
(1, 'Ноутбук MacBook Air', 'Ультрабук Apple M2', 129990.00, 25, 'MBA-M2-256'),
(1, 'Наушники AirPods Pro', 'Беспроводные наушники с шумоподавлением', 24990.00, 100, 'AIRPODS-PRO-2'),
(1, 'Планшет iPad Air', 'Планшет Apple с дисплеем 10.9"', 69990.00, 30, 'IPAD-AIR-256'),

-- Одежда
(2, 'Джинсы Levi\'s 501', 'Классические прямые джинсы', 7990.00, 200, 'LEVIS-501-32-34'),
(2, 'Футболка Nike Dri-FIT', 'Спортивная футболка', 2990.00, 150, 'NIKE-DRYFIT-M'),
(2, 'Куртка The North Face', 'Зимняя куртка с утеплителем', 19990.00, 75, 'TNF-WINTER-L'),

-- Книги
(3, 'Чистый код', 'Роберт Мартин - руководство по написанию качественного кода', 2490.00, 80, 'BOOK-CLEAN-CODE'),
(3, 'Война и мир', 'Лев Толстой - классическая литература', 1590.00, 120, 'BOOK-WAR-PEACE'),
(3, 'Алгоритмы и структуры данных', 'Техническая литература по программированию', 3490.00, 60, 'BOOK-ALGORITHMS'),

-- Спорт
(4, 'Кроссовки Nike Air Max', 'Беговые кроссовки', 12990.00, 90, 'NIKE-AIRMAX-42'),
(4, 'Гантели 10кг', 'Разборные гантели', 4990.00, 40, 'DUMBBELLS-10KG'),
(4, 'Коврик для йоги', 'Противоскользящий коврик', 1990.00, 200, 'YOGA-MAT-BLUE'),

-- Дом и сад
(5, 'Кофеварка Philips', 'Автоматическая кофемашина', 34990.00, 20, 'PHILIPS-COFFEE'),
(5, 'Пылесос Dyson V15', 'Беспроводной пылесос', 54990.00, 15, 'DYSON-V15'),
(5, 'Набор садовых инструментов', 'Лопата, грабли, секатор', 3990.00, 50, 'GARDEN-TOOLS-SET');

-- Вставка тестовых заказов (последние 50 для тестирования)
INSERT INTO orders (product_id, quantity, unit_price, total_price, customer_name, customer_email, purchase_time) VALUES
(1, 1, 89990.00, 89990.00, 'Иван Петров', 'ivan@example.com', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 2, 24990.00, 49980.00, 'Мария Сидорова', 'maria@example.com', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(5, 1, 7990.00, 7990.00, 'Алексей Козлов', 'alex@example.com', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(8, 1, 2490.00, 2490.00, 'Елена Волкова', 'elena@example.com', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(11, 1, 12990.00, 12990.00, 'Дмитрий Орлов', 'dmitry@example.com', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(2, 1, 129990.00, 129990.00, 'Ольга Смирнова', 'olga@example.com', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
(13, 1, 1990.00, 1990.00, 'Сергей Попов', 'sergey@example.com', DATE_SUB(NOW(), INTERVAL 7 HOUR)),
(6, 3, 2990.00, 8970.00, 'Анна Лебедева', 'anna@example.com', DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(14, 1, 34990.00, 34990.00, 'Павел Морозов', 'pavel@example.com', DATE_SUB(NOW(), INTERVAL 9 HOUR)),
(4, 1, 69990.00, 69990.00, 'Татьяна Белова', 'tatiana@example.com', DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(12, 2, 4990.00, 9980.00, 'Николай Зайцев', 'nikolay@example.com', DATE_SUB(NOW(), INTERVAL 11 HOUR)),
(9, 1, 1590.00, 1590.00, 'Людмила Крылова', 'ludmila@example.com', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(7, 1, 19990.00, 19990.00, 'Владимир Соколов', 'vladimir@example.com', DATE_SUB(NOW(), INTERVAL 13 HOUR)),
(15, 1, 54990.00, 54990.00, 'Ирина Новикова', 'irina@example.com', DATE_SUB(NOW(), INTERVAL 14 HOUR)),
(10, 2, 3490.00, 6980.00, 'Андрей Федоров', 'andrey@example.com', DATE_SUB(NOW(), INTERVAL 15 HOUR));

-- Добавим еще записей для полноценного тестирования (всего будет около 100 записей)
INSERT INTO orders (product_id, quantity, unit_price, total_price, customer_name, customer_email, purchase_time)
SELECT 
    (FLOOR(RAND() * 15) + 1) as product_id,
    (FLOOR(RAND() * 3) + 1) as quantity,
    p.price as unit_price,
    p.price * (FLOOR(RAND() * 3) + 1) as total_price,
    CONCAT('Клиент ', FLOOR(RAND() * 1000)) as customer_name,
    CONCAT('client', FLOOR(RAND() * 1000), '@example.com') as customer_email,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 168) HOUR) as purchase_time
FROM products p, 
     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION 
      SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION
      SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
      SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
      SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION
      SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30 UNION
      SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35 UNION
      SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40 UNION
      SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45 UNION
      SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50 UNION
      SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54 UNION SELECT 55 UNION
      SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59 UNION SELECT 60 UNION
      SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64 UNION SELECT 65 UNION
      SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69 UNION SELECT 70 UNION
      SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74 UNION SELECT 75 UNION
      SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79 UNION SELECT 80 UNION
      SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84 UNION SELECT 85) as numbers
WHERE p.id <= 15
LIMIT 85;

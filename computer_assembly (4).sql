-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2026 at 09:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `computer_assembly`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(1, 2, 3, 1, '2026-06-16 09:46:27'),
(2, 2, 9, 1, '2026-06-16 09:46:27'),
(3, 2, 13, 2, '2026-06-16 09:46:27'),
(4, 5, 16, 1, '2026-06-16 09:46:27'),
(5, 5, 21, 1, '2026-06-16 09:46:27'),
(6, 5, 26, 1, '2026-06-16 09:46:27'),
(9, 7, 40, 1, '2026-06-16 11:28:18'),
(10, 9, 43, 1, '2026-06-29 14:24:28');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image_url`, `created_at`) VALUES
(1, 'CPU / Processors', 'Desktop and workstation processors from Intel and AMD', 'img/categories/cpu.jpg', '2026-06-16 09:46:27'),
(2, 'Motherboards', 'ATX, Micro-ATX and Mini-ITX motherboards for every socket', 'img/categories/motherboard.jpg', '2026-06-16 09:46:27'),
(3, 'RAM / Memory', 'DDR4 and DDR5 memory kits for gaming and workstations', 'img/categories/ram.jpg', '2026-06-16 09:46:27'),
(4, 'GPU / Graphics Cards', 'Discrete graphics cards from NVIDIA and AMD', 'img/categories/gpu.jpg', '2026-06-16 09:46:27'),
(5, 'Storage', 'SSDs, HDDs and NVMe drives', 'img/categories/storage.jpg', '2026-06-16 09:46:27'),
(6, 'Power Supply Units', 'Fully-modular and semi-modular PSUs from 550W to 1200W', 'img/categories/psu.jpg', '2026-06-16 09:46:27'),
(7, 'PC Cases', 'Mid-tower, full-tower and mini-ITX chassis', 'img/categories/case.jpg', '2026-06-16 09:46:27'),
(8, 'Cooling Solutions', 'Air coolers, AIO liquid coolers and case fans', 'img/categories/cooling.jpg', '2026-06-16 09:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `payment_method`, `shipping_address`, `order_status`, `status`, `notes`, `delivered_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'ORD-20260101-0002', 70998.00, 'UPI', '42 MG Road, Mumbai, Maharashtra 400001', 'delivered', 'delivered', NULL, '2026-01-06 14:00:00', '2026-01-01 10:30:00', '2026-01-06 14:00:00'),
(2, 3, 'ORD-20260215-0003', 139999.00, 'Credit Card', '15 Anna Salai, Chennai, Tamil Nadu 600002', 'delivered', 'delivered', NULL, '2026-02-20 16:00:00', '2026-02-15 09:00:00', '2026-02-20 16:00:00'),
(3, 4, 'ORD-20260310-0004', 48497.00, 'Net Banking', '77 CG Road, Ahmedabad, Gujarat 380009', 'shipped', 'shipped', NULL, NULL, '2026-03-10 12:00:00', '2026-03-13 09:00:00'),
(4, 5, 'ORD-20260401-0005', 183997.00, 'Credit Card', '88 Banjara Hills, Hyderabad, Telangana 500034', 'processing', 'processing', NULL, NULL, '2026-04-01 18:00:00', '2026-04-02 08:00:00'),
(5, 6, 'ORD-20260501-0006', 32498.00, 'COD', '10 Connaught Place, Delhi, Delhi 110001', 'pending', 'pending', NULL, NULL, '2026-05-01 11:00:00', '2026-05-01 11:00:00'),
(6, 2, 'ORD-20260510-0002', 13999.00, 'UPI', '42 MG Road, Mumbai, Maharashtra 400001', 'delivered', 'delivered', NULL, '2026-05-15 12:00:00', '2026-05-10 07:00:00', '2026-05-15 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 22999.00),
(2, 1, 9, 1, 18999.00),
(3, 1, 13, 2, 8999.00),
(4, 2, 16, 1, 139999.00),
(5, 3, 25, 2, 8999.00),
(6, 3, 39, 2, 3499.00),
(7, 3, 30, 1, 4499.00),
(8, 4, 16, 1, 139999.00),
(9, 4, 26, 1, 19999.00),
(10, 4, 36, 1, 8999.00),
(11, 5, 31, 1, 17999.00),
(12, 5, 40, 2, 6999.00),
(13, 6, 21, 1, 13999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `brand`, `description`, `price`, `stock_quantity`, `image_url`, `image`, `rating`, `average_rating`, `review_count`, `created_at`) VALUES
(1, 1, 'Intel Core i9-14900K', 'Intel', '24-core (8P+16E) desktop processor, 6.0 GHz max boost, 125 W TDP, LGA1700.', 45999.00, 30, 'img/products/i9-14900k.jpg', 'i9-14900k.jpg', 4.80, 4.50, 2, '2026-06-16 09:46:27'),
(2, 1, 'AMD Ryzen 9 7950X', 'AMD', '16-core Zen 4 processor, 5.7 GHz max boost, 170 W TDP, AM5 socket.', 49999.00, 25, 'img/products/ryzen9-7950x.jpg', 'ryzen9-7950x.jpg', 4.90, 5.00, 1, '2026-06-16 09:46:27'),
(3, 1, 'Intel Core i5-14600K', 'Intel', '14-core (6P+8E) mid-range desktop processor, 5.3 GHz max boost, LGA1700.', 22999.00, 60, 'img/products/i5-14600k.jpg', 'i5-14600k.jpg', 4.70, 4.50, 2, '2026-06-16 09:46:27'),
(4, 1, 'AMD Ryzen 5 7600X', 'AMD', '6-core Zen 4 processor, 5.3 GHz max boost, 105 W TDP, AM5 socket.', 18999.00, 70, 'img/products/ryzen5-7600x.jpg', 'ryzen5-7600x.jpg', 4.60, 0.00, 0, '2026-06-16 09:46:27'),
(5, 1, 'Intel Core i7-14700K', 'Intel', '20-core (8P+12E) processor, 5.6 GHz max boost, 125 W TDP, LGA1700.', 35999.00, 40, 'img/products/i7-14700k.jpg', 'i7-14700k.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(6, 2, 'ASUS ROG Maximus Z790 Hero', 'ASUS', 'ATX, LGA1700, DDR5, PCIe 5.0, Wi-Fi 6E, 2.5 GbE, USB4.', 52999.00, 15, 'img/products/rog-z790-hero.jpg', 'rog-z790-hero.jpg', 4.80, 0.00, 0, '2026-06-16 09:46:27'),
(7, 2, 'MSI MEG X670E ACE', 'MSI', 'E-ATX, AM5, DDR5, PCIe 5.0 ×2, Wi-Fi 6E, 10 GbE.', 49999.00, 12, 'img/products/meg-x670e-ace.jpg', 'meg-x670e-ace.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(8, 2, 'Gigabyte Z790 AORUS Master', 'Gigabyte', 'ATX, LGA1700, DDR5, PCIe 5.0, Thunderbolt 4, 2.5 GbE LAN.', 39999.00, 20, 'img/products/z790-aorus-master.jpg', 'z790-aorus-master.jpg', 4.60, 0.00, 0, '2026-06-16 09:46:27'),
(9, 2, 'ASRock B650 Steel Legend WiFi', 'ASRock', 'ATX, AM5, DDR5, PCIe 5.0, Wi-Fi 6, 2.5 GbE — best value B650.', 18999.00, 35, 'img/products/b650-steel-legend.jpg', 'b650-steel-legend.jpg', 4.50, 0.00, 0, '2026-06-16 09:46:27'),
(10, 2, 'ASUS TUF Gaming B760M-Plus WiFi', 'ASUS', 'Micro-ATX, LGA1700, DDR5, Wi-Fi 6, 2.5 GbE, reinforced PCIe slots.', 14999.00, 50, 'img/products/tuf-b760m.jpg', 'tuf-b760m.jpg', 4.40, 0.00, 0, '2026-06-16 09:46:27'),
(11, 3, 'G.Skill Trident Z5 RGB DDR5-6000 32GB', 'G.Skill', '2×16 GB DDR5-6000 CL30, Intel XMP 3.0 & AMD EXPO, RGB lighting.', 11999.00, 80, 'img/products/trident-z5-rgb.jpg', 'trident-z5-rgb.jpg', 4.90, 5.00, 1, '2026-06-16 09:46:27'),
(12, 3, 'Corsair Dominator Platinum DDR5-5600 64GB', 'Corsair', '2×32 GB DDR5-5600 CL36, iCUE RGB, aluminum DHX cooler.', 19999.00, 45, 'img/products/dominator-plat.jpg', 'dominator-plat.jpg', 4.80, 0.00, 0, '2026-06-16 09:46:27'),
(13, 3, 'Kingston Fury Beast DDR5-5200 32GB', 'Kingston', '2×16 GB DDR5-5200 CL40, Intel XMP 3.0, low-profile heat spreader.', 8999.00, 100, 'img/products/fury-beast-ddr5.jpg', 'fury-beast-ddr5.jpg', 4.60, 4.00, 1, '2026-06-16 09:46:27'),
(14, 3, 'TeamGroup T-Force Delta DDR4-3600 16GB', 'TeamGroup', '2×8 GB DDR4-3600 CL18, RGB, wide compatibility for older platforms.', 4499.00, 120, 'img/products/tforce-delta-ddr4.jpg', 'tforce-delta-ddr4.jpg', 4.50, 0.00, 0, '2026-06-16 09:46:27'),
(15, 3, 'Crucial Pro DDR5-5600 96GB', 'Crucial', '2×48 GB DDR5-5600 CL46, ECC support, ideal for content creation.', 29999.00, 20, 'img/products/crucial-pro-96.jpg', 'crucial-pro-96.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(16, 4, 'NVIDIA GeForce RTX 4090 24GB', 'NVIDIA', 'Ada Lovelace flagship, 24 GB GDDR6X, DLSS 3, 4K/8K gaming.', 139999.00, 8, 'img/products/rtx4090.jpg', 'rtx4090.jpg', 4.90, 4.50, 2, '2026-06-16 09:46:27'),
(17, 4, 'AMD Radeon RX 7900 XTX 24GB', 'AMD', 'RDNA 3 flagship, 24 GB GDDR6, FSR 3, excellent rasterization.', 89999.00, 12, 'img/products/rx7900xtx.jpg', 'rx7900xtx.jpg', 4.80, 5.00, 1, '2026-06-16 09:46:27'),
(18, 4, 'NVIDIA GeForce RTX 4070 Ti Super 16GB', 'NVIDIA', '16 GB GDDR6X, DLSS 3.5, great 1440p and entry 4K performance.', 69999.00, 20, 'img/products/rtx4070ti-super.jpg', 'rtx4070ti-super.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(19, 4, 'AMD Radeon RX 7800 XT 16GB', 'AMD', 'Best value 1440p GPU, 16 GB GDDR6, FSR 3, open-source drivers.', 44999.00, 30, 'img/products/rx7800xt.jpg', 'rx7800xt.jpg', 4.60, 0.00, 0, '2026-06-16 09:46:27'),
(20, 4, 'NVIDIA GeForce RTX 4060 8GB', 'NVIDIA', '8 GB GDDR6, DLSS 3, Frame Generation, ideal for 1080p gaming.', 29999.00, 50, 'img/products/rtx4060.jpg', 'rtx4060.jpg', 4.50, 4.00, 1, '2026-06-16 09:46:27'),
(21, 5, 'Samsung 990 Pro NVMe 2TB', 'Samsung', 'PCIe 4.0 ×4 M.2, 7450/6900 MB/s read/write, 5-year warranty.', 13999.00, 60, 'img/products/990pro-2tb.jpg', '990pro-2tb.jpg', 4.90, 5.00, 2, '2026-06-16 09:46:27'),
(22, 5, 'WD Black SN850X NVMe 4TB', 'Western Digital', 'PCIe 4.0 ×4 M.2, 7300/7100 MB/s, PS5 compatible, 5-year warranty.', 22999.00, 35, 'img/products/sn850x-4tb.jpg', 'sn850x-4tb.jpg', 4.80, 0.00, 0, '2026-06-16 09:46:27'),
(23, 5, 'Seagate FireCuda 530 NVMe 1TB', 'Seagate', 'PCIe 4.0 ×4, 7300/6900 MB/s, heatsink model available.', 7999.00, 70, 'img/products/firecuda530.jpg', 'firecuda530.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(24, 5, 'Samsung 870 EVO SATA SSD 4TB', 'Samsung', '2.5\" SATA SSD, 560/530 MB/s, excellent for mass storage builds.', 17999.00, 40, 'img/products/870evo-4tb.jpg', '870evo-4tb.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(25, 5, 'Seagate BarraCuda HDD 8TB', 'Seagate', '3.5\" 7200 RPM SATA HDD, 256 MB cache, bulk storage workhorse.', 8999.00, 90, 'img/products/barracuda-8tb.jpg', 'barracuda-8tb.jpg', 4.40, 0.00, 0, '2026-06-16 09:46:27'),
(26, 6, 'Corsair HX1200 Platinum', 'Corsair', '1200 W, 80+ Platinum, fully modular, 10-year warranty.', 19999.00, 25, 'img/products/hx1200.jpg', 'hx1200.jpg', 4.90, 5.00, 1, '2026-06-16 09:46:27'),
(27, 6, 'Seasonic Focus GX-1000 Gold', 'Seasonic', '1000 W, 80+ Gold, fully modular, 10-year warranty, silent fan control.', 15999.00, 30, 'img/products/focus-gx1000.jpg', 'focus-gx1000.jpg', 4.80, 0.00, 0, '2026-06-16 09:46:27'),
(28, 6, 'EVGA SuperNOVA 850 G6 Gold', 'EVGA', '850 W, 80+ Gold, fully modular, 10-year warranty, ECO mode.', 12999.00, 40, 'img/products/supernova-850g6.jpg', 'supernova-850g6.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(29, 6, 'be quiet! Straight Power 11 750W', 'be quiet!', '750 W, 80+ Gold, fully modular, silent wings fan, German engineering.', 10999.00, 45, 'img/products/straight-power-11.jpg', 'straight-power-11.jpg', 4.60, 0.00, 0, '2026-06-16 09:46:27'),
(30, 6, 'Cooler Master MWE 550 Bronze V2', 'Cooler Master', '550 W, 80+ Bronze, non-modular, budget-friendly starter PSU.', 4499.00, 100, 'img/products/mwe550.jpg', 'mwe550.jpg', 4.30, 0.00, 0, '2026-06-16 09:46:27'),
(31, 7, 'Lian Li O11 Dynamic EVO XL', 'Lian Li', 'Full-tower, dual-chamber, supports E-ATX, 420 mm radiator front.', 17999.00, 20, 'img/products/o11-evo-xl.jpg', 'o11-evo-xl.jpg', 4.90, 5.00, 1, '2026-06-16 09:46:27'),
(32, 7, 'Fractal Design Torrent', 'Fractal Design', 'Mid-tower, two 180 mm front fans, open grill, exceptional airflow.', 13999.00, 25, 'img/products/fractal-torrent.jpg', 'fractal-torrent.jpg', 4.80, 5.00, 1, '2026-06-16 09:46:27'),
(33, 7, 'NZXT H9 Elite', 'NZXT', 'Mid-tower, dual-chamber, panoramic glass, 3× 120 mm fans included.', 16999.00, 18, 'img/products/nzxt-h9-elite.jpg', 'nzxt-h9-elite.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(34, 7, 'Corsair 4000D Airflow', 'Corsair', 'Mid-tower ATX, tempered glass, 2× LL120 fans, excellent value.', 7999.00, 50, 'img/products/4000d-airflow.jpg', '4000d-airflow.jpg', 4.60, 0.00, 0, '2026-06-16 09:46:27'),
(35, 7, 'Cooler Master MasterBox Q300L', 'Cooler Master', 'Micro-ATX mini-tower, magnetic dust filter, versatile panel placement.', 3999.00, 70, 'img/products/q300l.jpg', 'q300l.jpg', 4.30, 0.00, 0, '2026-06-16 09:46:27'),
(36, 8, 'Noctua NH-D15 Air Cooler', 'Noctua', 'Dual-tower, dual NF-A15 140 mm fans, 250 W TDP, universal socket.', 8999.00, 55, 'img/products/nh-d15.jpg', 'nh-d15.jpg', 4.90, 5.00, 1, '2026-06-16 09:46:27'),
(37, 8, 'Corsair iCUE H150i Elite Capellix XT', 'Corsair', '360 mm AIO, three 120 mm XT fans, iCUE RGB, Intel & AMD.', 14999.00, 40, 'img/products/h150i-capellix.jpg', 'h150i-capellix.jpg', 4.80, 5.00, 1, '2026-06-16 09:46:27'),
(38, 8, 'ARCTIC Liquid Freezer III 280', 'ARCTIC', '280 mm AIO, A-RGB PWM fans, integrated VRM fan, near-silent.', 8499.00, 35, 'img/products/liquid-freezer3-280.jpg', 'liquid-freezer3-280.jpg', 4.70, 4.00, 1, '2026-06-16 09:46:27'),
(39, 8, 'Thermalright Peerless Assassin 120 SE', 'Thermalright', 'Dual-tower, dual 120 mm ARGB fans, 240 W TDP, best-value air cooler.', 3499.00, 90, 'img/products/peerless-assassin.jpg', 'peerless-assassin.jpg', 4.80, 5.00, 1, '2026-06-16 09:46:27'),
(40, 8, 'Lian Li Uni Fan SL-Infinity 3-Pack', 'Lian Li', '3× 120 mm ARGB fans, daisy-chain, frameless infinite mirror edge.', 6999.00, 60, 'img/products/unifan-sl-infinity.jpg', 'unifan-sl-infinity.jpg', 4.70, 0.00, 0, '2026-06-16 09:46:27'),
(42, 8, 'vdv', 'dbfdb', '', 122.00, 1, NULL, 'C:\\fakepath\\cute_radha_krishna_wallpaper-watermark-676x676.jpg', 0.00, 0.00, 0, '2026-06-16 11:22:30'),
(43, 8, 'a', 'aaa', '', 2000.00, 1, NULL, 'C:\\fakepath\\cute_radha_krishna_wallpaper-watermark-676x676.jpg', 0.00, 0.00, 0, '2026-06-29 11:41:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `verified_purchase` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `comment`, `helpful_count`, `unhelpful_count`, `verified_purchase`, `created_at`) VALUES
(1, 1, 3, 5, 'Absolute beast for content creation', 'Upgraded from a Ryzen 9 5900X — the multi-thread performance jump is insane. Rendering times cut by 40%.', 12, 1, 1, '2026-06-16 09:46:27'),
(2, 1, 5, 4, 'Great performance, runs a bit hot', 'Paired with a 360 AIO and it stays cool under gaming load. Power draw is high but performance is worth it.', 8, 0, 0, '2026-06-16 09:46:27'),
(3, 2, 4, 5, 'Best desktop CPU money can buy', '32 threads on a mainstream platform — incredible. Blender renders fly. AM5 platform future-proofing is a bonus.', 15, 0, 1, '2026-06-16 09:46:27'),
(4, 3, 6, 5, 'The sweet spot CPU', 'Paired with B760M TUF and 32 GB DDR5. Games beautifully and handles video editing without breaking a sweat.', 20, 1, 1, '2026-06-16 09:46:27'),
(5, 3, 2, 4, 'Excellent price-to-performance', 'Gaming performance is right there with the i7 at 70% of the cost. Would buy again.', 10, 0, 0, '2026-06-16 09:46:27'),
(6, 16, 3, 5, 'Future-proof 4K powerhouse', 'Not a single game can stress this card. DLSS 3 Frame Generation is magic — literally doubles framerates.', 30, 2, 1, '2026-06-16 09:46:27'),
(7, 16, 4, 4, 'Amazing GPU but needs a good PSU', 'Pulled 450 W under full load. Make sure you have at least a Gold 1000W PSU. Performance is unmatched though.', 18, 1, 1, '2026-06-16 09:46:27'),
(8, 17, 5, 5, 'Open-source drivers are a game changer', 'Excellent rasterization, FSR 3 is decent. Linux support is miles ahead of NVIDIA. Very happy.', 14, 1, 0, '2026-06-16 09:46:27'),
(9, 20, 6, 4, 'Great 1080p card, limited VRAM', 'Runs everything at 1080p ultra 60+ fps. Just be aware that 8 GB is tight for texture-heavy games.', 11, 2, 1, '2026-06-16 09:46:27'),
(10, 21, 2, 5, 'Fastest SSD I have ever owned', 'Sequential reads are stunning. Boot times are almost zero. Samsung Magician software is excellent.', 25, 0, 1, '2026-06-16 09:46:27'),
(11, 21, 4, 5, 'Perfect OS + games drive', 'Runs cool even without heatsink. 5-year warranty gives peace of mind. Highly recommended.', 17, 0, 1, '2026-06-16 09:46:27'),
(12, 26, 5, 5, 'Silent, efficient, premium', 'Zero-RPM mode means it is completely silent under light load. Cable quality is excellent.', 9, 0, 1, '2026-06-16 09:46:27'),
(13, 31, 3, 5, 'The definitive builder case', 'Dual-chamber layout keeps the cable mess hidden. Fits massive radiators. Build quality is top-tier.', 22, 1, 1, '2026-06-16 09:46:27'),
(14, 32, 6, 5, 'Airflow king', 'Two 180 mm fans in front move serious air. Temps dropped 8°C compared to my old case. Highly recommend.', 16, 0, 1, '2026-06-16 09:46:27'),
(15, 36, 4, 5, 'Best air cooler, period', 'Keeps the i7-14700K under 80°C in an all-core Cinebench run. Incredibly quiet. Worth every rupee.', 28, 1, 1, '2026-06-16 09:46:27'),
(16, 37, 2, 5, 'Gorgeous and effective', 'RGB looks amazing through the glass panel. Temps are great — i9-14900K peaks at 85°C in stress test.', 13, 0, 1, '2026-06-16 09:46:27'),
(17, 38, 5, 4, 'Best budget AIO', 'Outperforms coolers twice the price. VRM fan is a nice touch. A-RGB looks clean.', 7, 0, 1, '2026-06-16 09:46:27'),
(18, 39, 6, 5, 'Crazy value, crazy performance', 'Under ₹3500 and it competes with AIO coolers. ARGB fans are a bonus. No-brainer purchase.', 35, 0, 1, '2026-06-16 09:46:27'),
(19, 11, 3, 5, 'Runs at rated speeds out of the box', 'XMP 3.0 profile engaged on first boot and it just worked at DDR5-6000. Timings are tight for the price.', 11, 0, 1, '2026-06-16 09:46:27'),
(20, 13, 2, 4, 'Solid budget DDR5', 'Stable at 5200 MHz with XMP. No RGB but who needs it inside a closed case? Great value.', 8, 0, 1, '2026-06-16 09:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `city`, `state`, `postal_code`, `country`, `profile_image`, `bio`, `date_of_birth`, `role`, `status`, `created_at`) VALUES
(2, 'rahul_builds', 'rahul@example.com', '$2y$12$8Kd6nTQYGWU9yVHSp./LFegWK6.szVFrRKj2N3.f6JBJ5UHRl9w6e', 'Rahul Sharma', '9876543210', '42 MG Road', 'Mumbai', 'Maharashtra', '400001', 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:46:27'),
(3, 'priya_techie', 'priya@example.com', '$2y$12$8Kd6nTQYGWU9yVHSp./LFegWK6.szVFrRKj2N3.f6JBJ5UHRl9w6e', 'Priya Nair', '9876543211', '15 Anna Salai', 'Chennai', 'Tamil Nadu', '600002', 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:46:27'),
(4, 'vikram_pc', 'vikram@example.com', '$2y$12$8Kd6nTQYGWU9yVHSp./LFegWK6.szVFrRKj2N3.f6JBJ5UHRl9w6e', 'Vikram Patel', '9876543212', '77 CG Road', 'Ahmedabad', 'Gujarat', '380009', 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:46:27'),
(5, 'sneha_gamer', 'sneha@example.com', '$2y$12$8Kd6nTQYGWU9yVHSp./LFegWK6.szVFrRKj2N3.f6JBJ5UHRl9w6e', 'Sneha Reddy', '9876543213', '88 Banjara Hills', 'Hyderabad', 'Telangana', '500034', 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:46:27'),
(6, 'arjun_rig', 'arjun@example.com', '$2y$12$8Kd6nTQYGWU9yVHSp./LFegWK6.szVFrRKj2N3.f6JBJ5UHRl9w6e', 'Arjun Singh', '9876543214', '10 Connaught Place', 'Delhi', 'Delhi', '110001', 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:46:27'),
(7, 'itachi12', 'i@gmail.com', '$2y$10$mS8EMJ7E25P27SjwbY0VTuc7kMS.O7X.OlrBMc/8dbtE3fuJnrXPa', 'Itachi', NULL, NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, 'customer', 'active', '2026-06-16 09:47:23'),
(9, 'admin', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, NULL, NULL, NULL, 'India', NULL, NULL, NULL, 'admin', 'active', '2026-06-16 10:25:26');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `added_at`) VALUES
(1, 2, 16, '2026-06-16 09:46:27'),
(2, 2, 31, '2026-06-16 09:46:27'),
(3, 3, 1, '2026-06-16 09:46:27'),
(4, 3, 26, '2026-06-16 09:46:27'),
(5, 4, 2, '2026-06-16 09:46:27'),
(6, 4, 7, '2026-06-16 09:46:27'),
(7, 4, 12, '2026-06-16 09:46:27'),
(8, 5, 22, '2026-06-16 09:46:27'),
(9, 5, 37, '2026-06-16 09:46:27'),
(10, 6, 18, '2026-06-16 09:46:27'),
(11, 6, 32, '2026-06-16 09:46:27'),
(12, 6, 40, '2026-06-16 09:46:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 11:45 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tradesphere`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Electronics'),
(2, 'Books'),
(3, 'Clothing'),
(4, 'Furniture'),
(5, 'Sports'),
(6, 'Accessories'),
(7, 'Shoes'),
(8, 'Stationery');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `message_type` varchar(50) DEFAULT 'normal',
  `signature` longtext DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `room_id`, `sender_id`, `receiver_id`, `message_text`, `message_type`, `signature`, `is_read`, `created_at`) VALUES
(1, 1, 3, 2, 'Is this still available?', 'normal', NULL, 1, '2026-05-18 06:01:32'),
(2, 1, 2, 3, 'Yes, it is available.', 'normal', NULL, 1, '2026-05-18 07:52:22'),
(3, 1, 3, 2, 'Is the price negotiable?', 'normal', NULL, 1, '2026-05-18 08:14:22'),
(4, 1, 2, 3, 'Yes, we can negotiate.', 'normal', NULL, 1, '2026-05-18 08:14:28'),
(5, 1, 3, 2, 'Buyer offered Rs 1,000.00 for Noise Cancelling Headphones 171', 'offer', 'oVPpghhuuqdPsZaJD6HLUdVFgzxHh2jJtYNxr9PC1tSQqKF3zHoHFxMBxvgrWUsmOujVBpjqm+9mDsbhU0NOXHheDpbJlcv8AWTNk8QF0N1e0mNOEMzFp8f0Fh7vrH0ZDG5PgXu2VEev7c+EeRiZVLEc3i1ITJK6wc2IItFZeaAOpkJm9fb7Bb3X+9rTBJzuji+P+AqQEvckNqE3amd5WyyN9L9nnoj/l3RomVqCKe3WyghK8JW7jaTLl6oL7JLrZGDcdjHUDhQSP+3ugFNa9lDphycmdvz9MuVKs6p9CiNS+iwJLyMDaRYFLlVhZLjE46ouXxC+IrTWpOiRfFAKgA==', 1, '2026-05-18 08:14:38'),
(6, 1, 2, 3, 'Seller accepted offer Rs 1,000.00 for Noise Cancelling Headphones 171', 'accepted', 'hGIhntImw7MoTQCmb8WD3HNJBbsY3KSoKaF+xNmU/fVMxrWDCvujO6GYaB5Op1LXIE6weKbg/A5XkNR92DqU7gsxCT5584kJljlQg9hYZPdtnoN3H3BSCx3qWlVFot4WCfNWtlK9w2Aix+u1kO8XkWkx/kMUdFDS+sl0CQiouSZQ/aJPZh9z7rC22ReMw+9n4OksegDvckMEKpzSYckNFiO2SNyrFQOgv0FRXSTya+YaZ2NTXYXaKwkC2v6MCrUt8cricaC+DLaU2JMjYAg4GEZ6XiGa52H5Fp+647MaY8nPe1NjHmbqR/z9SAJed7i6QUSr6Y8S7mMqQjKdPgiIAA==', 1, '2026-05-18 08:14:49'),
(7, 1, 3, 2, 'Is the price negotiable?', 'normal', NULL, 1, '2026-05-18 08:27:16'),
(8, 1, 2, 3, 'Yes, we can negotiate.', 'normal', NULL, 1, '2026-05-18 08:40:26'),
(9, 1, 2, 3, 'Yes, it is available.', 'normal', NULL, 1, '2026-05-18 08:40:35'),
(10, 1, 3, 2, 'Buyer offered Rs 900.00 for Noise Cancelling Headphones 171', 'offer', 'AVoOMpVa8ENa+tkb6wTM6Ni9R/ecD6ZzPR/xiPAUTGkUpCD3SmIPkQ7u08EcM9JQDNMUvPf3Btk2oqBxOpda3t1rEmwqzoB7BDe2A+RVUOFkxFdr15L7es5J+oUoFFR35ihgIA/adSV2liVtvXs6wB1BfYVEZtFuxruCEx4TQbBQ3qy3eRnJCwGDcg64I5vgBrj05rklDaU1KGT7J1z3nRgdGCm5rzWd5e1DuYr2yTyGaBplxCdKI/peHHOivUY2a13M8aZ5OiIo/as8ppzrmQkx+UviF811Y96lQfShxdBGy51KfXK3FKxYwHDhCAx/y+Oh0dL1qdSGWyaSBq1NFA==', 1, '2026-05-18 08:40:49'),
(11, 1, 2, 3, 'Seller accepted offer Rs 900.00 for Noise Cancelling Headphones 171', 'accepted', 'dLV6nAalrckVRysN9rp3CTS/2dyo/pmL4DxJmfTQ+ikyfllyEgAJy7nqVrXafD2GDNhCGgS7lvD5/S4OKvIY+g7X+MnNhRZ9HI21UgBGJpYmBdry5QpBjWxCsYtdsMoFCwsDEFh2+1sdzBNtkxQTE23RQTf4F2JyKlIrffoQUja7Y5SbthMXvZICgBLe6mdagKWPg04iZvB8We9y7VBC8K2EcWQVRIsMqlAiYdw7MOL8RdovlrkKJLPyBsPESHAJkudF+lOU0A+OIyE4390iGPKaQ9jb3wFzWtOHpi+9CUJ9l4i9TM+30WxQi9S7jYd3ZrsMlNG5OSa4KzapE0ETnA==', 1, '2026-05-18 08:40:54'),
(12, 1, 2, 3, 'so are you interested?', 'normal', NULL, 1, '2026-05-18 10:22:36'),
(13, 1, 3, 2, 'yes', 'normal', NULL, 1, '2026-05-18 10:22:49'),
(14, 1, 2, 3, 'when will you place order?', 'normal', NULL, 1, '2026-05-18 10:23:06'),
(15, 1, 3, 2, 'soon', 'normal', NULL, 1, '2026-05-18 10:23:28'),
(16, 1, 2, 3, 'okay', 'normal', NULL, 1, '2026-05-18 10:30:27'),
(17, 1, 3, 2, 'will let you know later', 'normal', NULL, 1, '2026-05-18 11:08:31'),
(18, 1, 2, 3, 'okay', 'normal', NULL, 1, '2026-05-18 11:08:53'),
(19, 1, 3, 2, 'hello', 'normal', NULL, 1, '2026-05-18 11:16:27'),
(20, 1, 2, 3, 'any updates', 'normal', NULL, 1, '2026-05-18 11:16:43'),
(21, 1, 3, 2, 'test message 1', 'normal', NULL, 1, '2026-05-18 11:19:41'),
(22, 1, 2, 3, 'test 2', 'normal', NULL, 1, '2026-05-18 11:22:21'),
(23, 1, 2, 3, 'test 3', 'normal', NULL, 1, '2026-05-18 11:22:30'),
(24, 1, 3, 2, 'test4', 'normal', NULL, 1, '2026-05-18 11:27:57'),
(25, 1, 3, 2, 'test 5', 'normal', NULL, 1, '2026-05-18 11:28:04'),
(26, 1, 2, 3, 'test 6', 'normal', NULL, 1, '2026-05-18 11:28:22'),
(27, 1, 3, 2, 'test 7', 'normal', NULL, 1, '2026-05-18 11:28:30'),
(28, 1, 3, 2, 'tets8', 'normal', NULL, 1, '2026-05-18 11:40:25'),
(29, 1, 2, 3, 'test completed', 'normal', NULL, 1, '2026-05-18 11:40:37'),
(30, 1, 2, 3, 'test again', 'normal', NULL, 1, '2026-05-18 11:42:23'),
(31, 1, 3, 2, 'test start', 'normal', NULL, 1, '2026-05-18 11:42:31'),
(32, 1, 2, 3, 'test completed', 'normal', NULL, 1, '2026-05-18 11:42:50'),
(33, 2, 3, 2, 'hello', 'normal', NULL, 1, '2026-05-21 04:10:45'),
(34, 2, 3, 2, 'hello', 'normal', NULL, 1, '2026-05-21 04:17:12'),
(35, 2, 3, 2, '//', 'normal', NULL, 1, '2026-05-21 04:17:19'),
(36, 2, 3, 2, '..', 'normal', NULL, 1, '2026-05-21 04:18:05'),
(37, 2, 2, 3, 'hello', 'normal', NULL, 1, '2026-05-21 07:16:24'),
(38, 2, 3, 2, 'Buyer offered Rs 500.00 for Desk Organizer 320', 'offer', 'KfwzxzFUd955nD/blQrqb3ajfGWBUPQwKPzF5W2ndhN4WP9TOmrChs/VRK24dCorjcpbL+ZUiymU66/p2zagPP+7PefcNverrgYu29E9Dlpk5pRBmeNTRf9g7ffoMaUPfK1SGeJuuLjpdrdRyxZVuxC2eBZklpWcpdgKM5/+t68PwuzawvP2YLMI90wEH2L++BC8G3iZ+H8BxkzCnwp7+eguy8WZezv6LKTCI6GGYkDoI9Xw4ftfWrIwhhJWOl8TFzb46o1XZ2ALK+LZfu3i+5/ytGSJEMuFWbAzpd1KlNFf0sM9PQ/z7TTW2/8BDEpRLLgSgmg9sO9ayR8ygJzERw==', 1, '2026-05-21 07:16:49'),
(39, 2, 2, 3, 'Yes, we can negotiate.', 'normal', NULL, 1, '2026-05-21 07:16:57'),
(40, 2, 3, 2, 'Is the price negotiable?', 'normal', NULL, 1, '2026-05-21 07:17:06'),
(41, 2, 2, 3, 'Yes, we can negotiate.', 'normal', NULL, 1, '2026-05-21 07:17:11'),
(42, 2, 3, 2, 'Buyer offered Rs 500.00 for Desk Organizer 320', 'offer', 'f/zt7ng0GEte5FibENJXuBF2ZTTM+p2rpCY5HoyhTf2CF5DvbQpF3EE1/q3Gz8DcP4GyrFE6gE2hcMZvE9NELehXSMTI1Fz4PYPwwsE7OEFw4zBu4817qalZFd8Yq/4O8QkC7vie80Pis6iVeeVp7rGBPS8MMPb68dIjr8qXQRotsYCXp1PiIrkoXJSYVGZNr62ac96DBmpoe85NXValQnMOMquJm+PkbxIL9LyO+DDEA0npZq4lYe3ZNOnfusT8UN53IF6yGtqqSQ50bdalYzqL7nS8EQ8ZrTvPucsFZCdWiQSEdlqJCmJQ+Kyu4Hds/N0XBDiONxD+6KkGyAgCDw==', 1, '2026-05-21 07:17:17'),
(43, 2, 2, 3, 'Seller accepted offer Rs 500.00 for Desk Organizer 320', 'accepted', 'k9Yb8euZQL5pkF/ZFjAEldmw/LWtvXEc2n2KLOKmH5BTHMwyGFN1Gfjjdi7w+0jiM9hnak6z6C7NAvz+OMJd6iSAvFHqYRm6iskcc3gy4AbnHN8AKjHPa0cpACdm6Ea5IYQgW7bHnfXao/LYE9wTBGAMr54oJkogInQQSaykuF98Vrpg9K7Of2Mf5DyAKfguUXlK6SJOsBoZdNF3/vcckIYNKmJSm92jdpuOPSc7g6Gvgld/L5iRRL7Fee33Mkq+2emJMYwl/mABPclgIDjzAC/xAgGGmklhYf8LWGCl7QUK0I+tytMVK+Ox2nq2titnqPM0cneF6efWTCO1yPpJIA==', 1, '2026-05-21 07:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `chat_rooms`
--

CREATE TABLE `chat_rooms` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_rooms`
--

INSERT INTO `chat_rooms` (`id`, `buyer_id`, `seller_id`, `product_id`, `order_id`, `created_at`) VALUES
(1, 3, 2, 7, NULL, '2026-05-18 03:25:28'),
(2, 3, 2, 91, NULL, '2026-05-21 04:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 3, 'Your product is out for delivery: Pen Pack 47', 1, '2026-05-12 06:52:56'),
(2, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:53:19'),
(3, 3, 3, 'Seller is processing your order: Pen Pack 47', 1, '2026-05-12 06:53:47'),
(4, 2, 4, 'Buyer confirmed receiving your product: Pen Pack 47', 1, '2026-05-12 06:54:07'),
(5, 3, 3, 'Seller is processing your order: Pen Pack 47', 1, '2026-05-12 06:54:12'),
(6, 3, 3, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:54:28'),
(7, 3, 3, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:54:32'),
(8, 2, 3, 'Buyer confirmed receiving your product: Pen Pack 47', 1, '2026-05-12 06:54:42'),
(9, 3, 3, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:54:50'),
(10, 3, 4, 'Seller updated your order status to Pending: Pen Pack 47', 1, '2026-05-12 06:54:59'),
(11, 3, 4, 'Seller updated your order status to Pending: Pen Pack 47', 1, '2026-05-12 06:55:22'),
(12, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:55:26'),
(13, 2, 4, 'Buyer confirmed receiving your product: Pen Pack 47', 1, '2026-05-12 06:55:48'),
(14, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:55:55'),
(15, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:56:05'),
(16, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 06:58:40'),
(17, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:00:57'),
(18, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:01:01'),
(19, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:03:14'),
(20, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:03:19'),
(21, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:03:50'),
(22, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:06:13'),
(23, 3, 4, 'Seller marked your order as delivered. Please confirm received: Pen Pack 47', 1, '2026-05-12 07:07:54'),
(28, 2, NULL, 'New offer received: Rs 1,000.00 for Noise Cancelling Headphones 171', 1, '2026-05-18 08:14:38'),
(29, 3, NULL, 'Your offer was accepted for Noise Cancelling Headphones 171', 1, '2026-05-18 08:14:49'),
(33, 2, NULL, 'New offer received: Rs 900.00 for Noise Cancelling Headphones 171', 1, '2026-05-18 08:40:49'),
(34, 3, NULL, 'Your offer was accepted for Noise Cancelling Headphones 171', 1, '2026-05-18 08:40:54'),
(40, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:16:27'),
(41, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:16:43'),
(42, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:19:41'),
(43, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:22:21'),
(44, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:22:30'),
(45, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:27:57'),
(46, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:28:04'),
(47, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:28:22'),
(48, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:28:30'),
(49, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:40:25'),
(50, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:40:37'),
(51, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:42:23'),
(52, 2, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:42:31'),
(53, 3, NULL, 'New message about Noise Cancelling Headphones 171', 1, '2026-05-18 11:42:50'),
(54, 2, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 04:10:45'),
(55, 2, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 04:17:12'),
(56, 2, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 04:17:19'),
(57, 2, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 04:18:05'),
(58, 3, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 07:16:24'),
(59, 2, NULL, 'New offer received: Rs 500.00 for Desk Organizer 320', 1, '2026-05-21 07:16:49'),
(60, 3, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 07:16:57'),
(61, 2, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 07:17:06'),
(62, 3, NULL, 'New message about Desk Organizer 320', 1, '2026-05-21 07:17:11'),
(63, 2, NULL, 'New offer received: Rs 500.00 for Desk Organizer 320', 1, '2026-05-21 07:17:17'),
(64, 3, NULL, 'Your offer was accepted for Desk Organizer 320', 1, '2026-05-21 07:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_user_id` int(11) DEFAULT NULL,
  `buyer_name` varchar(120) NOT NULL,
  `buyer_email` varchar(120) NOT NULL,
  `buyer_phone` varchar(30) NOT NULL,
  `buyer_message` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `transaction_uuid` varchar(120) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'eSewa',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `order_status` varchar(50) NOT NULL DEFAULT 'placed',
  `seller_delivery_status` varchar(50) NOT NULL DEFAULT 'pending',
  `buyer_received` tinyint(1) NOT NULL DEFAULT 0,
  `delivered_at` datetime DEFAULT NULL,
  `buyer_received_at` datetime DEFAULT NULL,
  `seller_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `esewa_ref_id` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `product_id`, `seller_user_id`, `buyer_name`, `buyer_email`, `buyer_phone`, `buyer_message`, `amount`, `quantity`, `transaction_uuid`, `payment_method`, `payment_status`, `order_status`, `seller_delivery_status`, `buyer_received`, `delivered_at`, `buyer_received_at`, `seller_cleared`, `esewa_ref_id`, `created_at`, `updated_at`) VALUES
(1, 3, 85, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 933.00, 1, 'ts_6a00273ec4434_p85', 'eSewa', 'paid', 'processing', 'delivered', 1, '2026-05-10 12:37:06', '2026-05-10 12:36:47', 0, '000F830', '2026-05-10 06:35:42', '2026-05-10 06:52:06'),
(2, 3, 3, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 487.00, 1, 'ts_6a014dfdd8673_p3', 'eSewa', 'paid', 'processing', 'delivered', 1, '2026-05-11 09:34:21', '2026-05-11 09:19:10', 0, '000F976', '2026-05-11 03:33:17', '2026-05-11 03:49:21'),
(3, 3, 86, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 317.00, 1, 'ts_6a014e8018205_p86', 'eSewa', 'paid', 'processing', 'delivered', 1, '2026-05-12 12:39:50', '2026-05-12 12:39:42', 0, '000F977', '2026-05-11 03:35:28', '2026-05-12 06:54:50'),
(4, 3, 86, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 317.00, 1, 'ts_6a0150d44f338_p86', 'eSewa', 'paid', 'processing', 'delivered', 1, '2026-05-12 12:52:54', '2026-05-12 12:40:48', 0, '000F97C', '2026-05-11 03:45:24', '2026-05-12 07:07:54'),
(5, 3, 7, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 1127.00, 1, 'ts_6a0ae8343d002_p7', 'eSewa', 'pending', 'placed', 'pending', 0, NULL, NULL, 0, NULL, '2026-05-18 10:21:40', '2026-05-18 10:21:40'),
(6, 3, 91, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 1061.00, 1, 'ts_6a0eb774a3ed4_p91', 'eSewa', 'pending', 'placed', 'pending', 0, NULL, NULL, 0, NULL, '2026-05-21 07:42:44', '2026-05-21 07:42:44'),
(7, 3, 91, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 500.00, 1, 'ts_6a0ebcad3a3a5_p91', 'eSewa', 'pending', 'placed', 'pending', 0, NULL, NULL, 0, NULL, '2026-05-21 08:05:01', '2026-05-21 08:05:01'),
(8, 3, 91, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 500.00, 1, 'ts_6a0ebce3a1fa0_p91', 'eSewa', 'pending', 'placed', 'pending', 0, NULL, NULL, 0, NULL, '2026-05-21 08:05:55', '2026-05-21 08:05:55'),
(9, 3, 91, 2, 'Harry Potter', 'hellwrld0045@gmail.com', '98348695743895', '', 500.00, 1, 'ts_6a0ebd2daa0c9_p91', 'eSewa', 'paid', 'confirmed', 'pending', 0, NULL, NULL, 0, '000FIKL', '2026-05-21 08:07:09', '2026-05-21 08:07:58');

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_uuid` varchar(120) NOT NULL,
  `status` varchar(50) NOT NULL,
  `raw_response` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_logs`
--

INSERT INTO `payment_logs` (`id`, `order_id`, `transaction_uuid`, `status`, `raw_response`, `created_at`) VALUES
(15, 20, 'ts_69e6dcd99f13b', 'paid', '{\"transaction_code\":\"000EYK6\",\"status\":\"COMPLETE\",\"total_amount\":\"1433.0\",\"transaction_uuid\":\"ts_69e6dcd99f13b\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"kFPrBhMxBW5445IvsisPz5neFCvtC/ZqFxEYAArG5ow=\"}', '2026-04-21 02:12:31'),
(16, 21, 'ts_69e702e711b18', 'paid', '{\"transaction_code\":\"000EYMY\",\"status\":\"COMPLETE\",\"total_amount\":\"614.0\",\"transaction_uuid\":\"ts_69e702e711b18\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"gp7hPDcA0cnNBJ7iDaQ2xnLWYykmmpFRrnZm83+WHn8=\"}', '2026-04-21 04:54:29'),
(17, 1, 'ts_6a00273ec4434', 'paid', '{\"transaction_code\":\"000F830\",\"status\":\"COMPLETE\",\"total_amount\":\"933.0\",\"transaction_uuid\":\"ts_6a00273ec4434\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"uyoEqh2agenW8rWEJ4cRxeb40wYFazRt8DjbDNJecCk=\"}', '2026-05-10 06:38:57'),
(18, 2, 'ts_6a014dfdd8673', 'paid', '{\"transaction_code\":\"000F976\",\"status\":\"COMPLETE\",\"total_amount\":\"487.0\",\"transaction_uuid\":\"ts_6a014dfdd8673\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"hWAo1BLXHRDGs0mMPAM9CEcjEZHqL9lAIXWvjzmXxfM=\"}', '2026-05-11 03:33:37'),
(19, 3, 'ts_6a014e8018205', 'paid', '{\"transaction_code\":\"000F977\",\"status\":\"COMPLETE\",\"total_amount\":\"317.0\",\"transaction_uuid\":\"ts_6a014e8018205\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"3TxRbfHn4FFBpwExyhYojMLt+yCWsJ/695MX6nQ6OX8=\"}', '2026-05-11 03:35:46'),
(20, 4, 'ts_6a0150d44f338', 'paid', '{\"transaction_code\":\"000F97C\",\"status\":\"COMPLETE\",\"total_amount\":\"317.0\",\"transaction_uuid\":\"ts_6a0150d44f338\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"Qvx35eQK+w9ZP76UxLuj4ZG+hlG5u0ni0HO2pqL84XQ=\"}', '2026-05-11 03:45:43'),
(21, 9, 'ts_6a0ebd2daa0c9', 'paid', '{\"transaction_code\":\"000FIKL\",\"status\":\"COMPLETE\",\"total_amount\":\"500.0\",\"transaction_uuid\":\"ts_6a0ebd2daa0c9\",\"product_code\":\"EPAYTEST\",\"signed_field_names\":\"transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names\",\"signature\":\"Wrem0oBEQ8VpGJbOCOJ0oIQM/R2y413pa25O6JRC638=\"}', '2026-05-21 08:07:58');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `seller_email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_condition` varchar(50) DEFAULT NULL,
  `status` enum('available','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `average_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `name`, `category_id`, `price`, `city`, `seller_email`, `contact_number`, `contact_info`, `image`, `description`, `product_condition`, `status`, `created_at`, `average_rating`, `rating_count`) VALUES
(1, 2, 'iPhone 11 698', 1, 197.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'iphone_11.png', 'Selling because no longer needed.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(2, 2, 'Samsung Galaxy S20 150', 1, 309.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'samsung_galaxy_s20.png', 'Good quality product with normal signs of use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(3, 2, 'Dell Inspiron Laptop 648', 1, 487.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'dell_inspiron_laptop.png', 'Neatly kept and available at a good price.', 'Like New', 'available', '2026-04-04 07:11:32', 5.00, 1),
(4, 2, 'HP Pavilion Laptop 737', 1, 388.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'hp_pavilion_laptop.png', 'Good quality product with normal signs of use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(5, 2, 'Wireless Mouse 350', 1, 381.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'wireless_mouse.png', 'Selling because no longer needed.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(6, 2, 'Bluetooth Speaker 312', 1, 316.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'bluetooth_speaker.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(7, 2, 'Noise Cancelling Headphones 171', 1, 1127.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'noise_cancelling_headphones.png', 'Neatly kept and available at a good price.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(8, 2, 'Power Bank 354', 1, 1148.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'power_bank.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(9, 2, 'Smart Watch 604', 1, 1078.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'smart_watch.png', 'A practical item for everyday use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(10, 2, 'USB Keyboard 926', 1, 1882.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'usb_keyboard.png', 'Well maintained and in good condition.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(11, 2, 'Tablet 752', 1, 704.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'tablet.png', 'Used carefully and still works perfectly.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(12, 2, 'Phone Charger 96', 1, 538.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'phone_charger.png', 'Neatly kept and available at a good price.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(13, 2, 'Organic Chemistry Book 327', 2, 293.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'organic_chemistry_book.png', 'In nice condition and ready to use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(14, 2, 'Biology Textbook 824', 2, 134.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'biology_textbook.png', 'Affordable and useful for students.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(15, 2, 'Physics Guide 360', 2, 480.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'physics_guide.png', 'In nice condition and ready to use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(16, 2, 'Math Practice Book 789', 2, 1756.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'math_practice_book.png', 'A practical item for everyday use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(17, 2, 'English Novel 722', 2, 1193.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'english_novel.png', 'Used carefully and still works perfectly.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(18, 2, 'History Notes Book 823', 2, 212.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'history_notes_book.png', 'In nice condition and ready to use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(19, 2, 'Exam Preparation Book 210', 2, 267.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'exam_preparation_book.png', 'Well maintained and in good condition.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(20, 2, 'Programming Basics Book 738', 2, 699.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'programming_basics_book.png', 'Well maintained and in good condition.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(21, 2, 'Lab Manual 100', 2, 1043.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'lab_manual.png', 'In nice condition and ready to use.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(22, 2, 'Dictionary 232', 2, 494.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'dictionary.png', 'Well maintained and in good condition.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(23, 2, 'Research Methods Book 167', 2, 1392.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'research_methods_book.png', 'Well maintained and in good condition.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(24, 2, 'Public Speaking Book 935', 2, 130.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'public_speaking_book.png', 'Neatly kept and available at a good price.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(25, 2, 'Blue T-Shirt 56', 3, 1701.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'blue_t_shirt.png', 'Used carefully and still works perfectly.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(26, 2, 'Black Hoodie 715', 3, 1708.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'black_hoodie.png', 'Used carefully and still works perfectly.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(27, 2, 'Denim Jacket 513', 3, 383.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'denim_jacket.png', 'In nice condition and ready to use.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(28, 2, 'White Shirt 531', 3, 601.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'white_shirt.png', 'Well maintained and in good condition.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(29, 2, 'Casual Pants 549', 3, 1715.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'casual_pants.png', 'Affordable and useful for students.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(30, 2, 'Slim Fit Jeans 697', 3, 1835.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'slim_fit_jeans.png', 'Good quality product with normal signs of use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(31, 2, 'Winter Sweater 680', 3, 1798.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'winter_sweater.png', 'Used carefully and still works perfectly.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(32, 2, 'Track Pants 586', 3, 1924.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'track_pants.png', 'Used carefully and still works perfectly.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(33, 2, 'College Jacket 43', 3, 577.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'college_jacket.png', 'Well maintained and in good condition.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(34, 2, 'Sports T-Shirt 588', 3, 498.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sports_t_shirt.png', 'Neatly kept and available at a good price.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(35, 2, 'Cotton Kurta 494', 3, 1888.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'cotton_kurta.png', 'Affordable and useful for students.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(36, 2, 'Printed Shirt 376', 3, 1435.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'printed_shirt.png', 'Well maintained and in good condition.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(37, 2, 'Study Table 653', 4, 1809.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'study_table.png', 'A practical item for everyday use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(38, 2, 'Office Chair 891', 4, 1906.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'office_chair.png', 'Used carefully and still works perfectly.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(39, 2, 'Wooden Shelf 26', 4, 567.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'wooden_shelf.png', 'Affordable and useful for students.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(40, 2, 'Bedside Table 234', 4, 787.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'bedside_table.png', 'Well maintained and in good condition.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(41, 2, 'Drawer Cabinet 277', 4, 1697.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'drawer_cabinet.png', 'Well maintained and in good condition.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(42, 2, 'Reading Desk 735', 4, 621.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'reading_desk.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(43, 2, 'Plastic Chair 372', 4, 1491.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'plastic_chair.png', 'Used carefully and still works perfectly.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(44, 2, 'Bookshelf 387', 4, 411.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'bookshelf.png', 'Selling because no longer needed.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(45, 2, 'Single Bed Frame 132', 4, 507.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'single_bed_frame.png', 'Used carefully and still works perfectly.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(46, 2, 'Laptop Table 14', 4, 265.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'laptop_table.png', 'Neatly kept and available at a good price.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(47, 2, 'Mirror Stand 125', 4, 1620.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'mirror_stand.png', 'In nice condition and ready to use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(48, 2, 'Storage Rack 106', 4, 695.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'storage_rack.png', 'A practical item for everyday use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(49, 2, 'Football 415', 5, 1708.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'football.png', 'In nice condition and ready to use.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(50, 2, 'Cricket Bat 709', 5, 1442.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'cricket_bat.png', 'Selling because no longer needed.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(51, 2, 'Badminton Racket 504', 5, 1764.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'badminton_racket.png', 'Neatly kept and available at a good price.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(52, 2, 'Basketball 252', 5, 334.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'basketball.png', 'Used carefully and still works perfectly.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(53, 2, 'Yoga Mat 980', 5, 1389.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'yoga_mat.png', 'Well maintained and in good condition.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(54, 2, 'Skipping Rope 32', 5, 511.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'skipping_rope.png', 'Affordable and useful for students.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(55, 2, 'Tennis Ball Set 627', 5, 1637.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'tennis_ball_set.png', 'Affordable and useful for students.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(56, 2, 'Gym Gloves 539', 5, 215.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'gym_gloves.png', 'Selling because no longer needed.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(57, 2, 'Volleyball 52', 5, 128.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'volleyball.png', 'Used carefully and still works perfectly.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(58, 2, 'Dumbbell Pair 462', 5, 898.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'dumbbell_pair.png', 'Selling because no longer needed.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(59, 2, 'Shin Guard 251', 5, 272.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'shin_guard.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(60, 2, 'Sports Bottle 581', 5, 1405.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sports_bottle.png', 'Used carefully and still works perfectly.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(61, 2, 'Backpack 689', 6, 711.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'backpack.png', 'Affordable and useful for students.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(62, 2, 'Laptop Bag 123', 6, 1318.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'laptop_bag.png', 'A practical item for everyday use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(63, 2, 'Wallet 528', 6, 711.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'wallet.png', 'A practical item for everyday use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(64, 2, 'Sunglasses 478', 6, 1448.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sunglasses.png', 'Selling because no longer needed.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(65, 2, 'Wrist Watch 762', 6, 983.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'wrist_watch.png', 'Well maintained and in good condition.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(66, 2, 'Cap 204', 6, 1609.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'cap.png', 'Affordable and useful for students.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(67, 2, 'Belt 901', 6, 901.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'belt.png', 'Neatly kept and available at a good price.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(68, 2, 'Travel Mug 866', 6, 836.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'travel_mug.png', 'Affordable and useful for students.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(69, 2, 'Phone Stand 37', 6, 1439.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'phone_stand.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(70, 2, 'Crossbody Bag 428', 6, 1132.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'crossbody_bag.png', 'Affordable and useful for students.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(71, 2, 'Jewelry Box 844', 6, 1911.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'jewelry_box.png', 'Used carefully and still works perfectly.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(72, 2, 'Scarf 834', 6, 649.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'scarf.png', 'Good quality product with normal signs of use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(73, 2, 'Running Shoes 19', 7, 1600.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'running_shoes.png', 'Selling because no longer needed.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(74, 2, 'Canvas Shoes 610', 7, 1443.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'canvas_shoes.png', 'Neatly kept and available at a good price.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(75, 2, 'Formal Shoes 715', 7, 592.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'formal_shoes.png', 'Used carefully and still works perfectly.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(76, 2, 'White Sneakers 381', 7, 644.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'white_sneakers.png', 'In nice condition and ready to use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(77, 2, 'Sports Shoes 163', 7, 447.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sports_shoes.png', 'In nice condition and ready to use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(78, 2, 'Slip-On Shoes 835', 7, 1925.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'slip_on_shoes.png', 'Well maintained and in good condition.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(79, 2, 'Hiking Shoes 203', 7, 1640.00, 'Pokhara', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'hiking_shoes.png', 'Used carefully and still works perfectly.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(80, 2, 'Sandals 825', 7, 951.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sandals.png', 'In nice condition and ready to use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(81, 2, 'College Shoes 699', 7, 598.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'college_shoes.png', 'A practical item for everyday use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(82, 2, 'Training Shoes 943', 7, 1558.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'training_shoes.png', 'Selling because no longer needed.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(83, 2, 'Casual Sneakers 851', 7, 549.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'casual_sneakers.png', 'Well maintained and in good condition.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(84, 2, 'Flat Shoes 988', 7, 1111.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'flat_shoes.png', 'Neatly kept and available at a good price.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(85, 2, 'Notebook Set 406', 8, 933.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'notebook_set.png', 'Used carefully and still works perfectly.', 'Used', 'available', '2026-04-04 07:11:32', 5.00, 1),
(86, 2, 'Pen Pack 47', 8, 317.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'pen_pack.png', 'Well maintained and in good condition.', 'New', 'available', '2026-04-04 07:11:32', 5.00, 2),
(87, 2, 'Drawing Book 595', 8, 381.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'drawing_book.png', 'Good quality product with normal signs of use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(88, 2, 'Scientific Calculator 330', 8, 1114.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'scientific_calculator.png', 'Good quality product with normal signs of use.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(89, 2, 'Geometry Box 528', 8, 508.00, 'Butwal', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'geometry_box.png', 'A practical item for everyday use.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(90, 2, 'Sticky Notes Pack 218', 8, 487.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'sticky_notes_pack.png', 'In nice condition and ready to use.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(91, 2, 'Desk Organizer 320', 8, 1061.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'desk_organizer.png', 'Good quality product with normal signs of use.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(92, 2, 'Marker Set 525', 8, 1801.00, 'Bhaktapur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'marker_set.png', 'Affordable and useful for students.', 'Used', 'sold', '2026-04-04 07:11:32', 0.00, 0),
(93, 2, 'Clipboard 452', 8, 1595.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'clipboard.png', 'Well maintained and in good condition.', 'New', 'available', '2026-04-04 07:11:32', 0.00, 0),
(94, 2, 'File Folder Set 213', 8, 1289.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'file_folder_set.png', 'Well maintained and in good condition.', 'Used', 'available', '2026-04-04 07:11:32', 0.00, 0),
(95, 2, 'Journal Book 257', 8, 1433.00, 'Kathmandu', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'journal_book.png', 'Selling because no longer needed.', 'Good', 'available', '2026-04-04 07:11:32', 0.00, 0),
(96, 2, 'Whiteboard Kit 162', 8, 614.00, 'Lalitpur', 'skkhanal45@gmail.com', '9860678556', 'skkhanal45@gmail.com', 'whiteboard_kit.png', 'Affordable and useful for students.', 'Like New', 'available', '2026-04-04 07:11:32', 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_offers`
--

CREATE TABLE `product_offers` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `offer_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
  `buyer_signature` longtext DEFAULT NULL,
  `seller_signature` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_offers`
--

INSERT INTO `product_offers` (`id`, `product_id`, `buyer_id`, `seller_id`, `offer_amount`, `status`, `buyer_signature`, `seller_signature`, `created_at`, `responded_at`) VALUES
(1, 7, 3, 2, 1000.00, 'accepted', 'oVPpghhuuqdPsZaJD6HLUdVFgzxHh2jJtYNxr9PC1tSQqKF3zHoHFxMBxvgrWUsmOujVBpjqm+9mDsbhU0NOXHheDpbJlcv8AWTNk8QF0N1e0mNOEMzFp8f0Fh7vrH0ZDG5PgXu2VEev7c+EeRiZVLEc3i1ITJK6wc2IItFZeaAOpkJm9fb7Bb3X+9rTBJzuji+P+AqQEvckNqE3amd5WyyN9L9nnoj/l3RomVqCKe3WyghK8JW7jaTLl6oL7JLrZGDcdjHUDhQSP+3ugFNa9lDphycmdvz9MuVKs6p9CiNS+iwJLyMDaRYFLlVhZLjE46ouXxC+IrTWpOiRfFAKgA==', 'hGIhntImw7MoTQCmb8WD3HNJBbsY3KSoKaF+xNmU/fVMxrWDCvujO6GYaB5Op1LXIE6weKbg/A5XkNR92DqU7gsxCT5584kJljlQg9hYZPdtnoN3H3BSCx3qWlVFot4WCfNWtlK9w2Aix+u1kO8XkWkx/kMUdFDS+sl0CQiouSZQ/aJPZh9z7rC22ReMw+9n4OksegDvckMEKpzSYckNFiO2SNyrFQOgv0FRXSTya+YaZ2NTXYXaKwkC2v6MCrUt8cricaC+DLaU2JMjYAg4GEZ6XiGa52H5Fp+647MaY8nPe1NjHmbqR/z9SAJed7i6QUSr6Y8S7mMqQjKdPgiIAA==', '2026-05-18 08:14:38', '2026-05-18 13:59:49'),
(2, 7, 3, 2, 900.00, 'accepted', 'AVoOMpVa8ENa+tkb6wTM6Ni9R/ecD6ZzPR/xiPAUTGkUpCD3SmIPkQ7u08EcM9JQDNMUvPf3Btk2oqBxOpda3t1rEmwqzoB7BDe2A+RVUOFkxFdr15L7es5J+oUoFFR35ihgIA/adSV2liVtvXs6wB1BfYVEZtFuxruCEx4TQbBQ3qy3eRnJCwGDcg64I5vgBrj05rklDaU1KGT7J1z3nRgdGCm5rzWd5e1DuYr2yTyGaBplxCdKI/peHHOivUY2a13M8aZ5OiIo/as8ppzrmQkx+UviF811Y96lQfShxdBGy51KfXK3FKxYwHDhCAx/y+Oh0dL1qdSGWyaSBq1NFA==', 'dLV6nAalrckVRysN9rp3CTS/2dyo/pmL4DxJmfTQ+ikyfllyEgAJy7nqVrXafD2GDNhCGgS7lvD5/S4OKvIY+g7X+MnNhRZ9HI21UgBGJpYmBdry5QpBjWxCsYtdsMoFCwsDEFh2+1sdzBNtkxQTE23RQTf4F2JyKlIrffoQUja7Y5SbthMXvZICgBLe6mdagKWPg04iZvB8We9y7VBC8K2EcWQVRIsMqlAiYdw7MOL8RdovlrkKJLPyBsPESHAJkudF+lOU0A+OIyE4390iGPKaQ9jb3wFzWtOHpi+9CUJ9l4i9TM+30WxQi9S7jYd3ZrsMlNG5OSa4KzapE0ETnA==', '2026-05-18 08:40:49', '2026-05-18 14:25:54'),
(3, 91, 3, 2, 500.00, 'expired', 'KfwzxzFUd955nD/blQrqb3ajfGWBUPQwKPzF5W2ndhN4WP9TOmrChs/VRK24dCorjcpbL+ZUiymU66/p2zagPP+7PefcNverrgYu29E9Dlpk5pRBmeNTRf9g7ffoMaUPfK1SGeJuuLjpdrdRyxZVuxC2eBZklpWcpdgKM5/+t68PwuzawvP2YLMI90wEH2L++BC8G3iZ+H8BxkzCnwp7+eguy8WZezv6LKTCI6GGYkDoI9Xw4ftfWrIwhhJWOl8TFzb46o1XZ2ALK+LZfu3i+5/ytGSJEMuFWbAzpd1KlNFf0sM9PQ/z7TTW2/8BDEpRLLgSgmg9sO9ayR8ygJzERw==', NULL, '2026-05-21 07:16:49', NULL),
(4, 91, 3, 2, 500.00, 'accepted', 'f/zt7ng0GEte5FibENJXuBF2ZTTM+p2rpCY5HoyhTf2CF5DvbQpF3EE1/q3Gz8DcP4GyrFE6gE2hcMZvE9NELehXSMTI1Fz4PYPwwsE7OEFw4zBu4817qalZFd8Yq/4O8QkC7vie80Pis6iVeeVp7rGBPS8MMPb68dIjr8qXQRotsYCXp1PiIrkoXJSYVGZNr62ac96DBmpoe85NXValQnMOMquJm+PkbxIL9LyO+DDEA0npZq4lYe3ZNOnfusT8UN53IF6yGtqqSQ50bdalYzqL7nS8EQ8ZrTvPucsFZCdWiQSEdlqJCmJQ+Kyu4Hds/N0XBDiONxD+6KkGyAgCDw==', 'k9Yb8euZQL5pkF/ZFjAEldmw/LWtvXEc2n2KLOKmH5BTHMwyGFN1Gfjjdi7w+0jiM9hnak6z6C7NAvz+OMJd6iSAvFHqYRm6iskcc3gy4AbnHN8AKjHPa0cpACdm6Ea5IYQgW7bHnfXao/LYE9wTBGAMr54oJkogInQQSaykuF98Vrpg9K7Of2Mf5DyAKfguUXlK6SJOsBoZdNF3/vcckIYNKmJSm92jdpuOPSc7g6Gvgld/L5iRRL7Fee33Mkq+2emJMYwl/mABPclgIDjzAC/xAgGGmklhYf8LWGCl7QUK0I+tytMVK+Ox2nq2titnqPM0cneF6efWTCO1yPpJIA==', '2026-05-21 07:17:17', '2026-05-21 13:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `product_ratings`
--

CREATE TABLE `product_ratings` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `buyer_user_id` int(11) NOT NULL,
  `seller_user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ratings`
--

INSERT INTO `product_ratings` (`id`, `order_id`, `product_id`, `buyer_user_id`, `seller_user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 1, 85, 3, 2, 5, '', '2026-05-10 06:52:00'),
(2, 2, 3, 3, 2, 5, '', '2026-05-11 03:34:15'),
(3, 3, 86, 3, 2, 5, '', '2026-05-12 06:54:46'),
(4, 4, 86, 3, 2, 5, '', '2026-05-12 06:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_views`
--

INSERT INTO `product_views` (`id`, `user_id`, `product_id`, `category_id`, `viewed_at`) VALUES
(1, 3, 12, 1, '2026-04-18 15:10:53'),
(2, 3, 95, 8, '2026-04-21 02:10:26'),
(3, 2, 96, 8, '2026-04-22 10:26:07'),
(4, 2, 95, 8, '2026-05-21 04:17:39'),
(5, 2, 3, 1, '2026-04-22 10:27:53'),
(6, 3, 87, 8, '2026-04-22 10:50:54'),
(7, 3, 88, 8, '2026-04-22 14:43:12'),
(8, 5, 96, 8, '2026-04-22 14:59:45'),
(9, 3, 85, 8, '2026-05-11 03:35:08'),
(10, 3, 86, 8, '2026-05-11 03:45:15'),
(11, 3, 7, 1, '2026-05-18 08:16:38'),
(12, 3, 91, 8, '2026-05-21 04:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `signatures`
--

CREATE TABLE `signatures` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `signed_data` longtext NOT NULL,
  `signature` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signatures`
--

INSERT INTO `signatures` (`id`, `user_id`, `action_type`, `related_id`, `signed_data`, `signature`, `created_at`) VALUES
(1, 2, 'product_created', 2, '{\"user_id\":2,\"product_id\":2,\"action\":\"product_created\",\"product_name\":\"tv\",\"condition\":\"Like New\",\"timestamp\":\"2026-04-02 12:37:58\"}', 'gGuBSfeZjLB0+Tpgxk1EeJsG8DeOLlxIPq64glWtyLfZb0ZqRASMYbsOOAL2quYN5PcGY/2j9Qsd5u3p8Rl/xmRrS5sKE3bODe93I8YSsjvofX1UrLaGuQKI8b1th+MLDGBrW3VEYH0To3E6D0Q7+REVLCcT+gaqbxUsrcvqYy+dZEdgCVb4tOkwkAQyo9uzBQT047XkzGHtHbN4WiLG3wjfcvsx/b5MoBNtx9rL1ENsYtHN+rkKAHkhzNk/KGzVdeMsDHGu8O6K97XG2Uh/wFD2KmydfkjQIhdyr6tO3e02gkJ/Z0WOKOwz14jT+cxHD7oMntmYzA8xDg74c6xWFQ==', '2026-04-02 10:37:58'),
(2, 2, 'product_status_update', 2, '{\"user_id\":2,\"product_id\":2,\"old_status\":\"available\",\"new_status\":\"sold\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-02 12:58:34\"}', 'L3GWN0DkAczoNzNnTWfNPT3ns3HzzmznFZbMqpfXOI+7xlDdeCLQOtKzMsfxeNdDaETjkGEb9tEaTPUNii/hB/tUPSaomInJJ7F1gFV8oPMe2omSzjieSaNqd+MtERkjAvpPHNrENLoqo9HCZwK5VS0PH0LZpRI/RR7uHaPGnxy9T0Xpw2zYzZLbUsYHnUrEeBpg9HqXNb3mcdEG/Jts/xD82ia56avnr1CGsVc+tiiNCDHNFxvj+m89nq/8wVp2r3nfvfgy+mniHGD0ZvEFsiIAS8lr7oU1ZBcBoH9aDTE/r16izTakXlUOftiyzskvxyxtXYBu3J66dvHfYEjXcg==', '2026-04-02 10:58:34'),
(3, 2, 'product_status_update', 2, '{\"user_id\":2,\"product_id\":2,\"old_status\":\"sold\",\"new_status\":\"available\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-02 13:00:55\"}', 'EdGxbnlSJIaVEY6svwbYcBUeVEijJRncVfwSjKrbksxSN4HpCxRGHRqH6taM4DhUwu/l+6XbneMIEIyT9S5rRB989PieZe4+aRl9kp/dhhXddXQiWhFNFlWKot/nXN8oY5skezvDJi6PJWjyvaFtXCYbVkctWHgDhWHd+pny06CW1to39Hvlw346NLvFPtVeLHA8WnFgbAEsb4GZNfobGtEAiwu26LoarYiRhJGnNWLQPrpGdVZDtBnWeG/VzXuzvkZ3CxYlgQg1dQE2iSTquMvvyzi2x2sLps4jWRYFzFbOq0qbx3jK6IkPCsvbLvM476/wRX/xQubqXfpJ4ow7kg==', '2026-04-02 11:00:55'),
(4, 2, 'product_status_update', 2, '{\"user_id\":2,\"product_id\":2,\"old_status\":\"available\",\"new_status\":\"sold\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-02 13:01:06\"}', 'KbnzogTzrfvaZc99oD+bz+olytO2bxUA6O9T6YnBlXX6+40KMlElLYC1VfnBoQaPCZgqGM89KNHEnKVuXUZaPcSE1KDuzZ/yfO9L4PDCeWbrK9ejm07VIz0NWCdZpURfkSK7A45C1ew/rM/EtLHGEdyfqojbSlYzbUee00TZkBJvbpaR635JYM59T7HlOcOnm7tJaYAZ35dEpEuuJTBpBLiUxsFS5oXg4IIS12oFVKeYv1Oe+bQURObXzWRlF3+plnt6TXj8A3HONHt/fKpLYF8QqMS/B+ONvX1OdwzjDzWv0Qz2S7KoFF3m2/swna3mPIS4ChUN+yWHcnaZG0izVg==', '2026-04-02 11:01:06'),
(5, 2, 'seller_delivery_status_update', 12, '{\"user_id\":2,\"order_id\":12,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:31:56\"}', 'DZ1TVZ0NgUl5AHez83RQUsghYz6CDR3s0eFZqvNZebglGqqbtUioyKEHgvt7+X1tEg1+WVtY2JN7wvH+AfxatNBfBi13qfz6ZGwTc17X1sWxhxreqK+61zzVWX0xQajjHc39Sdly4PSK4T+i0XZWVP5AN/v3iEIgVcUgokxb+JfDg9ZUbU0HTpey6Kar+LXcodRXH3GOPXt/9r9owq65p/xGDedCovVq7N7scZrL43ZDJEeVr5jX9a1Afp4TRF59ZC9ArVNqB3r1BHkzn/FsjpffWdVNcogeyiStdsknI/Yd2E7aHZrnpWVl5liutVEzWdcOBFjiFa/DAA34UuDfKw==', '2026-04-06 09:31:57'),
(6, 2, 'seller_delivery_status_update', 12, '{\"user_id\":2,\"order_id\":12,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:32:28\"}', 'ft/ne0p9ikdcUJSyf/NLB7gpTgwHEomUupowyGvkl2ZPImBtcxIrn6UiJTvCqDxjCKqOVMhB1ONMbLKGQ++NqCEOQEOjpIdu38pHNC8wL+JM6a4Lj4U3UsVPEVBTrkZJ1cgKfxTFEN/i2s0F6CgUmd9+1zFY3anVHJwn1JYNQwiFMWWOVGOgxHeGpqfjKj9vVvwzP1DUkrBq64r4pxUDj2fYW9SokiEfAm7jbXvphYtFo8m5GCKENx2A3aj1upLAl1sOiF260ISdd5BTMjDdtItQbQuAocHP1sq2TIb0n5ks17SscRXn0wNpBlaZckkN7/QDikX4Qi+6qLOAAjwAJQ==', '2026-04-06 09:32:28'),
(7, 2, 'seller_delivery_status_update', 13, '{\"user_id\":2,\"order_id\":13,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:52:41\"}', 'TuSJL+rG+5HOO5cmKnXK05H1UjpuBG+DWx77onTXT94oGRY5m6VXEQQ5vLNPTG1jUDG56yIMho7F2etylQA6ZgGuTqn10+WRfI6DQJv+bOChXacnj3Z96K+MERtyrrL0qmR1D5gmOuenmCfzz9CD2+VvLSFgcJt2XNXS//kcsvrqprzBja6BPm3VvUGkYbW0yfyS+jGOD3G18Fopf9KOHnyjlPCgkPxkpuJ7vsE7DBuSDRS/uL4lWWgYwg9atswUJUHiVWrSrVvH2n2mm1VTs3m1BH1iB6Thuw+gR/NRCAJoLMhmGlbIGXEG0oqNd4qOFqqzzxznX6fVsteqU1Rl7Q==', '2026-04-06 09:52:41'),
(8, 2, 'seller_delivery_status_update', 13, '{\"user_id\":2,\"order_id\":13,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:52:51\"}', 'X4JVN9VDjm86er51KuJ/JY9qduvnuD0KuWkU/kibRwYDUaGVDRfSKv7E3ir82A/SsyXezeUGsgYkS6+bVCDB3tm3m19NAok2JQ0z6IL1ckwvDvVbr/5qN9pMsdkp3Q+I9kNFU5sNXdea+YTnHM92BDzTYC8H4q0OiXNrc/IST4DHeUgxnoz0QisxAUOzaAgb9R76/D4ZX70feriksGlQCVVCHEkZXrYRmeB1GoC6SvVFzttn+ROIUkBU9MKm5C/j278qZVLDBnStnjkj0ewJUsLD01csEpFZAmNFljGTOslhQlmQS9It9aGTS6bfyvw8tHc47BcIhC0p362quZWwLw==', '2026-04-06 09:52:51'),
(9, 2, 'seller_delivery_status_update', 14, '{\"user_id\":2,\"order_id\":14,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:56:43\"}', 'G3mrhFK6ps8dt9eiIdXQqKgpambfLqgLO8G7YtPc0K1AkCtyMsu4D5VuPOut9d8wecNDD3F5xl7NscG6XQVerfCFcWWq7sshQtNZuJG3/toDeEfZVb1paoEV5RsL/h8DvQp9rkALvRV7Dy7t3ClzFZnm27cRg/R0Jug6EViWiWUGL4qkHsr5HeSEbaXiffKtTb9OUCNeuJeCEvZBt6+CrTfKlyu6hNEzhuS/oOHYSLXYX9ih5gugPbFtKmg1Ffep6EWHNCBtdpeCjir9fs08JTguQZckwuiUJTyeVb1rYMmozOMsU6XvBSkm8nwb5i/LUWtmH1WbzOoUIfVUVUzt1A==', '2026-04-06 09:56:43'),
(10, 2, 'buyer_confirmed_received', 14, '{\"user_id\":2,\"order_id\":14,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-04-06 11:56:54\"}', 'JWfgoNTCMysB4vwtxAXAuoPdPRT1iRoggh7AUPAOB0DHxQcChTxvDSF1VCblwF4u770zjrtV+c77jityAnsddJeZCQyGKEhD2vuF2E+9nQLzh/GEGqTdvO71k0bUEeFF8FL4FHnJyVUDJzOGIy9Qiq1mepDasZ+O5wNPcQSO4C3d4IKaIHsqUCPVC8SMQnrBUoXuKONGHkBXbc9VG1StcCAFHZPVDBtFwUUxAaDe8YqgOC4QsFo4p+dALGq5F4NBSKNREkyX2emcdbjebuf9qpeCj+aX/Z+Y2iIWo5AMZ5jXjVJvNNdUm09ZbheDzSyXMLz3J05ARepufwWrGUzjQg==', '2026-04-06 09:56:54'),
(11, 2, 'seller_delivery_status_update', 13, '{\"user_id\":2,\"order_id\":13,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 11:57:22\"}', 'BeevHtYEat2EP8D+Oolhq/p+4b8qL0ieqjbmwYW2U+wHTn6tkH95IFEkcDIzZSn8l5PtSJEPow0rN8NLltaWXjSChgGsiUyzAYVh6PrkcGryzKG6l0YUSZ7+EBPnRwvpNcpEshuQcngwjiHEDtgPTU3Ut36+piOQM1In3bGDZg9BKvtXvX+UQ+jxk8BiSKi9KW+mgitZ+IPqMFa9a1MGyL8PY6yCoMeTmzeXF5DVtWlRlC+1BuU5DOILi2b8ozNxkg96szDPUTus497mg6xKWsG2hBaTe1Na5EHLMIP8T3V30KdieObpFzpRbNPS+POI1S1+2NNnQqhZtNHuEoGyLQ==', '2026-04-06 09:57:22'),
(12, 2, 'seller_delivery_status_update', 13, '{\"user_id\":2,\"order_id\":13,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 12:04:41\"}', 'TnAV7mx9GTEePFp0iUP+BcmNm6cupKcK+ef3gfQTt7FQkC5NiKCZoVDR+Ekt1mJrJDlSJT6KxD/0/pRiwO95kjP6Oa/kb5iy8Vn6WgJ9ZGxZihGU/eLTOw/wSczYzaraB75nKaeQ3GwEvKKs0xp7agftd13zA26fP88aPZajf5L54WqAQXkvxMQoyqjhTiZExWOwfjv2vJRNy79QRJbK3dQ/wfSaBXzYj2gC4IS8ks3te1LffHrVtZCniEiCKM2Bl5Ag1ytVO3Cqr6qAxJ9hIpK1OY2x4rCGqeQP1hquDZmIDQKhzksS0GQKOKjG0sb1evVHa1Vq5M8qvLgRurTzLQ==', '2026-04-06 10:04:41'),
(13, 2, 'buyer_confirmed_received', 13, '{\"user_id\":2,\"order_id\":13,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-04-06 12:04:50\"}', 'ZORUlwWGGN6HteGMFGCiOc+U0L2WZheqtXLW5wDd4De7VhBZr5sqOqO+IV7Y6hcuoL0/yLxtYFH1ZVPvQqVgPUGIE0g0/Bf2mzM9vFelyBhVmPvgMTZpmIoGPmosygUhBKht9f3i9QpFglf4zTOePT6GooOU1XPzY28722dkgOhZmXir+lFOw4GSTdulwgErqMDIMAXhlvfSwqldr/TNU4K4VSTh9hjhDRZ8+6tW46txAiD16cqeENAAz7eUuC5gunghOT6PpRptt/PQbfWjNJyNxYg/hgQ1OPILRqPruYhn6N3/YWEVD7DU5lXSIbLn0vsh09q7ig2wLdQLOL/ucw==', '2026-04-06 10:04:50'),
(14, 2, 'product_status_update', 92, '{\"user_id\":2,\"product_id\":92,\"old_status\":\"available\",\"new_status\":\"sold\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-06 12:16:22\"}', 'DjB0+Q18ued2GiOxEzgp1F8eQkU8+Ef3YMMrtpWIcNWVLwhH8iv11l/oR/CCaLx48C8n7+Ue7sylunlVS03nR0qZuz5eTlEOlqUarCcGNLDFb/LmrntyIlqyC0VMIf5yGshsd18pByQKZXJGYibT6GdiPFrWABEpNGM8EDJtx9mmJ/Jm8GpA3xX5G8FozcC+E3Shddt95rZEBrk1bDbFaLQpVmsZxiGIo/c1YklFCGID+VaDMK6gSVuzstoI/YfoxpL1Dss2JWffriewa80gfBhomAgMcY+e3HejE84e4ZCzjegMvdRV3V7NSJMPpuAi7KUx+YZg1wrNbE+oJfvF4w==', '2026-04-06 10:16:22'),
(15, 2, 'seller_delivery_status_update', 15, '{\"user_id\":2,\"order_id\":15,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 12:59:43\"}', 'ER9GyVWY4mYyxU4Z/d117zAOoBexWD2b55RJ1AZZRaNfekygFiWvT+k36Jti9UL6czvpu6c5BPgJ+4DSrlSF4arWb4vaP5tY071P4eB7zBnG+wgAUKB5athejdQC81bC1EuD0vJOpOfKreoYtc65td+qYDr+Bw/7lYXSErLB4nE5NZ5YXEY7YyN8vjQphT+as8C2wTZuKJN4biSuPRjIMhRMN15t3mD55tRxrvxRZPaKHPJemBmJHIaF9FDggUBfafC3yLzxIB8GYiEvnmeLniHJNQudctiJUO04vT0ab8G7tuwQ39CYSQ5a3wckHXgv0sM2L8SpUhddTSuDXsRHJQ==', '2026-04-06 10:59:43'),
(16, 2, 'seller_delivery_status_update', 15, '{\"user_id\":2,\"order_id\":15,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 13:00:08\"}', 'Mvv40GYrBcOO+P/g5Rt3l3/Lh5d2r+jqQ60ghjG9XM7MaFOvGb8gPsqg/GQKb9iGaOKh6R81v6w5FdXKnHzF06HjehtIcO7MsOOi8mD3guWjS8WRQIiZhwIrNwR/J7KCp8jZp/b+dLzEzXQQ5gG8BeZKJGQjicqYA/MFK2LU74Zf7MA0IOPRPwHNSV16/AAMe1py6szo2HxZOsm3YJvv8Pgue2Ptnrc/HQaxyrJtxdxZKrLEDbroer8HLgCxb4xPFlaiGIL5Q5cYBD7+IriOa1v+i1uH0e/iZ1L1cE14oRYaW8fhNscYfZ1siOyCFUkRr1LAjiWKhoa7cUDQxj6rMw==', '2026-04-06 11:00:08'),
(17, 2, 'seller_delivery_status_update', 15, '{\"user_id\":2,\"order_id\":15,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 13:04:23\"}', 'ndJ5GKbodRle+UDMtSxX4MlSW51bLgZyz2eTZUdCuWUvbhLmwyYNtajwngCTfhGHTk1GQfse7o6C3ivbE/WYIf1F+btOXqN2xd/toIJ8vbp/wdrOFWojxP8momfYmJBfmDZcid5q+gmG6IYVyAH7l/QLQBLwTR/S/YUXJy38XoEiZ1iqQjHlaVkMGLjbs/Sdmh7CoHI45ANJ/TS/oWp90J2Kj8UwlJQUHKoJ28xoAGsyVUESx3CjCK7mKAzBD4Q8VJPiFZc72a3TOPXDF1SyZYCkRIDLMfFZ5xvbQ0FYlZx5DtPhhSjkPPoIMSzyQXEGNf0dfgiwes524NFWgx41DA==', '2026-04-06 11:04:23'),
(18, 2, 'seller_delivery_status_update', 16, '{\"user_id\":2,\"order_id\":16,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-06 17:23:00\"}', 'WI8BatP69LwDFrf/zempIqR5Alqu6pIJ6oXSbKVF64VJceFnyHNhoCmsP6ZmX4TL5JwTGNSUwxv+RGNnJIxAsvrwjY8u2CGlpecOJzCGuj0Rrv6h2/jmvGWScBxtr6ZHPxIik14/OO1dZAQ0XAQUOlW1LJiSwiwoM5x+NFKnGuw+3OwjJTpxstxDMoJz+h653xUcvdM+Z0iHSHihTfogp3wZW1PSlPnYiKGoKV0lXaCi1n0Vnzabbbr0WmFw8B9plaesAxWf1GMpYMor5h3PgN5JwU5yi9usZ6yXedBUcfepgREA1HRhizXEkuAN/0+eoRJCbzjxxOeBkMbkh5lGQQ==', '2026-04-06 15:23:00'),
(19, 2, 'buyer_confirmed_received', 16, '{\"user_id\":2,\"order_id\":16,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-04-06 17:23:08\"}', 'e7uYYZO8i65kBOI5YhKWgypzJTYBzQI0tuhYdXaUWVwGGyVQiItL73PkOerszcsMg0bdI65umULlR+rRP2EIargqz4gJsLS4SKCOqjLpS4GQHi4noYntcBJ0Dx0Ucv27paTxPPNrC7F+xgwNYeIQ0uWOzxElbvy2/aQokcfeCOqb+Y6+t+aYEjZ/y29vp9XpFCeL62pHlaGAkHOXrS28C0XJBfzo8sO0tRQo1Gg2zt8Eb7tn59mg6R9ck06Xxtr9hQBDqnV5jG6Apoai86vub1RcpWlgR8v37CxKz2Mtp1favzQFiwDxwBJ46T6jsocYC6+U1emYu2kaC651Zi+Ayw==', '2026-04-06 15:23:08'),
(20, 2, 'product_status_update', 95, '{\"user_id\":2,\"product_id\":95,\"old_status\":\"available\",\"new_status\":\"sold\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-06 17:25:17\"}', 'b0neMbz0fHHIBe66IJacoKy0Ve/XVqt1DPlzhEBVi8CzSNz1ivfuwQy3gSnKr9sSETo9I59dRYwqF81s6PKOE3uO3Munt6gAWqbMyGpG7wGbQYeRYkDgqeUqE/IuUFFLozNfpJkoAdD590oe9OO95lmVM70yv49OeJSQBwpUO9KaRuyo5kvr6gph4siaO2rky/OvmG36uDSLBisQMFbSUoL0PPeY7K+1S/DfBy3AIyioR3im5KwTP0UoMdzLaNmUuERLISLCuIeX/Q4+xeXw93/0BnIxt/Zcqu2IJS6HrFG9PM49gREnxSeilJ7l23bCjPHY6PdlIIqkSMAOVnRDVg==', '2026-04-06 15:25:17'),
(21, 2, 'product_status_update', 95, '{\"user_id\":2,\"product_id\":95,\"old_status\":\"sold\",\"new_status\":\"available\",\"action\":\"product_status_update\",\"timestamp\":\"2026-04-06 17:25:23\"}', 'UnMTCPnAICyXKLX9u2uKQ2qg3+O6KXfmDhM5JhGZG0dAQU2wI4WblYegnuySLVmq4AwWCGTtRhf/cNvEPIhSHU+7p+R/uazRlOF1fNBElnfP8FE0LtSaMCfbFZzRLtRK5PwwUXqfnnaH3a9GPewxToVl1IaINIZbCiSB+5doqfpRxyq8KITuZJhK4/GwP9QStz2XOMYm44yQEmv+XP/Yb03+u7+cZZ4/4b/B9B9Urj259HGZVNvg0OzHC3zAhSyNpnYo6SUrF3qoRmbZYLKxbXKshpTYXg5TWDX7FvMi29yl86vd7D+gL96EuolxecP94G/dUrwba1DGnOtAoNTTpA==', '2026-04-06 15:25:23'),
(22, 2, 'seller_delivery_status_update', 20, '{\"user_id\":2,\"order_id\":20,\"product_id\":\"95\",\"product_name\":\"Journal Book 257\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-21 06:53:04\"}', 'isLgFCJQEKL4OM7M9/KwMozkvVIo/hsOCp9yANDwnZ/dTV31qSNZEDHiqXMrcd8FzW4dMUNsxPzwNIxIAHI2RN/iQAmfXegQxxBxoxBUsGSygZCLX0vmdBhroB02UfJ3HM7PbouWbGNhwTHjfXM+arTeUzp3aQE3ogPJ693KpGlPSgpMpWesXglMyYQ0AtGzrut1O32bwiuDCXMgMd5WuIKAX8QIryqdzNmXraWY1+tq2tRyKOlqhQX2h3YULLE9+59LqAeLYeHhz6G+sDB86HM7ZzZxOqW/doiGXv6tzmSNdiNouaannqw3LJKKtkSMZPQeSVKc1V4nkCTgFalFTg==', '2026-04-21 04:53:04'),
(23, 2, 'seller_delivery_status_update', 21, '{\"user_id\":2,\"order_id\":21,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-21 06:55:34\"}', 'N9itOQAi7Lz5IG22YZ14yyDUhakcA3obSPUPgtkID+2xfWkLo7hyP/kZkFZaUqKdin6zazzv6ie8x3JPjO2SYr7ICQQuOmM0vlakWhkreJ3C9e8J+F3e0DReBsYk4v7I/CX/B14n58ULBW3z76QK0lCGGWH5IEGuo8s1/0DND+EqB8lhj5hGrgjYrJaq0o7/RM6HB52WQmlnbJCFkEB/zb/tSTdC+/EvsqNtYg3AFJTSri7JrcB65B+ZeYrpI2FeVhwiR+o7G0n4JxXj4/5hHKvNqbNBRqbHeQ42lsz7Zw12MUG0fm0F+86+q6D6GhkjS5DsXOabVyXwDeWB7Vg+Aw==', '2026-04-21 04:55:34'),
(24, 2, 'seller_delivery_status_update', 21, '{\"user_id\":2,\"order_id\":21,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-22 12:24:34\"}', 'FI8Q+ZFx9lIfRX/jKcBNVcumE6vVjDsm8Z9WKAzuCkmT4pTcM6VREEDac8RTaVDplRoJsP0ACC1mrQUmmU0gjXOEGVt5Kd0pNchlWM0+V4YJWbzeUd+YDkS+bkU2qCP2avWkUtBQELoWPuk8SFhgc+zkdrtEym95/nLAzuKqsL/PSa2SonOnSIO01nlcoEdB3j5ntXpy0Wk65IpiN546hs7a2I1Gmk+l3SWcSUDuLPXF0bloU74Ozx12bVQ3lABs/3/Oqk9DKjQfgxPDWc9bQp9pU9pOW8fdGhMcSbIhI2BAx6HV/hxryQ4Ypq990rbUlBeuuf4n3ltEHnWIHxUgJw==', '2026-04-22 10:24:34'),
(25, 2, 'buyer_confirmed_received', 21, '{\"user_id\":2,\"order_id\":21,\"product_id\":\"96\",\"product_name\":\"Whiteboard Kit 162\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-04-22 12:24:37\"}', 'EFoZW7LPPhZ4+wdSJK+10ZHGvBGiinUubytK9MPG9FygOfgxJQZlyfbZNIiuR5TlIzqD7m19wf90eT8HG+wxgYsbc8oMFnZ4xkx8R0I16iRzY8IAXdxC4/ZlNQ05ZISsCVOtE983my5U6bzaOEO/9+lgsyis3pr+Dfwlbilo3+92HIfbDJx/iOLp5TXXKIPjR6O7u9Tk+uxmzP5LaxtQcH/XSqthyf2GNCDsBC3y2LAEmA9FyLWMdGxt724ewJKlGh0MWzKCR3QmGXd1XaB0In9KWpkNWICgtJ0YUbIzKYiU5TavZEHtsqnBKR3C3aVYD9ZFzGlkooArMMO1H6M1Ug==', '2026-04-22 10:24:37'),
(26, 2, 'seller_delivery_status_update', 20, '{\"user_id\":2,\"order_id\":20,\"product_id\":\"95\",\"product_name\":\"Journal Book 257\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-04-22 12:26:40\"}', 'Ad8rBeFzBh8QDe2HJJeufB/J55J7dBYwpErN1egFmPIeUgbd08KM/ri5Cj20JEYwy9U2qVWpc5SpIlTGk7zaSzMkCZSpkMidYOG5JtWD8rJDJb2FNM/SA21b6hkrYtRbq8W3LuNG+JVSe8K38jTMS8cYjhXLpHs1jFMS9H3d/kMfkpqYuhzA163dX8gfOqdoTSaLObAmCmWnwwOWOEl4vff4PZb5iOIVmj3saaZLvRRyoMlq2UaHypoBhX1I7InOhG5QJ4W6VzrcH3zUC/4rtz+nDuEAC90Cf22IjlJExUSgNz/3ubfiDDu2Zyqs1KK60i0DWwwez5lFKRJD4osL7w==', '2026-04-22 10:26:40'),
(27, 2, 'seller_delivery_status_update', 1, '{\"user_id\":2,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-10 08:50:54\"}', 'IRPqB3rR4YTDnKu9cgiTTuM+VXXwlnmhYqpo/qL/4ziEM+vJIzZ4seJ8nRd8RNlTHPiXVCwMemKPEXhry2H7A5IWyX9RIN5T3v3FaEOmCfnWhPH+mGiHd3Uh0bZj8KacPaNVIqAJKJDl36S1LUhGnn7Jtt6fREIhKGQ6Z7k204i3IB1lLGiW4UeOAXp9buoAK2D62nqeh6H3X6uwuS2fNdIf8kLQyXgmUmm588uOvt74TZkEf5M/h1dZEzHIvrFmW8nzMf6Kgv+r7NRAppz2qAsY5G8Us0J4Keljl/rip/qtKZsbKMEhiNCyyp44MGXFLyC3ATODc67WYBFQxFpGFA==', '2026-05-10 06:50:54'),
(28, 2, 'seller_delivery_status_update', 1, '{\"user_id\":2,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-10 08:51:10\"}', 'DhI6ibK4Zwxpf3ii49Qcw3IuRH1MURFTpFV8IES7PSGsxiGCiYee9bTYqRaOV9GvpuyR9C7nf6Ju3ZcK1JBM8Mg3C5jHfskvRDnWBJNTEvJOxZfjLQLw/MoY5vdhewyzZvLNOzB7X5u5f4/ZFbr0BiH2TTzQA7ssm2/n0SiuU7FULBed710K6d9KdBD2PM0YQ3lrVBwslSIorSpYNHrzJqutGxWUuEnZCkHzMwt2LQ5X096DxfwNLkmv3hy3eEgAXn7JGSNnbbrRf9rIZ0fkRXkhOuB1tyJSSJ4hN3itqYJwNzvxkXQzJEzB5zag6HX2eVOLTU8tyrmClG+3yCMZhQ==', '2026-05-10 06:51:10'),
(29, 2, 'seller_delivery_status_update', 1, '{\"user_id\":2,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-10 08:51:27\"}', 'i2x8UgKt4Q9uflESQxPPP2Bp5ntJ3QpEKLeo0IfURjYd9JxP3xbimzG1CPixbo0Obofe3+8DRfHVojxxoBcSAg7x6Re4TRjelfYn354s8hDfLtJWDcfENOKrI3RSMHozJjRzV07MpBDgrscGwsSjX+r08ci2mwgQRDOUyl8FhuQkP4YGHOmcjSQz/qTl+ywyOuX5n+qyVoBtINzGm7822+6LQNHQfMNIN9N9oLgyflQRmwaHXOB4GiLE+bNG8StPOQwPK4G58noefUNieVXM2or8N9awWUy7U+mKtgBLipSD0KYAI+pmxk3foKFCNyzkB4Rmd7Ewvr38oO9r2jPMlw==', '2026-05-10 06:51:27'),
(30, 2, 'seller_delivery_status_update', 1, '{\"user_id\":2,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-10 08:51:41\"}', 'Z4tAOB1R/UJAmKopm7fm+XoxIZgRaO1090Dvc9BwVrcboddB1WPKwL9+JFLa8T+TCEkAWfyJafI3zWl/yvZw5S/jhzSyT1rqpVBZkFgW7QaxSCbqGeTyYNRXubYABM1vnJrWBtvuOb4KG98s+onRIegfgmBqkYr+KyyYfshZPokEeFanBQBdnnx1CncgGFNnrdDLel8tTa1h8Scp5CW1x9dY4DIKCUcpdOP/ufp9UqrSEEEyYRieoK4RDpRyG75SCVry0CV0uWXf56vbAXWD9S2SJWTILFe6IAQneMr4SOrfRvTCsWhvfSF1/ftag6qI9iqc017cYa6A6lPMQ5tLGA==', '2026-05-10 06:51:41'),
(31, 3, 'buyer_confirmed_received', 1, '{\"user_id\":3,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-05-10 08:51:47\"}', 'SnQtcmN3w6DJFW0Kf69laNYxEy5F6Q87W91QRs83oxMVV/6p22jezKEBoYbLFgu4nKqfR85PW+3B1RD3tI2l4JpxuSv0eNqgKXtDb5PIWxG7cP3TpuJRwzzE3ueJIp6x6j9KheN89EWKeiHwuNi2DOPH2mIhME8UCQbJZDH+5ZbWi6NNYkbhZfLU6hICSHmr74RxAkPg8ZMLtl0b48a6r3Sj845UNJFxvWx1lvDXvYWXbyCoJW/Re0y6ztUzkA2HvYkhXcECfck+EVJLjOnp2jZMZCaZe0amG9duPjKd96VEhQIzxRIEf/asb3+MGVtdEklbzPMYBVmOYqF4MyoYpg==', '2026-05-10 06:51:47'),
(32, 2, 'seller_delivery_status_update', 1, '{\"user_id\":2,\"order_id\":1,\"product_id\":\"85\",\"product_name\":\"Notebook Set 406\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-10 08:52:06\"}', 'l1NLV4O6sxyshAWXoyhOoTfDY4MHLRmP8UDxlF9MvT6ET9VEH3hTHtiTPBwG+oVTPNE+xfUQJQSHokj5aBp9s1MbTja946KML5ofTP5XPVi1DVLPWLS6EYVntvRCkCvm+/h240d47vnfIMNBKiYPI6o47zxfFUomsZXT5Hzh/IovFsJ4c9o46/qkEvZukES/KlCnlcvNGOdHwYGobV/BjX1iP/2ovcmPa9Y9Wjb+nIdyr78fLTxVJO/N2ooEAD6wBZR74vlCZuz6q1innDUlGaIM04/mH01qoLFYwTebnuL3MKgUsIg0SespZwfAq/yGbgbRGcq4/G7OsI7G2Xbyfg==', '2026-05-10 06:52:06'),
(33, 2, 'seller_delivery_status_update', 2, '{\"user_id\":2,\"order_id\":2,\"product_id\":\"3\",\"product_name\":\"Dell Inspiron Laptop 648\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-11 05:33:54\"}', 'UhR28QahmXtKf89Kjjm05PwjdKVGnETnPE8Z15CL1hjoS+GCDCVaTil2aNMKiSuUZCzeDhUnL2cUySxMDeJjvhN6MBk0r+o/3K4XCzxsvES3iRVv/8n7ASdXKsLfZtDWGfw4meUsSPtdXagLxG7QHw8R5ObHgSypBe4OXC5jJvhqeEJ5JpiQ/UIb/yT/Ywd3tSPj127f0Z9K2NLQjDGle0Blvr07IFm3/xaS7DJq9AtoGSaEo7HZKH6HUEKsBzO3XjcqvrWQGgSYmaN4PbpG5UryxuZUctdwQuQXc9LHfPoabvwN0uQPMSMnpUeuFxcQBACdzeTU9qo+++Y6NXifMg==', '2026-05-11 03:33:54'),
(34, 3, 'buyer_confirmed_received', 2, '{\"user_id\":3,\"order_id\":2,\"product_id\":\"3\",\"product_name\":\"Dell Inspiron Laptop 648\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-05-11 05:34:10\"}', 'cdAsCGt9WdHMO3nueQYeeWP0WTVK90c8dMUDR7t0hiZw/ZtLxWoPZE/4qPGp0ICbnZfG9gv/Zx0JXfQJJ9hK5p/dIPF2JD370TURkb/4eTcwWgZYM2cEs6UjN5t4TiufhbFRuL+pie+yAoXYHGZAivlh6JPswtnz6Jyb3wSnDh+tUnDRD5SXfd9ATLQXsD2USQeTQ8QuXNJvKBg/WIr1WNDJadpMhiWcuii1FvtO86AWKP05O3pTg9Se1gQzKRWN9DKPUuNkw8JA5iaXvgzFFVZKeZs12aj20aJhRthnZLpAi8VRCv/GNS08V/h5B77p20E6eMfvLtCbJSQKLVWzHw==', '2026-05-11 03:34:10'),
(35, 2, 'seller_delivery_status_update', 2, '{\"user_id\":2,\"order_id\":2,\"product_id\":\"3\",\"product_name\":\"Dell Inspiron Laptop 648\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-11 05:34:25\"}', 'U1/jagXm2MOb8/GpbOVHUZX+M97Jo9ihekqfOy2hC8w7N4WOHn1FZPDoffTtupvKXs+9hpptRpt5p4/gF5qiudD3fEa5i/IEPqCRK3bSY8OItHRBiPJlr9kdbCj1X5d2eoMXIF6zqYqN2sxpYs9ki6/oAmw6ZMCycLa2K4AtGbl4tJvgnV+tIZxuruO5S/1QQUbU2rIJ6q+MHxSMcZeYCL80r3FsyTEWuSIl2a6VsCxQBRUyPB0RZfsP5EbdwKnrQCEPOmVtMPxCennEbJFwAnoSfDDs1WqUa34zNoUGT/fmxhhA9NWqCwcFUYxC3321ii9M406aPMse1eouwRBinQ==', '2026-05-11 03:34:25'),
(36, 2, 'seller_delivery_status_update', 2, '{\"user_id\":2,\"order_id\":2,\"product_id\":\"3\",\"product_name\":\"Dell Inspiron Laptop 648\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-11 05:49:21\"}', 'HqGeBiLEjDiHLrpiwNnWECWHrF8MPq5EpKuOHFAWyXVdDglHdwhZzEcmJr9q6pjX2bGXaGIPLEFnFXwcKGpMMUm094qZY+TlcxOF+FM7vVEHfPZLZ+x0YwsXTBv5/5vHGdUTes3dQ2od4ySB/N/JDc5zs8nyaiaufXt5SDZ3Cmt8vLcDynw6yLWoFllZS4qv45s5cmBE2r2dL3YcagYZI77UgeTlhmYLWiYGAIbmbRo2AWIDKxiHSMmbyXboGd/Oe2eVhsvqSN6VRhQMycJao4zi18Ac4SMS9/qU7EUAxdi+0UqIZKxZYhFZCXjRFtxGDTMLETa1rWG3Iy9NJjki4Q==', '2026-05-11 03:49:21'),
(37, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-11 05:49:25\"}', 'jof3tsAWUKuH1iir92Q9D9u+72e1YdrpgXGzj7a7JNurxyWksHUQFbXQCH2jwcuZQBslDV34wIDyAkXciJO55d1K1qexctpOf8f49JGxA8XtG8IP4K8XBOnIvT2HjavmNj8tA+j5vhRGeq5vi+h43ZLDtct/Xc7WeglOqemUHp5H4zo4/NHPGxA0fNuibgrqzIjL2CQm8Wkjw/wfoED6VZG9HP3n6neBjvwaP817+TNcdTsETPh3e5/U3z83ZUVup9JRnVzfv0cTxrZs4pyA/Du+qOdn95i2nV8sc/ww9F23MDH0CZUP9Hx4mU5UG80FaDXAdNGV5Gr6KFVECcESyQ==', '2026-05-11 03:49:26'),
(38, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-11 05:52:53\"}', 'TW1w2IJm0Ts2qrDdLsc9nABg8ZAgH+4oUFg31jjc6+xaFwjROkH/2GrYjlPK8kCjMXau1JUITb1fXKG8LbSBRPyTpclQmSLQQl+YZygowY+URqNPFgUh07G79+HXEubIQ3moGgzNEJp1cXRRZ6mZhQFIwM2vL/4JeJep/B2V5t6kGnl042m3BK1rUKQwphlQeI/CdjgsrGNfkd+SGOjvWAnRGLqFHTJZBMpxJnYs4cAmw4AGwjsPk0n0Yl0TFTxH1qFmJP1xcvBkWWUNeoHVZ0phRl6E1UvGcuXGbKL0NUWhI5Y6fv8S05/VqmZU3g5u1kXl94WpxnlLrrn8zwqW5Q==', '2026-05-11 03:52:53'),
(39, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"out_for_delivery\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:52:56\"}', 'nc2Gvj+hKk4ewd/53CZIDAqXFdGt9uVv7s2hc8z/fLbJuRAFsND/Zty8hDTcaefYU9qWqqUXZLv+CKxzMP7NnZev57bUljBTXPbdDrDk7W1Buj8aYyd8YUBnOhUgiWuTaw9/jrR1gGbE7dcgPx1uGccoMS7CmqxD2RT68dcuD+Xbc/TQxHDyI+kCQNxxZ6jeQFcD+Lm6SL0oHxAW/W5uFQVM+s3sKA1ER/0CvsyUWDuupb9DHCUurMpnvgeldjQ3p6UKD1QxmiDUAei/ORuuhCE5+fbhaFfkyzAvKdGwztpVP/rRhIZigB5jVsbcL0CNvXrY3l05ZjZ0PxtjetyS3A==', '2026-05-12 06:52:56'),
(40, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:53:19\"}', 'QEwaS/9B4a6s11BugYWcfrZRA6Lh8R+qzUVMW/KO1l9ESw3DJ0iVWTIGANGPvAmdACY+OhePPvfzdQ97poI1xyyrCSAKdfSfQ4ApJsNTvSyD00frUYXw+N+7j3amWHHKh2dLYwHppQ2szEhNhlISkXQriN/XkYueM6Nb+9/DDDw87EQFETY7esv3LhxMzIuam684A7VF+u/rA22NIcI319OTPN8nESxpdA0BypFobqovCYXAYVi9UJ8nAOqYlmPfx/MQ+hTWgfAyrTXI15uKTabO6T1/WwctH+miKtJUVb3OusidzT/MgudK2JfnOLxwtWu3F/r2jibQFBYk+cgaBw==', '2026-05-12 06:53:19'),
(41, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:53:47\"}', 'lFzL0J5mwexZNAOqToolqUovrc3r1K6wWPaQDE1tBQoAzLW+GIEsr16D6JByMD5fvvrVfkOI8hYzzIGv/AX23N4wE0PXzPfBbobPIqj6h5fl+Div/DoLM+MSIaipdlTU7+nLzkJCOdherShkuVC+T7sz5vSa74bVJAuoq9Ud8kaCYx3iugKqRe5XcmFux7p8bfiEEJckO3GC0ok4EX86rZZaAu3vQJoaxhP3ZqTDjDh2RMHfqcybKrYS7gP7Fx24LctPgFAjgP5rd79HvGtTEEbW84iLv1CTrk99u5U+TegiyYP/Ian3Zw5C4vO2eBw9x5W92+OuOOGwcgMkMuXmgw==', '2026-05-12 06:53:47'),
(42, 3, 'buyer_confirmed_received', 4, '{\"user_id\":3,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-05-12 08:54:07\"}', 'PegTW+qSLNPuI7fMe4eRZ3QYcR5bgP48PfGGj4xKGkI56lcF7ymGUN6lckfwg3f+Qxv3lwiI8wwHQSGC+64LsPPORg3vmlDYzau0STbaMTKgAzM54XUXxZ5Fvh3eeA53MZHANJmPZpJuWAKskhdcS9EMe3vsP/uHMuPVVNByRoWYZDE2gl5PZuPZ9ZLzD9tMnZa2gnKgnNA/9mJC6chiTe231t5PM1RVzhUovINAefgOf3sv9YFzwNjMCHz3zCMB0nAOS6uy+YaNtGlsjd+6fUf+WkYJqW0Gthv5jjesPXw6W1nN7mngdhLe3F1sWFo7ET9ZrCjn6iV7coTuv6MmSQ==', '2026-05-12 06:54:07'),
(43, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"processing\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:54:12\"}', 'Re64j9L02+eyth08secwtTIAj1HXQX4p5Yav09ffH9qH7HgIlbsP7TDTIrh9JqG3acMJJ56rrMFn6l0UWMnIa2OaaFPepxpEwINiFORlvVKZBx7/5hEBX9vYSzS7xaZImJB2m6IXGzAsJN8uoAZatSPt/UpzvSPD7umFyhwtfwoTavemCMK9719wb9A97dQpiJP0fhFBGXGjEu9n1xunPvqISNXe9n4RwZxQ0Z6E/po2ThM8zfjhY+TLiwMforby38xA51/GptdUPda01z1y/ZdWnRORkG0laqg3RRayh0TyqMJm0RFBpk7G4L74tzAcoYRpHwiGY9yacPKLOm2Dpg==', '2026-05-12 06:54:12'),
(44, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:54:28\"}', 'RoTqaR2jvdqmeYV026/Ee2zNP2Hmfp5m9XJTHgE6XBJCZwiHO3F8PYUVLK2y7g+W0+Fq9BpVg9OyM9y7/4rCddtQwNcObArdpE+g5gx/1aLtH6tKcxoAuyLUrn5FtbC1X6HWET0ngebkYKxJiFWOg2xlPZqSqQ6kh3TvJKhGWKFnqgoH77H56LZ5oVVF6OhbmY/DO78scEjshHgQBMKeUAp1STDkc6gFCGKZVds8egqfQVczDHGnJ+0yvjQCQ0C9oVdVNvDsKh6x/LNPMye7lw1ERa9xvHAFEQHQJ7QNu5JknPH3byi9Hl/0TzOFxrH9M1VcKpoiOqyhZv4ug0MWYA==', '2026-05-12 06:54:28'),
(45, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:54:32\"}', 'hCJ4EjKL7ucs9xoa1GNYyqswEG0nvx68nFdOVTBFF7t6Oh0Q1mZw4v79e65XrAEcHqLfBXAsrfR4AyHkk8qLUE0TCQf6hwNRvFpOijgJTiiRD5eEiFoK0cYP+hxbTDH7tT6gXQzGSTdkpG6AvHGfmFARCNeyn6AXZ1lYqIZO98nIEmuQGN3TlenWauOz78GefnjU6otTVILXtK39XEQJamU69PHODFAA9f1Y6HTOlsyDtr0jzTTmru4zCHzo3mntf2Psnv1S7cDsdrUNJt0jI97pdqM0SqpLDxEvJu6QHajce1GLumwhfo4H6RNGVYpWlhKueMgkVCC6C/bYWeKRJA==', '2026-05-12 06:54:32'),
(46, 3, 'buyer_confirmed_received', 3, '{\"user_id\":3,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-05-12 08:54:42\"}', 'g9/JkOMOD2Ayrj7PLgGMlNvqj4LBk2fDVt6BOtWZukKBgEdcQ2b9s7R1yrstY2xpvKpFYi1nk+Z4+CjCdTueJbFo7S3cYIRbC+k0do/vJ5TwTl4raAP3dsh2v+wWhS5gcZqoNLD7ki5hmRFvT20TO4uFwtvsO+Eb8SDnJYKdwcj9cJ2K3XdWFZNx4iSPzVfN4IvHaGwPIWOFLlLIascQNvnGasN1n0ll69ju7nzomg7TAi/Jc2qSCPyCMYDWt28cC2ThvOYFnibu2IYDJFfoLTIR0WHJ2CDa3mMYIV/YMGvOYb6kYIb5eF4zXTL6BehE9kelr+6DW/O53mwJkpsIBw==', '2026-05-12 06:54:42'),
(47, 2, 'seller_delivery_status_update', 3, '{\"user_id\":2,\"order_id\":3,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:54:50\"}', 'dw1E/zjZADeWKoVY9s9zdgwAzppPj3iwdIaje9/WVVleNPwSikSxY0ztojZ/Le+/S3sgsefaAfqUP8lEVAFJXLjbXfUUAWVDT9csHofh8zX4XWSucVwiSWqKvvnipIfTyqPlfzotQcC0519Yyejb7EhBwV/ymUv9aHO9r/vjUYtK4O/8ziIj5rcXxt+REQY5mTx6v5qUqSnuGFEPXuqDY6aHC8oy9TGVHwhwNgJ78Suh5kEuinFwQLMlzp3QQq4Odi7ZLJxrqamB0v/pnXTGonXH1MEd+ALx0VyH4wLEGOvjaXhLGlWHot7ygvqy4BGAmvAh178ElWx9GTPd9OYOLg==', '2026-05-12 06:54:50'),
(48, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"pending\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:54:59\"}', 'kxfzSFKBKxaCARnvlcSmhF9oYtSPPsMNo8iTiSxCfghcuT0QAvcLftB3cZsZkvr/FNIWEdlO452TGTEPfzTnixpHgVMqZfu1ua1SJaRV04lgnYMHro6zfRcWj4DptHj601iskSACZxmOHQofcoNYqRxopM79io+A8Nf9zR153FW9Ru0+TfNmC4KNOZ3BXwiyDm0IwlHc0e4bGS0eWyWI32zOCvEniwjrRUGnABHndTK2d/GWRzPKWuSuQESyHJCnCoRp2VHwxE1JFlu8/8dRXAG1+j7tB3qtgYSMQO4VNIs0SCtma6mWtgmQZ+IrbzzVS+R1KOC/ABEpJbFGXn6bjA==', '2026-05-12 06:54:59'),
(49, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"pending\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:55:22\"}', 'JAKi8VUK/glP9tFHojs7IzxDfmcJBYEiSNZQecHGN6aCoKIRxVK9vqdgSgIFpsSaYufqOnkR5wArFT9RI5ugREH9z0e/S1IDwe0UnscIaUzm1NUIerFqNmm5aFarXh13FCu564ZvFNOh1+KQKMSKvsWin9z9LWl+a2dOXCArWICkvA7jZ7QP3FtnjpAtV97sw9/RF7ciYHqoSBiiMyLDKw5LVEWoFqIiQBzPozQoNz/xzzlFW3ZFtO5SKaLSu08YB/Xp56JYcGGYJzhsFhzDqE0xLnQnbbxJ4xOkj1UBWhczLpp7/8UUtC7U6/BoSvFbSMsVTVolWnfH7sxfWQhtCg==', '2026-05-12 06:55:22'),
(50, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:55:26\"}', 'MuLC9r7ah5OCB9bVQwi0fy47jef9jz1l3IULpoAevuuS99MptHhHV6k7BATRpe3rsV1i+os9Odzgn7CIwiB3Qqloj3E1hPptBJSdQZwDBvloxygk8VwYiTid4hqaEpcZYFxQ1Evf6P0JVZ7QEFRmO2djUufAi3zioS7BC6dwDqCaoDmrmMcL04ZwGqVl2qCmc/0yJINKVbJDKhHqLWOoPZvb+WA1JxMHYtdyAFPU+xHeLNKdmyGizkQnz7GLULCqDUzPBtCcTL2rR20kelgC813OLV7cNzKcgDYE4WtEvJfmyygcGsiqFb8TkmHJejMqnEir8fFdUR4OKhzwK0SwYw==', '2026-05-12 06:55:26'),
(51, 3, 'buyer_confirmed_received', 4, '{\"user_id\":3,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"action\":\"buyer_confirmed_received\",\"timestamp\":\"2026-05-12 08:55:48\"}', 'KBynLbkv1dT51FPeAfPDgVV2m0DQQTU68+bWOGZMxwd2romQ0fQqjSAoMtQpV19jAAmNC+2GOE67FXnyTBcY1YS4MTJAcqjgBR3vawQSIu3rZ+nxcgSYhBSG4fL6niMCR8FJ2279PNT3WCH5ACtSd8Gh2xU0vS7H96QD7JXI/57Tjk8N8nKwwwbKdYz38z7qHw3YF6FvrbAqYXLS0kVm9uhyfVf33hpGT6K0Ce8EdkT+NJWfqVyQZthtVZcTU+QRpHODlxz3GOCYx5OgdzZrQEtG/Pn/u3fQfGuiYRPFTV+M3tSB55ykGhV7dECTvcdnVK+icFWmSHPq/uYjaUnqZg==', '2026-05-12 06:55:48'),
(52, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:55:55\"}', 'noy/uW/FuM0JLw/L+RHU5VQrbIZACXWTuolWW80orsPN4ysfe6rMnH7NFJqnzh69i9I6/apgSp0TaESLCgLQYZHDcUhLKvEkwTOYr3LyhIrD6kAks16uQNwU8gYJ4mF9l8y1KMGIPftqx67YV80sMcUiX6lbrlWvB8SUDaIIz7+IWseoLzAmPh9CJxozPsp4zKZJPN/VW22/iR9AaxTYJ1MWFZt1RaD3+2RdlX8edm0rsaV01RXJPWmn3c/XcafwvQzkM7DfsszE7IDEv30SR+6UKuBoXhbSvkwE9EGVFKEZvPDLYMI6CMJUxMult5KNru8ZoQLjehIgtbms3hC9Gw==', '2026-05-12 06:55:55'),
(53, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:56:05\"}', 'ceBFa7Rdh2ao094EnBbqhaDlobvCJd2QYBlR5CKn0huT9WNd5Cw1qXnbsBIvbZplIpZf8istImpEDB3A7T5aKMFIlbD0DC4VyPUFrsMZ5+lm9nL4AkLFd57A3bxlL/HCImb9Vx76/QMTejq8YLTHf3qxuMsrRfOZxD1+adBcdfe+RU+YBCUte9lPDg+g9fadEWq0xBuRNCdMHJfDScEgkfpTc5yj9WXyG099RVIYBeV/YvL/Es+w7l2Q/Z9livPKh79V2hBMXoPNWrMig1Y2WfADtYBW50mlnQ3L7x79ddeNRnvLLaz3cTfrnUbvTkGpD4ZEymdDICAnyjFemHdGtw==', '2026-05-12 06:56:05'),
(54, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 08:58:40\"}', 'Qtdt4woNyBGbMnuXVaEmXMyWioRG9VWYgl2544CYl2E7ng3/Vv7bWOkKDtpIq92TfbBU4oGmXuEW8+iPITXiSky+pbxs+sodaaYvXPGtIzO8N9vjof4w+EIiZ797KHExv5xKER5/PDdrr+5xJVNOVMJAXuhWP54hfc3EgRXwLXDCoAt4V6aLWELe2pPXcNLOWA4/a9Zl9b6vOl/2RlW7M2sa3/3SR5CCGshpX1YUcRFskQcySB5ztFuUgriLiOt/I42tkyK17AW3ef6asLATbbKT6TRH9KtxEeJFXmoJbL8izHpxFoROPad5J+fEQpJFdpPPnPSq76YqCNiq8ApWSA==', '2026-05-12 06:58:40'),
(55, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:00:57\"}', 'Ke/MdxZFvypbI+ceHso4uH1CaddPlTPznXnLWNpdaGZjB3ms+HeH8XwAYFA187wd+aZ7JtXykSzzYzrC5PxzyjKdHVX4KUNUvNWZwm+jrrcE8dbkGObfVXP9YG2HD6bZFI3Xu8kDgz0WCcv3Wp8cewiqVdXhxyvgfrLq7rbUAnzNcLYcFT9v4NrAbE59yo88ZCMrAu6z3WHCLcR7R6ZwIAI4nJ/JdU3r+EXV6A7CZ1YhHLAOgWLXUlHx/Jz0gOxb7ibkZVPXp+niUoL8/AfddKP0uDssbUAqHzaBKv4zDV8rLHhwQQx/uK/eMHu2xsgSeaW3pLntrw7hT9FUPyTdmQ==', '2026-05-12 07:00:57'),
(56, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:01:01\"}', 'h0AAYT+W8iszrE/OdG4BtD8OjAHqOdXBFi1k06cxmZMdxRbwzBD82NE2NNOUUztQOKucO48svpAmKJFSnynLSSjncg5s9YVGuAa4tLSYmBx6HQbjIHcBCv2j7ncaJkZRF/dYGLmmLYSWiWlExQCZ/QX5DgNHxN0HX6k4TOXMGgk7LDFR0c7rKz7HITyKZm5jdinSLDeB++p+w7zUmVeaD2DkXIBK7HxGRq6rpCl8H3C2R0zLxopyokz99XdsBx+oxVQJ94iwAXQ5JQWDB53ChfzvWKcEp22zQ+ERzXCEVgpCd9LGo6DseHcwGw9Mf61w2SBYNTUgjORcPOvMLUr9yA==', '2026-05-12 07:01:01'),
(57, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:03:13\"}', 'U4C8l2djy5xF+rpdDT2IbPuI9Q+UdJcULsrM01Z9T4FEzUnUYGRIlY29LMDG1VIrPRi0d7hWuCPJMAr1EoDBPLbafthRk9Yf8u7JTq+HVtjkPp28DjVUm399jyoWu8ru/vu3AZbbCleQ+xDUOdJVHIFwnO8IUCZ92CCFdk0QW7mG2R4MC3I3h5575OwFPWYbReDNbPOgMAHgTVxWK77tSBtxib/0z/AzogS4687e6bzxh3vtJGIYDX1kEUIKgi+3BCSOUQxMkz1ovtBh3Hiog0Lw42HFrBopKns/BeGZFAWHIw2fpdRQkqZbp8Vz4cUpK7GK6af2bPB09NDnpb+aVw==', '2026-05-12 07:03:14'),
(58, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:03:19\"}', 'ChwJh+eYOoAHZF2tIGTjs9jzHVd8UgRNYytJUrB6qUrKe80vylh4s3JYrkNm/GJ1LTkmuVazJ6GU1Uq9oIZ4tdak2StZM7co0J/ruG34VeNEqB6AIbaA/E0EbLFobwlR6mAj21mXwwfWLB3cF6F7QwVumcUMKhkFwaFJhqz/lmrpmqNcsXHm0OlpTILQph+prvodaGOOuixYnAsVR15+MvM06OiK+dHm2Nv1wF4ehfnEelVq/eg8qxCDEn7mKCS6GcC0c+dbgTJnHDojmd1zcqOBv23mfrLiweqFmmzJJ2YaFpq3Emih1wXmB5HG5ZxIKtsGU12JIJKdYMYJC9q2PQ==', '2026-05-12 07:03:19'),
(59, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:03:50\"}', 'SM0PKqnhcvnO0CFMn5M0s/KvEVbmbVHtvsZx9sPJvGwZUm+ZTsh64Ik9WLI7JBA3cPwTNd78bT7+9SnjyZoCF6f10zBXPt764qePYP/MpCKBe5C6LruH818BNjSbFVUIIoObXSWHe22XP7UdBfnWerpZw86AwSrzL30DKrsbB7ZlLeVL5LUmKqHfo5xy9l1W1se2dIst6G28DDbrAZh3z2T7csk8zqBj34H5EbFP7lxKT/PVEgFUILmGm2gMBcKw7kfc8OxWSgl3C2jJ5bdizLwYO2bzJ72DJppnk/dgatr4rTCA2J7/k09WjigLaL12zKwTO3L1cJQWED5QV2It8Q==', '2026-05-12 07:03:50'),
(60, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:06:13\"}', 'EbrXJuszEwNoBd0PbyVItxWYASb4OF2NKZxXSWKL0sFCw67+IqoWqpwkl2p6cDScDBGNESzIYexsNo53E5Xv07+zsUWhwAP+X+ypTfELwQQEXIsTErZf/MAJUrXymOkcEfQpl5zSNm9ppz+fAxuj4qGmbtGAvu0d/TdcLO290U9s7We4ASUf5lfVWkn56Hi1il4Ct+CJU38HY4+XWfDkLRTcLCWIDvH5kGCaNBSrvmDq93tGogJ3mz3+lK5ut21HR1CPXH17nnpXLOKe5M3kWKfVyegOLX2+PE18++4g2N/NEkk61gCw7+XWkGvtotXPxjw6rYgAghAMQlkI5ebv9Q==', '2026-05-12 07:06:13'),
(61, 2, 'seller_delivery_status_update', 4, '{\"user_id\":2,\"order_id\":4,\"product_id\":\"86\",\"product_name\":\"Pen Pack 47\",\"new_delivery_status\":\"delivered\",\"action\":\"seller_delivery_status_update\",\"timestamp\":\"2026-05-12 09:07:54\"}', 'lu7XrDCxTw1B3y0+ebhPfTm1eyFJobTMEUwPhJmVjA+C/BZJynK/LxN8ttyrzJS0pHWR7JFnD+rukYIdUCk+sazcQh9GxfDS16jTwt3hmS63khGIGS3sVqJEmp+4hELF12+31X0M3+J9BlG43mfzXxcWFUjSVid70BSPVcpGex3sRWGRoIf73lnNu0Nbyt/dNvwmE/ltYsMjRQuVDcbJerfhvcEpk/RfglYhf7FWc8HazP/yMUfi0YJ4NjqBvZ8ilAwrEbV24sX2Vgd0zR1ggnAt4oAtAkZ6vZUoyQD1Q52Xnv7t7P4GHmfSH3l7xl6xxLoxbhhgxhxsyId45uHoFg==', '2026-05-12 07:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `is_verified` tinyint(1) DEFAULT 0,
  `email_otp` varchar(255) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_verified`, `email_otp`, `otp_expires_at`, `reset_token`, `reset_expires_at`, `created_at`) VALUES
(1, 'Admin', 'admin@tradesphere.com', '$2y$10$Wn8lEhjCAT2awdvKTI6VtOfj.03ThLgaWsS/k2SGNSxYSSH95N0hS', 'admin', 1, NULL, NULL, NULL, NULL, '2026-04-02 08:38:43'),
(2, 'Sushant Khanal.', 'skkhanal45@gmail.com', '$2y$10$T.xSPtM4ohrhluh8wXGoA.iLfb5Wh3j1JU82HaqOylq23lfOClyiu', 'user', 1, NULL, NULL, NULL, NULL, '2026-04-02 08:47:16'),
(3, 'Harry Potter', 'hellwrld0045@gmail.com', '$2y$10$a2TyqVBrTJxlv2KklP3Uue91ZsidDIvARL76KgRkvy.MDCMVtdZOK', 'user', 1, NULL, NULL, NULL, NULL, '2026-04-07 06:53:56'),
(4, 'Sushant Khanal', 'sk@gmail.com', '$2y$10$RjVNIEnYnyTwcUo6bFBYU.2KGqRvhJ7Ria/1K1/Xo3W3yc4P6Swvq', 'user', 0, '$2y$10$HGgd7WTie0OV8kWBnO7Pt.05upaJ1BnDHYrv9K0a0BjyLJO1cOryW', '2026-04-22 16:03:09', NULL, NULL, '2026-04-22 13:53:09'),
(5, 'Sushant Khanal', 'skhanal0045@gmail.com', '$2y$10$hoIiuAp0b1gMS8SOqrETNOwnyLz2sJEDgCRMHlmJWXjZLNVHBmAQu', 'user', 1, NULL, NULL, NULL, NULL, '2026-04-22 14:58:58'),
(6, 'Swastik College', 'info.swastikcollege@gmail.com', '$2y$10$DPm.B0Jbr/Rp3ZRvsHMLS.oTfLGKjz0VcEDDUQQAH.Glm6NlLa/AO', 'user', 0, '$2y$10$66FahTCbjxYQ6zjNGJnRwejn15405fXS1YbAWR599lxODmAnfb4m6', '2026-05-21 11:52:07', NULL, NULL, '2026-05-21 09:42:07');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(8, 3, 86, '2026-05-11 03:34:42'),
(9, 3, 4, '2026-05-11 03:34:43'),
(17, 3, 93, '2026-05-21 04:21:44'),
(21, 3, 96, '2026-05-21 06:20:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chat` (`buyer_id`,`seller_id`,`product_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_uuid` (`transaction_uuid`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_rating` (`order_id`);

--
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `signatures`
--
ALTER TABLE `signatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `related_id` (`related_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `chat_rooms`
--
ALTER TABLE `chat_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `product_offers`
--
ALTER TABLE `product_offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_ratings`
--
ALTER TABLE `product_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `signatures`
--
ALTER TABLE `signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

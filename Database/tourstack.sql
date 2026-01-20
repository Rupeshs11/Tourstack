-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql208.infinityfree.com
-- Generation Time: Jan 20, 2026 at 06:34 AM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38773698_tourstack`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$uE6pS4OivGYMWN0WBk69wOS8r9FpAK2t0kTj2jCIQAXR0k6FEyUDC', NULL, '2025-04-18 09:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `description`, `icon`, `image_path`, `status`) VALUES
(1, 'Swimming Pool', 'Relax in our luxurious infinity pool with a view of the city skyline.', 'fas fa-swimming-pool', 'images/swimming-pool.jpg', 'active'),
(2, 'Fitness Center', 'Stay fit during your vacation with our state-of-the-art fitness equipment.', 'fas fa-dumbbell', 'images/fitness-center.jpg', 'active'),
(3, 'Spa & Wellness', 'Indulge in a variety of rejuvenating treatments at our premium spa.', 'fas fa-spa', 'images/spa.jpg', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `included_items` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `duration`, `included_items`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Konkan Coastal Delight Package', 'A scenic beachside retreat for those who love the sea and coastal experiences', '7500.00', '3 Days / 2 Nights', '', 'images/packages/package_68025b671a81f.jpg', 'active', '2025-04-18 14:02:15', '2025-04-18 14:02:15'),
(7, 'Family Fun Package', 'A package designed for families to relax and explore together with kid-friendly activities.', '12000.00', '3 Days / 2 Nights', '', 'images/packages/package_68025be437d2d.jpg', 'active', '2025-04-18 14:04:20', '2025-04-18 14:04:20'),
(12, 'xyz', 'abc\r\n', '888.00', '2 days', '', 'images/packages/package_68dc351cc0b0f.jpg', 'active', '2025-09-30 19:53:00', '2025-09-30 19:53:00');

-- --------------------------------------------------------

--
-- Table structure for table `package_bookings`
--

CREATE TABLE `package_bookings` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `package_id` int(11) UNSIGNED NOT NULL,
  `booking_date` date DEFAULT NULL,
  `number_of_guests` int(3) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_bookings`
--

INSERT INTO `package_bookings` (`id`, `user_id`, `package_id`, `booking_date`, `number_of_guests`, `total_amount`, `booking_status`, `payment_status`, `special_requests`, `created_at`) VALUES
(9, 4, 5, '2025-04-25', 1, '7500.00', 'cancelled', 'pending', '0', '2025-04-19 08:15:57'),
(10, 5, 5, '2025-04-24', 1, '7500.00', 'confirmed', 'pending', '0', '2025-04-19 09:14:08'),
(11, 13, 7, '2025-10-28', 1, '12000.00', 'confirmed', 'paid', '0', '2025-09-30 19:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `booking_id` int(11) NOT NULL,
  `booking_type` enum('room','tour','package') NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_id`, `booking_id`, `booking_type`, `user_id`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ORD17449803552728', NULL, 3, 'room', 1, '25200.00', 'pending', '2025-04-18 12:45:55', '2025-04-18 12:45:55'),
(2, 'ORD17449803635476', 'pay_67aa15655d018938', 3, 'room', 1, '25200.00', 'completed', '2025-04-18 12:46:03', '2025-04-18 13:25:25'),
(3, 'ORD17449803927697', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:46:32', '2025-04-18 12:46:32'),
(4, 'ORD17449803989045', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:46:38', '2025-04-18 12:46:38'),
(5, 'ORD17449804534749', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:47:33', '2025-04-18 12:47:33'),
(6, 'ORD17449804566710', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:47:36', '2025-04-18 12:47:36'),
(7, 'ORD17449804604761', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:47:40', '2025-04-18 12:47:40'),
(8, 'ORD17449806019808', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:50:01', '2025-04-18 12:50:01'),
(9, 'ORD17449807091875', NULL, 11, 'tour', 1, '9999.00', 'pending', '2025-04-18 12:51:49', '2025-04-18 12:51:49'),
(10, 'ORD17449808049294', 'pay_5lwchfu92', 11, 'tour', 1, '9999.00', 'completed', '2025-04-18 12:53:24', '2025-04-18 12:53:26'),
(11, 'ORD17449809225232', 'pay_f4yec6b2v', 9, 'tour', 1, '7499.00', 'completed', '2025-04-18 12:55:22', '2025-04-18 12:55:24'),
(12, 'ORD_1744981458_12_1', 'pay_61871cf82d4d812a', 12, 'tour', 1, '7499.00', 'completed', '2025-04-18 13:04:18', '2025-04-18 13:12:48'),
(13, 'ORD_1744981507_8_1', 'pay_f3fe5af72d96d5eb', 8, 'tour', 1, '9999.00', 'completed', '2025-04-18 13:05:07', '2025-04-18 13:13:37'),
(14, 'ORD_1744983734_13_1', 'pay_2f265d7e47919604', 13, 'tour', 1, '9999.00', 'completed', '2025-04-18 13:42:14', '2025-04-18 13:42:17'),
(15, 'ORD_1744983774_14_1', NULL, 14, 'tour', 1, '9999.00', 'pending', '2025-04-18 13:42:54', '2025-04-18 13:42:54'),
(16, 'ORD_1744985441_15_1', 'pay_330b5d1667999229', 15, 'tour', 1, '7499.00', 'completed', '2025-04-18 14:10:41', '2025-04-18 14:10:45'),
(17, 'ORD_1744985758_2_1', 'pay_e276fc947f5f75cb', 2, 'package', 1, '7500.00', 'completed', '2025-04-18 14:15:58', '2025-04-18 14:16:00'),
(18, 'ORD_1744985876_16_1', NULL, 16, 'tour', 1, '9999.00', 'pending', '2025-04-18 14:17:56', '2025-04-18 14:17:56');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `booking_type` enum('tour','room','package') NOT NULL DEFAULT 'tour',
  `transaction_id` varchar(50) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('success','failed','pending','refunded') NOT NULL DEFAULT 'success',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `gateway` varchar(20) DEFAULT 'razorpay',
  `user_id` int(11) NOT NULL,
  `additional_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `booking_id`, `booking_type`, `transaction_id`, `payment_method`, `amount`, `status`, `payment_date`, `gateway`, `user_id`, `additional_info`) VALUES
(1, 2, 'tour', 'pay_3a0584eba6635eef', 'card', '0.00', 'success', '2025-04-18 07:50:29', 'razorpay', 1, '{\"order_id\":\"order_5f185e9a62c58dce\",\"currency\":\"INR\",\"tax_amount\":0,\"base_amount\":\"0.00\"}'),
(2, 2, 'tour', 'pay_581785f6a347cbdd', 'card', '0.00', 'success', '2025-04-18 07:50:35', 'razorpay', 1, '{\"order_id\":\"order_5f185e9a62c58dce\",\"currency\":\"INR\",\"tax_amount\":0,\"base_amount\":\"0.00\"}'),
(3, 3, 'tour', 'pay_5b7db5edffa5f7ed', 'card', '0.00', 'success', '2025-04-18 07:54:56', 'razorpay', 1, '{\"order_id\":\"order_06f635dbadc96ef6\",\"currency\":\"INR\",\"tax_amount\":0,\"base_amount\":\"0.00\"}'),
(4, 4, 'tour', 'pay_13b0d19d4874416e', 'card', '0.00', 'success', '2025-04-18 07:57:48', 'razorpay', 1, '{\"order_id\":\"order_c4f84d7d815ece09\",\"currency\":\"INR\",\"tax_amount\":0,\"base_amount\":\"0.00\"}'),
(5, 5, 'tour', 'pay_161ac064d58f249d', 'card', '1575.00', 'success', '2025-04-18 07:58:47', 'razorpay', 1, '{\"order_id\":\"order_b5a4945fadf6708c\",\"currency\":\"INR\",\"tax_amount\":75,\"base_amount\":\"1500.00\"}');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `description`, `price`, `capacity`, `image_path`, `status`) VALUES
(16, 'Penthouse Suite', 'A luxury penthouse with exclusive rooftop access, designed for ultimate comfort and privacy.', '11000.00', 4, 'images/rooms/room_6802a0ae5ccb6.jpg', 'active'),
(18, 'Executive Suite', 'A premium suite offering a spacious living area and high-end amenities, perfect for business or leisure travelers.', '3600.00', 3, 'images/rooms/room_6802a13ebda7e.jpg', 'active'),
(19, 'Deluxe Family	', 'A spacious and family-friendly room designed for a comfortable stay with extra sleeping arrangements.', '2800.00', 4, 'images/rooms/room_6802a17859eb2.jpg', 'active'),
(20, 'Deluxe King', 'A spacious and elegantly designed room with a king-size bed and luxurious interiors for a premium stay.', '1300.00', 2, 'images/rooms/room_6802a1a7c7421.jpg', 'active'),
(21, 'Standard Twin', 'A simple and functional room with two single beds, making it ideal for friends or business travelers.', '1000.00', 2, 'images/rooms/room_6802a1df81db1.jpg', 'active'),
(22, 'Standard Double', 'A well-furnished room with a double bed, perfect for couples or solo travelers seeking extra space and comfort.', '750.00', 2, 'images/rooms/room_6802a218021d7.jpg', 'active'),
(23, 'Standard Single', 'Standard Single', '400.00', 1, 'images/rooms/room_6802a23f3a422.jpg', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `room_bookings`
--

CREATE TABLE `room_bookings` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `room_id` int(11) UNSIGNED NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `adults` int(2) NOT NULL DEFAULT 1,
  `children` int(2) NOT NULL DEFAULT 0,
  `total_nights` int(3) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_bookings`
--

INSERT INTO `room_bookings` (`id`, `user_id`, `room_id`, `check_in_date`, `check_out_date`, `adults`, `children`, `total_nights`, `total_amount`, `booking_status`, `payment_status`, `special_requests`, `created_at`) VALUES
(19, 4, 23, '2025-04-20', '2025-04-21', 1, 0, 1, '400.00', 'cancelled', 'paid', '', '2025-04-19 05:43:44'),
(20, 7, 21, '2025-04-23', '2025-04-25', 1, 0, 2, '2000.00', 'cancelled', 'refunded', '', '2025-04-22 18:24:34'),
(21, 7, 22, '2025-04-25', '2025-04-26', 1, 0, 1, '750.00', 'completed', 'paid', '', '2025-04-22 20:47:58'),
(22, 7, 22, '2025-04-30', '2025-05-02', 1, 0, 2, '1500.00', 'completed', 'paid', '', '2025-04-23 04:55:24'),
(23, 8, 23, '2025-05-09', '2025-05-12', 1, 0, 3, '1200.00', 'pending', 'pending', '', '2025-05-09 03:52:43'),
(24, 9, 23, '2025-06-08', '2025-06-09', 1, 0, 1, '400.00', 'confirmed', 'paid', '', '2025-05-31 08:04:31'),
(25, 10, 21, '2025-07-18', '2025-07-19', 1, 1, 1, '1000.00', 'confirmed', 'paid', '', '2025-06-30 05:05:03'),
(26, 11, 23, '2025-08-28', '2025-08-30', 1, 0, 2, '800.00', 'pending', 'pending', '', '2025-08-16 18:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`) VALUES
(1, 'Luxury Room'),
(2, 'Simple Room'),
(3, 'Supreme Deluxe Room');

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `max_people` int(11) NOT NULL DEFAULT 10,
  `image_path` varchar(255) NOT NULL,
  `tag` varchar(50) DEFAULT NULL,
  `includes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `name`, `description`, `price`, `duration`, `max_people`, `image_path`, `tag`, `includes`, `itinerary`, `status`, `created_at`) VALUES
(12, 'Malvan Scuba Diving & Water Sports Tour', 'An adventure-packed trip for thrill-seekers, offering scuba diving and water sports.', '9999.00', '3 Days / 2 Nights', 10, 'images/tours/tour_680257f5be6fd.jpg', NULL, NULL, NULL, 'active', '2025-04-18 13:47:33'),
(13, 'Ratnagiri & Alphonso Mango Farm Tour', 'Explore Ratnagiri\'s beautiful landscapes and indulge in the world-famous Alphonso mango experience.', '7499.00', ' 2 Days / 1 Night', 10, 'images/tours/tour_68025836eb690.jpg', NULL, NULL, NULL, 'active', '2025-04-18 13:48:38'),
(14, 'Ganpatipule Spiritual & Beach Tour', 'A perfect blend of spirituality and leisure at the famous Ganpatipule Temple and beach.\r\n\r\n Created: Mar 28, 2025\r\n Last Updated: N/A\r\n Bookings: 1', '5999.00', ' 2 Days / 1 Night', 10, 'images/tours/tour_6802587c105b1.jpg', NULL, NULL, NULL, 'active', '2025-04-18 13:49:48'),
(15, 'Sindhudurg Fort & Tarkarli Beach Tour', 'A historical and beachside retreat featuring Sindhudurg Fort and the pristine Tarkarli Beach.', '6999.00', ' 2 Days / 1 Night', 10, 'images/tours/tour_680258b991cc1.jpg', NULL, NULL, NULL, 'active', '2025-04-18 13:50:49');

-- --------------------------------------------------------

--
-- Table structure for table `tour_bookings`
--

CREATE TABLE `tour_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `people` int(11) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `booking_status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tour_bookings`
--

INSERT INTO `tour_bookings` (`id`, `user_id`, `tour_id`, `booking_date`, `people`, `special_requests`, `total_amount`, `payment_status`, `booking_status`, `created_at`) VALUES
(28, 4, 14, '2025-04-25', 1, '0', '5999.00', '', 'cancelled', '2025-04-19 05:46:26'),
(29, 4, 14, '2025-04-25', 1, '0', '5999.00', 'paid', 'cancelled', '2025-04-19 05:46:26'),
(30, 6, 14, '2025-05-08', 1, '0', '5999.00', 'paid', 'confirmed', '2025-04-19 16:52:13'),
(31, 8, 15, '2025-05-15', 2, '0', '13998.00', '', 'cancelled', '2025-05-09 02:37:47'),
(32, 8, 15, '2025-05-15', 2, '0', '13998.00', 'paid', 'confirmed', '2025-05-09 02:37:47'),
(33, 8, 13, '2025-05-30', 1, '0', '0.00', 'pending', 'pending', '2025-05-27 14:06:54'),
(34, 11, 12, '2025-08-28', 1, '0', '9999.00', 'paid', 'confirmed', '2025-08-16 18:05:25'),
(35, 11, 12, '2025-08-28', 1, '0', '9999.00', 'pending', 'pending', '2025-08-16 18:05:25'),
(36, 12, 14, '2025-11-11', 1, '0', '5999.00', 'paid', 'confirmed', '2025-09-16 18:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `phone`, `dob`, `password`, `role`, `created_at`) VALUES
(4, 'Aditya  Patil', 'aditya@gmail.com', NULL, '0000000000', '2025-04-19', '$2y$10$qAL6PMlAFoT4sz21JiQleOVxqxMmslR70hhKx1JKABZUxJyYjfjW2', 'user', '2025-04-19 05:42:12'),
(5, 'Ramkrishna Patil', 'rama.patil2006@gmail.com', NULL, '7588192784', '2015-11-12', '$2y$10$avPS4TYcYiapuXJDuv22BO4Ek92QILfYQiJ5s9/wJHCeGIbtKUe0q', 'user', '2025-04-19 09:12:56'),
(6, 'Gajanan Khawade', 'gajanan@gmail.com', NULL, '1234567890', '2004-04-15', '$2y$10$jsKhESHLQ449w/5eTR3XkORrXYxANP8Pjdc1f8gJ1OBuFXzCe2sre', 'user', '2025-04-19 16:51:22'),
(8, 'Rupesh Satale', 'rupesh@gmail.com', NULL, '1112223330', '2004-11-11', '$2y$10$1PMjFF.DO4VTE4y/JpjP3ulel50zk1sQATzRlxylZRZ2P/18s9Ahi', 'user', '2025-05-09 02:35:22'),
(9, 'Abc', 'rcb@gmail.com', NULL, '1234567890', '2025-05-31', '$2y$10$xO5CZrN5DVJpMlxMQwtyJOCpCGIU/5R2qBmKeBpCyJkTQWPRdb5ce', 'user', '2025-05-31 08:01:32'),
(11, 'Sanket King', 'sanketkumar41@gmail.com', NULL, '1234569870', '2018-06-21', '$2y$10$LoyvpVqenta44RlmYGZfqu0BXVdVobNBW689CTT.RqKAxsf47PeIa', 'user', '2025-08-16 18:04:39'),
(12, 'Om Mahajan', 'omsmahajan143@gmail.com', NULL, '0000000000', '2006-02-02', '$2y$10$L8iQdbZ/k/us.tU7SCgd5ejSzHwfQSup4EmmmUGU.gY14F8aGvOnC', 'user', '2025-09-16 18:42:25'),
(13, 'kunal Mahajan', 'kunal@gmail.com', NULL, '1234567890', '2005-04-04', '$2y$10$TgNiI7X9LXgRqiV.lrZYwuwlAuiJ3Z1ZiKrzVumQQIE0rewIVwMt.', 'user', '2025-09-30 19:47:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_bookings`
--
ALTER TABLE `package_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tour_bookings`
--
ALTER TABLE `tour_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tour_id` (`tour_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `package_bookings`
--
ALTER TABLE `package_bookings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `room_bookings`
--
ALTER TABLE `room_bookings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tour_bookings`
--
ALTER TABLE `tour_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tour_bookings`
--
ALTER TABLE `tour_bookings`
  ADD CONSTRAINT `tour_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tour_bookings_ibfk_2` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

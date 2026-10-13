-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 04:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aumerch`
--

-- --------------------------------------------------------

--
-- Table structure for table `supplier_profiles`
--

CREATE TABLE `supplier_profiles` (
  `supplier_id` int(191) NOT NULL,
  `email` varchar(190) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL DEFAULT '',
  `company` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_profiles`
--

INSERT INTO `supplier_profiles` (`supplier_id`, `email`, `password`, `phone`, `company`, `address`, `updated_at`, `role`) VALUES
(1, 'supplier@supplier.com', 'samplepass', '12121212', 'JB', 'ssss', '2026-09-27 14:35:37', 'supplier');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `admin_id` int(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`admin_id`, `username`, `password`, `first_name`, `last_name`, `email`, `role`) VALUES
(1, 'admin', 'admin', 'John Benedict', 'Villegas', 'jbofficial123@gmail.com', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_categories`
--

CREATE TABLE `tbl_categories` (
  `category_id` int(50) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_logs`
--

CREATE TABLE `tbl_logs` (
  `log_id` int(11) NOT NULL,
  `product_id` int(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `quantity_changed` int(11) DEFAULT NULL,
  `previous_stock` int(11) DEFAULT NULL,
  `new_stock` int(11) DEFAULT NULL,
  `date_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

CREATE TABLE `tbl_products` (
  `product_id` int(50) NOT NULL,
  `category_id` int(50) NOT NULL,
  `product_name` varchar(55) NOT NULL,
  `color` varchar(50) NOT NULL,
  `variation` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `mini_desc` varchar(255) NOT NULL,
  `price` varchar(50) NOT NULL,
  `stock` int(50) NOT NULL,
  `image` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `category_id`, `product_name`, `color`, `variation`, `description`, `mini_desc`, `price`, `stock`, `image`, `status`) VALUES
(1, 0, 'AU Hoodie', 'Navy', 'Premium Cotton | Navy', 'Stay comfortable while representing Arellano University with the official AU Hoodie. Designed for students who want a casual and stylish look, this hoodie features a soft, comfortable fabric that is perfect for everyday campus wear.', 'Stay cozy, stay proud. Official Arellano University hoodie designed for comfort, style and school spirit', '500', 4, 'hoodie.png', 'Available'),
(2, 1, 'AU Shirt', 'White', 'Premium Cotton', 'Show your Chief spirit with this Classic Arellano University Seal T-Shirt! Featuring the iconic AU logo prominently printed on a crisp white backdrop, this shirt is designed for everyday comfort whether you\'re attending online classes, strolling around ca', 'Show your Chief spirit with this Classic Arellano University Seal T-Shirt! Featuring the iconic AU logo prominently printed on a crisp white backdrop', '150', 0, 'aushirt.png', 'Available'),
(3, 0, 'AU Pants', 'Blue', 'Premium Cotton', 'Complete your campus or PE look with the official AU Classic Royal Blue Track Pants! Featuring bold side-stripe paneling with \"ARELLANO UNIVERSITY\" vertical typography and the official university seal on the hip. Built with an elastic waistband and adjust', 'Complete your campus or PE look with the official AU Classic Royal Blue Track Pants! ', '100', 3, 'au_pants.png', 'Available'),
(4, 0, 'AU NSTP-CWTS Shirt', 'White', 'Cotton', 'Ready for community service and fieldwork? Get the official AU NSTP-CWTS Shirt! Featuring the custom circular National Service Training Program - Civic Welfare Training Service emblem printed on a clean white tee. Breathable, light', 'Get the official AU NSTP-CWTS Shirt! Featuring the custom circular National Service Training Program', '199', 4, 'nstp.png', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_requests`
--

CREATE TABLE `tbl_stock_requests` (
  `request_id` int(11) NOT NULL,
  `product_id` int(50) NOT NULL,
  `requested_qty` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Submitted',
  `requested_by` int(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `variant` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_supplier_logs`
--

CREATE TABLE `tbl_supplier_logs` (
  `log_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `product_id` int(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Approved',
  `date_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(20) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `first_name` varchar(55) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `student_id`, `first_name`, `last_name`, `email`, `password`, `role`) VALUES
(1, '24-00740', 'Jadrien Roi', 'Aguilar', 'jadrien@gmail.com', 'samplepass', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `supplier_profiles`
--
ALTER TABLE `supplier_profiles`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `tbl_logs`
--
ALTER TABLE `tbl_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `tbl_stock_requests`
--
ALTER TABLE `tbl_stock_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `fk_request_product` (`product_id`),
  ADD KEY `fk_request_user` (`requested_by`);

--
-- Indexes for table `tbl_supplier_logs`
--
ALTER TABLE `tbl_supplier_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `supplier_profiles`
--
ALTER TABLE `supplier_profiles`
  MODIFY `supplier_id` int(191) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `admin_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  MODIFY `category_id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_logs`
--
ALTER TABLE `tbl_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_products`
--
ALTER TABLE `tbl_products`
  MODIFY `product_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_stock_requests`
--
ALTER TABLE `tbl_stock_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_supplier_logs`
--
ALTER TABLE `tbl_supplier_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 25, 2026 at 02:27 PM
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
-- Database: `GoWithMe`
--

-- --------------------------------------------------------

--
-- Table structure for table `accepts`
--

CREATE TABLE `accepts` (
  `User_ID` int(10) NOT NULL,
  `Req_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `BlockedBy_ID` int(10) NOT NULL,
  `Blocked_ID` int(10) NOT NULL,
  `Date` date NOT NULL,
  `Reason` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `destination`
--

CREATE TABLE `destination` (
  `R_ID` int(10) NOT NULL,
  `Area` varchar(30) NOT NULL,
  `City` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `User_ID` int(10) NOT NULL,
  `Favored_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `go_by`
--

CREATE TABLE `go_by` (
  `R_ID` int(10) NOT NULL,
  `Type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instituition`
--

CREATE TABLE `instituition` (
  `Name` varchar(50) NOT NULL,
  `Area` varchar(20) NOT NULL,
  `City` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instituition`
--

INSERT INTO `instituition` (`Name`, `Area`, `City`) VALUES
('East West University', 'Aftabnagar', 'Dhaka'),
('BRAC University', 'Badda', 'Dhaka'),
('North South University', 'Bashundhara', 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `meetup`
--

CREATE TABLE `meetup` (
  `R_ID` int(10) NOT NULL,
  `Area` varchar(20) NOT NULL,
  `City` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `place`
--

CREATE TABLE `place` (
  `Area` varchar(30) NOT NULL,
  `City` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `place`
--

INSERT INTO `place` (`Area`, `City`) VALUES
('Aftabnagar', 'Dhaka'),
('Badda', 'Dhaka'),
('Banani', 'Dhaka'),
('Bashundhara', 'Dhaka'),
('Gulshan', 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `User_ID` int(10) NOT NULL,
  `Req_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prefers`
--

CREATE TABLE `prefers` (
  `R_ID` int(10) NOT NULL,
  `I_Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `ID` int(10) NOT NULL,
  `Date` date NOT NULL,
  `Time` time NOT NULL,
  `Status` enum('Found','Searching') NOT NULL,
  `Age_range` int(100) NOT NULL,
  `Number_of_people` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `texts`
--

CREATE TABLE `texts` (
  `Receiver_ID` int(10) NOT NULL,
  `Sender_ID` int(10) NOT NULL,
  `Message` varchar(1000) NOT NULL,
  `Message_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_mode`
--

CREATE TABLE `transport_mode` (
  `Type` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_mode`
--

INSERT INTO `transport_mode` (`Type`) VALUES
('Bike'),
('Bus'),
('Car'),
('Helicopter'),
('Plane'),
('Train'),
('Truck'),
('Walk');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `ID` int(10) NOT NULL,
  `Name` varchar(20) NOT NULL,
  `Password` varchar(20) NOT NULL,
  `Phone` int(11) NOT NULL,
  `Gender` enum('Male','Female','Prefer not to say') NOT NULL,
  `Age` int(3) NOT NULL,
  `Instituition` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `Name`, `Password`, `Phone`, `Gender`, `Age`, `Instituition`) VALUES
(1, 'Safwan', '1234', 123456789, 'Male', 23, 'BRAC University');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accepts`
--
ALTER TABLE `accepts`
  ADD PRIMARY KEY (`User_ID`,`Req_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Req_ID` (`Req_ID`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`BlockedBy_ID`,`Blocked_ID`),
  ADD KEY `User_ID` (`BlockedBy_ID`),
  ADD KEY `Blocked_ID` (`Blocked_ID`);

--
-- Indexes for table `destination`
--
ALTER TABLE `destination`
  ADD PRIMARY KEY (`R_ID`,`Area`,`City`),
  ADD KEY `R_ID` (`R_ID`),
  ADD KEY `Area` (`Area`,`City`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`User_ID`,`Favored_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Favored_ID` (`Favored_ID`);

--
-- Indexes for table `go_by`
--
ALTER TABLE `go_by`
  ADD PRIMARY KEY (`R_ID`,`Type`),
  ADD KEY `R_ID` (`R_ID`),
  ADD KEY `Type` (`Type`);

--
-- Indexes for table `instituition`
--
ALTER TABLE `instituition`
  ADD PRIMARY KEY (`Name`),
  ADD KEY `Area` (`Area`,`City`);

--
-- Indexes for table `meetup`
--
ALTER TABLE `meetup`
  ADD PRIMARY KEY (`R_ID`,`Area`,`City`),
  ADD KEY `R_ID` (`R_ID`),
  ADD KEY `Area` (`Area`,`City`);

--
-- Indexes for table `place`
--
ALTER TABLE `place`
  ADD PRIMARY KEY (`Area`,`City`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`User_ID`,`Req_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Req_ID` (`Req_ID`);

--
-- Indexes for table `prefers`
--
ALTER TABLE `prefers`
  ADD PRIMARY KEY (`R_ID`,`I_Name`),
  ADD KEY `I_Name` (`I_Name`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `texts`
--
ALTER TABLE `texts`
  ADD PRIMARY KEY (`Message_ID`),
  ADD KEY `Receiver_ID` (`Receiver_ID`),
  ADD KEY `Sender_ID` (`Sender_ID`);

--
-- Indexes for table `transport_mode`
--
ALTER TABLE `transport_mode`
  ADD PRIMARY KEY (`Type`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Instituition` (`Instituition`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `texts`
--
ALTER TABLE `texts`
  MODIFY `Message_ID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accepts`
--
ALTER TABLE `accepts`
  ADD CONSTRAINT `accepts_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `accepts_ibfk_2` FOREIGN KEY (`Req_ID`) REFERENCES `requests` (`ID`);

--
-- Constraints for table `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `blocks_ibfk_1` FOREIGN KEY (`BlockedBy_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `blocks_ibfk_2` FOREIGN KEY (`Blocked_ID`) REFERENCES `user` (`ID`);

--
-- Constraints for table `destination`
--
ALTER TABLE `destination`
  ADD CONSTRAINT `destination_ibfk_1` FOREIGN KEY (`R_ID`) REFERENCES `requests` (`ID`),
  ADD CONSTRAINT `destination_ibfk_2` FOREIGN KEY (`Area`,`City`) REFERENCES `place` (`Area`, `City`);

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`Favored_ID`) REFERENCES `user` (`ID`);

--
-- Constraints for table `go_by`
--
ALTER TABLE `go_by`
  ADD CONSTRAINT `go_by_ibfk_1` FOREIGN KEY (`R_ID`) REFERENCES `requests` (`ID`),
  ADD CONSTRAINT `go_by_ibfk_2` FOREIGN KEY (`Type`) REFERENCES `transport_mode` (`Type`);

--
-- Constraints for table `instituition`
--
ALTER TABLE `instituition`
  ADD CONSTRAINT `instituition_ibfk_1` FOREIGN KEY (`Area`,`City`) REFERENCES `place` (`Area`, `City`);

--
-- Constraints for table `meetup`
--
ALTER TABLE `meetup`
  ADD CONSTRAINT `meetup_ibfk_1` FOREIGN KEY (`Area`,`City`) REFERENCES `place` (`Area`, `City`),
  ADD CONSTRAINT `meetup_ibfk_2` FOREIGN KEY (`R_ID`) REFERENCES `requests` (`ID`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`Req_ID`) REFERENCES `requests` (`ID`);

--
-- Constraints for table `prefers`
--
ALTER TABLE `prefers`
  ADD CONSTRAINT `prefers_ibfk_1` FOREIGN KEY (`R_ID`) REFERENCES `requests` (`ID`),
  ADD CONSTRAINT `prefers_ibfk_2` FOREIGN KEY (`I_Name`) REFERENCES `instituition` (`Name`);

--
-- Constraints for table `texts`
--
ALTER TABLE `texts`
  ADD CONSTRAINT `texts_ibfk_1` FOREIGN KEY (`Receiver_ID`) REFERENCES `user` (`ID`),
  ADD CONSTRAINT `texts_ibfk_2` FOREIGN KEY (`Sender_ID`) REFERENCES `user` (`ID`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`Instituition`) REFERENCES `instituition` (`Name`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

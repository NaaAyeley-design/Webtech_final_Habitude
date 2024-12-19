-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2024 at 07:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

use `webtech_fall2024_ayeley_aryee`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webtech_habitude`
--

-- --------------------------------------------------------

--
-- Table structure for table `board_images`
--

CREATE TABLE `board_images` (
  `image_id` int(11) NOT NULL,
  `board_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_name` varchar(100) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `board_images`
--

INSERT INTO `board_images` (`image_id`, `board_id`, `image_path`, `image_name`, `upload_date`) VALUES
(2, 2, 'uploads/vision_boards/675f54a84c928_BED.jpeg', '675f54a84c928_BED.jpeg', '2024-12-15 22:14:00'),
(8, 12, 'uploads/vision_boards/6760c6179aaa4_919e0549-a0fe-480b-9a13-b1136b3a8422.jpeg', '6760c6179aaa4_919e0549-a0fe-480b-9a13-b1136b3a8422.jpeg', '2024-12-17 00:30:15'),
(10, 16, 'uploads/vision_boards/6760ce234eaf8_travel1.jpeg', '6760ce234eaf8_travel1.jpeg', '2024-12-17 01:04:35'),
(11, 16, 'uploads/vision_boards/6760ce234f7cd_travel2.jpeg', '6760ce234f7cd_travel2.jpeg', '2024-12-17 01:04:35'),
(12, 18, '../uploads/vision_boards/676162958855a_1734435477.jpeg', '', '2024-12-17 11:37:57'),
(13, 18, '../uploads/vision_boards/67616295890ba_1734435477.jpeg', '', '2024-12-17 11:37:57'),
(14, 18, '../uploads/vision_boards/6761629589dc0_1734435477.jpeg', '', '2024-12-17 11:37:57'),
(15, 18, '../uploads/vision_boards/676162958a47e_1734435477.jpeg', '', '2024-12-17 11:37:57');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `entry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mood` varchar(50) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`entry_id`, `user_id`, `content`, `created_at`, `updated_at`, `mood`, `tags`, `is_favorite`) VALUES
(12, 1, 'I am tired', '2024-12-16 02:44:04', '2024-12-16 02:44:04', 'neutral', '[\"\"]', 0),
(15, 17, 'Here\'s an example of a random journal entry:  \n---  \n\n**Journal Entry – October 15, 2024**  \n\n**Title: A Quiet Reflection on Change**  \n\nToday was one of those days where time seemed to slow down just enough for me to feel it. It wasn’t particularly eventful or exciting—no grand breakthroughs, no crises—but there was a quietness that I found both comforting and disconcerting.  \n\nI woke up earlier than usual, just as the sun crept through my window. The light was soft, golden, and hesitant, as though the day itself wasn’t sure if it was ready to start. For the first time in weeks, I didn’t rush to check my phone or think about my endless to-do list. Instead, I just sat there, listening to the birds outside and the sound of my own breathing. It felt strange to be still.  \n\nLately, life has felt like a treadmill I can’t quite step off. Assignments, meetings, expectations—everything pulling me forward, faster and faster, like I’m supposed to keep running but don’t know where I’m going. Maybe it’s just the mid-semester slump, but I’ve been thinking a lot about how easy it is to lose yourself in the noise of productivity.  \n\nToday, though, I allowed myself to slow down. I went for a walk under the pretense of “getting fresh air,” but it turned into something more. The streets were quieter than usual, and the air smelled like rain, even though the skies were clear. I noticed things I don’t usually pay attention to—cracks in the sidewalk shaped like rivers, an old man on his balcony humming to himself, the way the leaves are starting to turn red and gold. I remembered how my grandmother used to say that autumn was proof that change could be beautiful.  \n\nIt made me wonder why change often feels so heavy. I’ve been holding onto a lot lately—old habits, old fears, relationships that no longer serve me—because I’m scared of what happens if I let go. But maybe change doesn’t have to be so intimidating. Maybe it’s just like the leaves: a natural part of growth, something to embrace instead of resist.  \n\nWhen I got home, I brewed myself a cup of tea and sat with my thoughts for a while. I realized that I don’t need to have all the answers right now. Sometimes, it’s enough to just show up for yourself, even in small ways. Today, I did that. I slowed down, I paid attention, and I remembered that I’m a work in progress—and that’s okay.  \n\nTomorrow might be chaotic again. But for today, I’ll hold onto this quiet moment, this little reminder that I don’t need to rush through life to make it meaningful.', '2024-12-17 09:54:18', '2024-12-17 09:54:18', 'sad', '[\"#Tired of all this...lol\"]', 0),
(16, 2, 'I really tried with this website. Well done to me', '2024-12-17 11:36:30', '2024-12-17 11:36:30', 'happy', '#Amazing!', 0),
(18, 1, 'drtfvygbhjkml,', '2024-12-17 13:36:33', '2024-12-17 13:36:33', 'neutral', '[\"n\"]', 0),
(19, 1, 'we can do this', '2024-12-17 13:55:05', '2024-12-17 13:55:05', 'happy', '[\"w\"]', 0),
(20, 20, 'This is me', '2024-12-17 14:23:12', '2024-12-17 14:23:12', 'neutral', '[\"a\"]', 0),
(21, 20, 'Highlanders - 15 points i believe we can', '2024-12-17 14:54:18', '2024-12-17 14:54:18', 'excited', '[\"\"]', 0),
(22, 20, 'soon time, she will be there', '2024-12-17 14:54:42', '2024-12-17 14:54:42', 'calm', '[\"\"]', 0),
(23, 23, 'I am happy', '2024-12-17 18:10:47', '2024-12-17 18:10:47', 'neutral', '[\"\"]', 0);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_tags`
--

CREATE TABLE `journal_entry_tags` (
  `entry_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_tags`
--

CREATE TABLE `journal_tags` (
  `tag_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timer_preferences`
--

CREATE TABLE `timer_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `default_mode` varchar(50) DEFAULT 'Pomodoro',
  `default_duration` int(11) DEFAULT 1500,
  `sound_enabled` tinyint(1) DEFAULT 1,
  `last_meditation_mode` varchar(50) DEFAULT 'breathing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timer_preferences`
--

INSERT INTO `timer_preferences` (`preference_id`, `user_id`, `default_mode`, `default_duration`, `sound_enabled`, `last_meditation_mode`) VALUES
(2, 2, 'Meditation', 1500, 1, 'breathing'),
(3, 1, 'Pomodoro', 600, 1, 'breathing');

-- --------------------------------------------------------

--
-- Table structure for table `timer_sessions`
--

CREATE TABLE `timer_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mode_type` varchar(50) NOT NULL,
  `duration` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timer_sessions`
--

INSERT INTO `timer_sessions` (`session_id`, `user_id`, `mode_type`, `duration`, `completed_at`) VALUES
(1, 20, 'Long Break', 60, '2024-12-17 15:11:49'),
(2, 20, 'Mindfulness', 60, '2024-12-17 15:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `role_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `created_at`, `last_login`, `is_active`, `role_id`) VALUES
(1, 'ayeley.aryee@ashesi.edu.gh', '$2y$10$auDmsQelr3qqPyBscrtAb.HSXX/QiTaOh1/KZJMqzbGP5rA.zmLfu', '2024-12-14 18:30:45', NULL, 1, 1),
(2, 'naa@gmail.com', '$2y$10$Eh/5LG5FItWvgS3y14U9wudhE3/Kb4l5bXMMCx3KM4i9jDOk8W.ee', '2024-12-14 18:52:06', NULL, 1, 2),
(15, 'susan@ashesi.edu.gh', '$2y$10$KojPkUH17I2iYnGr99Y6GesRvg1H0KXPRM0GDBzB5BVNjBSJP5bsu', '2024-12-16 16:43:57', NULL, 1, 1),
(16, 'qaswedrtyue@gmail.com', '$2y$10$2u9ilkbRqTCb/iGkRLOWley1REJ9by8Xd9kG8jDZ55TYRxu7aFZtu', '2024-12-16 16:50:32', NULL, 1, 1),
(17, 'araba@gmail.com', '$2y$10$Toq51.H6esQ93iPhr5ujReLQpQZFELoRwRMdlftajiJpzE95WCfS6', '2024-12-17 09:52:13', NULL, 1, 1),
(18, 'asante@gmail.com', '$2y$10$ZoisljBQ96Kl9XCjJkKZyOrMSysegmTzym8.b3ukEkAGxo73TsArG', '2024-12-17 12:06:24', NULL, 1, 1),
(19, 'naaayeley@gmail.com', '$2y$10$Dw2ajziROwRQGOM.1iQwDenHQS0uN57iU25KG1jl2mJHNh6xkHjyK', '2024-12-17 12:57:26', NULL, 1, 1),
(20, 'JAsante@gmail.com', '$2y$10$4Gz2QiPwWAsCbQdJsrDmbeWgfW57GfMA154kGOJVdU0T2ddoua/3G', '2024-12-17 14:00:57', NULL, 1, 1),
(21, 'naasante@gmail.com', '$2y$10$5skRStbsSto/7xwubq1x4O8gddNm54ey3gvn4taqBmC1e/PHd7sRO', '2024-12-17 17:47:03', NULL, 1, 1),
(22, 'suezwen@gmail.com', '$2y$10$QV2K0KEe72B32p.sXGyuD.gGaBsemyf9rtWAJulL8SGTnt2cWjIA.', '2024-12-17 18:07:34', NULL, 1, 1),
(23, 'esi@gmail.com', '$2y$10$qAHjFRjWnaWQfFWzX0aT5.SW6unE/G6zajrdhQbTsq0xP/qrIPnk2', '2024-12-17 18:08:43', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `user_id`, `first_name`, `last_name`) VALUES
(1, 1, 'Naa', 'Adu-Aryee'),
(2, 2, 'Naa', 'Adu-Aryee'),
(5, 15, 'Naa Ayeley', 'Adu-Aryee'),
(6, 16, 'Naa', 'Bale'),
(7, 17, 'Naa', 'Ayeley'),
(8, 18, 'Naa', 'Asante'),
(9, 19, 'Naa', 'Asante'),
(10, 20, 'James', 'Asante'),
(11, 21, 'Naa', 'Asante'),
(12, 22, 'Susan', 'Zwennes'),
(13, 23, 'Esi', 'Adu-Aryee');

-- --------------------------------------------------------

--
-- Table structure for table `vision_boards`
--

CREATE TABLE `vision_boards` (
  `board_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vision_boards`
--

INSERT INTO `vision_boards` (`board_id`, `user_id`, `title`, `description`, `created_at`, `updated_at`) VALUES
(2, 1, 'My House', 'A look into my dream house', '2024-12-15 22:14:00', '2024-12-16 14:30:50'),
(10, 1, 'Shopping List', 'Things I\'m getting myself', '2024-12-17 00:25:37', '2024-12-17 00:25:37'),
(11, 1, 'Food😫😍', 'I love to eat good food', '2024-12-17 00:28:54', '2024-12-17 00:28:54'),
(12, 1, 'Shoes', 'shoesssss', '2024-12-17 00:30:15', '2024-12-17 00:30:15'),
(14, 1, 'Pinterest', 'Stuff I saw on pinterest', '2024-12-17 00:40:53', '2024-12-17 00:40:53'),
(16, 1, 'My Life', '', '2024-12-17 01:04:35', '2024-12-17 01:04:35'),
(17, 1, '2025 vision board', 'Things I want for the new year', '2024-12-17 09:56:16', '2024-12-17 09:56:16'),
(18, 1, 'My dream eatery', 'Ideas for the restaurant I want to make', '2024-12-17 11:37:57', '2024-12-17 11:37:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `board_images`
--
ALTER TABLE `board_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `board_id` (`board_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `journal_entry_tags`
--
ALTER TABLE `journal_entry_tags`
  ADD PRIMARY KEY (`entry_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `journal_tags`
--
ALTER TABLE `journal_tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `unique_user_tag` (`user_id`,`tag_name`);

--
-- Indexes for table `timer_preferences`
--
ALTER TABLE `timer_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_email` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `vision_boards`
--
ALTER TABLE `vision_boards`
  ADD PRIMARY KEY (`board_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `board_images`
--
ALTER TABLE `board_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `journal_tags`
--
ALTER TABLE `journal_tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timer_preferences`
--
ALTER TABLE `timer_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vision_boards`
--
ALTER TABLE `vision_boards`
  MODIFY `board_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `board_images`
--
ALTER TABLE `board_images`
  ADD CONSTRAINT `board_images_ibfk_1` FOREIGN KEY (`board_id`) REFERENCES `vision_boards` (`board_id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `journal_entry_tags`
--
ALTER TABLE `journal_entry_tags`
  ADD CONSTRAINT `journal_entry_tags_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`entry_id`),
  ADD CONSTRAINT `journal_entry_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `journal_tags` (`tag_id`);

--
-- Constraints for table `journal_tags`
--
ALTER TABLE `journal_tags`
  ADD CONSTRAINT `journal_tags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `timer_preferences`
--
ALTER TABLE `timer_preferences`
  ADD CONSTRAINT `timer_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `timer_sessions`
--
ALTER TABLE `timer_sessions`
  ADD CONSTRAINT `timer_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `vision_boards`
--
ALTER TABLE `vision_boards`
  ADD CONSTRAINT `vision_boards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

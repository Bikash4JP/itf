-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 03:28 AM
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
-- Database: `itf`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicant`
--

CREATE TABLE `applicant` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `furigana` varchar(255) NOT NULL,
  `roman_name` varchar(255) NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `dob` date NOT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `marital_status` enum('Single','Married') NOT NULL,
  `address` text NOT NULL,
  `postal_code` varchar(10) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `height_cm` int(11) DEFAULT NULL,
  `weight_kg` int(11) DEFAULT NULL,
  `passport_have` enum('Yes','No') NOT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `passport_expiry` date DEFAULT NULL,
  `migration_history` text DEFAULT NULL,
  `recent_migration_entry` date DEFAULT NULL,
  `recent_migration_exit` date DEFAULT NULL,
  `residency_status` varchar(100) DEFAULT NULL,
  `residency_expiry` date DEFAULT NULL,
  `education` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`education`)),
  `work_experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`work_experience`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`certifications`)),
  `self_intro` text NOT NULL,
  `motivation` text NOT NULL,
  `job_preference` text DEFAULT NULL,
  `uploads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`uploads`)),
  `resume_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant`
--

INSERT INTO `applicant` (`id`, `job_id`, `fullname`, `furigana`, `roman_name`, `nationality`, `gender`, `religion`, `dob`, `birth_place`, `marital_status`, `address`, `postal_code`, `phone`, `email`, `height_cm`, `weight_kg`, `passport_have`, `passport_number`, `passport_expiry`, `migration_history`, `recent_migration_entry`, `recent_migration_exit`, `residency_status`, `residency_expiry`, `education`, `work_experience`, `certifications`, `self_intro`, `motivation`, `job_preference`, `uploads`, `resume_path`, `created_at`) VALUES
(21, 13, 'THAPA BIKASH', 'タパ　ビカス', 'THAPA BIKASH', '中国国籍', 'Male', 'キリスト教', '1998-09-04', 'nepal', '', '東京都千代田区3-7-19山口コーポ２', '100-0000', '09086165634', 'bikash4jp@gmail.com', 170, 70, 'No', NULL, NULL, '0', NULL, NULL, NULL, NULL, '[{\"institution_name\":\"YSE ,\\u5c02\\u9580\\u5b66\\u6821\",\"institution_address\":\"3-7-19\",\"join_date\":\"2022\\/03\\/22\",\"leave_date\":\"\",\"faculty\":\"IT\",\"major\":\"SE\",\"status\":\"Ongoing\"}]', '[{\"company_name\":\"IT\",\"company_address\":\"3-7-19\",\"business_type\":\"IT\",\"job_role\":\"IT \\u307e\\u306a\\u3052\\uff52\",\"join_date\":\"2025\\/04\\/01\",\"leave_date\":\"\",\"current_status\":\"Current\"}]', '[{\"type\":\"Japanese\",\"name\":\"JLPT N2\",\"score\":\"1601\",\"date_obtained\":\"2020\\/02\\/02\"}]', 'df', 'gd', 'gdgdgd', '[{\"type\":\"Photo\",\"path\":\"..\\/uploads\\/681713e96210c_aboutimg.png\"}]', '../resumes/21_resume.xlsx', '2025-05-04 07:14:49'),
(22, 14, 'BIKASH THAPA', 'タパ　ビカス', 'THAPA BIKASH', 'ベトナム国籍', 'Male', 'ヒンドゥー教', '1998-09-04', 'ネパール', '', 'Kanagawa大和市7-18,Chuo3-Chome`yamaguti kopo 2', '242-0021', '09086165634', 'bikash4jp@gmail.com', 180, 72, 'No', NULL, NULL, '0', NULL, NULL, NULL, NULL, '[{\"institution_name\":\"YOKOHAMA\\u3000SYSTEM\",\"institution_address\":\"\\u5927\\u548c\\u5e02\\u4e2d\\u592e3-7-19 \\u5c71\\u53e3\\u30b3\\u30dd\\u30fc2\\u3000\\u3000\\uff12\\uff10\\uff15\\u53f7\",\"join_date\":\"2022\\/03\\/03\",\"leave_date\":\"2024\\/03\\/03\",\"faculty\":\"IT\",\"major\":\"IT\",\"status\":\"Graduated\"}]', '[{\"company_name\":\"\\u682a\\u5f0f\\u4f1a\\u793eITF\",\"company_address\":\"\\u6771\\u4eac\\u90fd\\u5927\\u7530\",\"business_type\":\"\\u5916\\u56fd\\u4eba\\u6750\",\"job_role\":\"IT\\u7ba1\\u7406\\u8005\",\"join_date\":\"2020\\/03\\/02\",\"leave_date\":\"\",\"current_status\":\"Current\"}]', '[{\"type\":\"Japanese\",\"name\":\"JLPTN2\",\"score\":\"PASS\",\"date_obtained\":\"2023\\/12\\/01\"}]', 'I am Bikash Thapa ORinginally from Nepal ,The country of Himalayas .', '入りたいから入りたいです。」', 'どこでも、どれでも', '[{\"type\":\"Photo\",\"path\":\"..\\/uploads\\/681ab2fee3a75_aboutimg.png\"}]', '../resumes/22_resume.xlsx', '2025-05-07 01:10:22'),
(23, 13, 'BIKASH THAPA', 'タパ　ビカス', 'THAPA BIKASH', 'その他', 'Male', 'キリスト教', '1998-09-04', 'ネパール', '', 'Kanagawareghr7-18,Chuo3-Chome`yamaguti kopo 2', '242-0021', '09086165634', 'bikash4jp@gmail.com', 180, 72, 'No', NULL, NULL, '0', NULL, NULL, NULL, NULL, '[{\"institution_name\":\"\\uff39\\uff33\\uff25\",\"institution_address\":\"7-18,Chuo3-Chome\",\"join_date\":\"2022\\/03\\/03\",\"leave_date\":\"2024\\/03\\/03\",\"faculty\":\"IT\",\"major\":\"IT\",\"status\":\"Graduated\"}]', '[{\"company_name\":\"\\u5c71\\u53e3\\u30b3\\u30fc\\u30ddII\",\"company_address\":\"7-18,Chuo3-Chome\",\"business_type\":\"\\u5c71\\u53e3\\u30b3\\u30fc\\u30ddII\",\"job_role\":\"FULL TIME\",\"join_date\":\"2020\\/03\\/02\",\"leave_date\":\"\",\"current_status\":\"Current\"}]', '[{\"type\":\"Japanese\",\"name\":\"JLPTN2\",\"score\":\"PASS\",\"date_obtained\":\"2023\\/12\\/01\"}]', 'jhbnvc', 'hgfdcxbvsf', 'bsgfcb', '[{\"type\":\"Photo\",\"path\":\"..\\/uploads\\/681af0a7972e9_aboutimg.png\"}]', '../resumes/23_resume.xlsx', '2025-05-07 05:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `post_type` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `job_location` varchar(255) DEFAULT NULL,
  `job_category` varchar(50) DEFAULT NULL,
  `job_type` varchar(50) DEFAULT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `bonuses` tinyint(4) DEFAULT NULL,
  `bonus_amount` varchar(100) DEFAULT NULL,
  `living_support` tinyint(4) DEFAULT NULL,
  `rent_support` varchar(100) DEFAULT NULL,
  `insurance` tinyint(4) DEFAULT NULL,
  `transportation_charges` tinyint(4) DEFAULT NULL,
  `transport_amount_limit` varchar(100) DEFAULT NULL,
  `salary_increment` tinyint(4) DEFAULT NULL,
  `increment_condition` text DEFAULT NULL,
  `japanese_level` varchar(50) DEFAULT NULL,
  `experience` varchar(255) DEFAULT NULL,
  `minimum_leave_per_year` varchar(50) DEFAULT NULL,
  `employee_size` varchar(50) DEFAULT NULL,
  `required_vacancy` varchar(50) DEFAULT NULL,
  `date` date NOT NULL,
  `posted_by` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `staff_id`, `post_type`, `category`, `title`, `summary`, `content`, `image`, `company_name`, `job_location`, `job_category`, `job_type`, `salary`, `bonuses`, `bonus_amount`, `living_support`, `rent_support`, `insurance`, `transportation_charges`, `transport_amount_limit`, `salary_increment`, `increment_condition`, `japanese_level`, `experience`, `minimum_leave_per_year`, `employee_size`, `required_vacancy`, `date`, `posted_by`, `created_at`) VALUES
(3, 4, 'job', NULL, '東京オフィスでスタップの募集しています。', 'ITFはタイのトップ人材紹介会社と提携し、タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。タイ人エンジニアの日本企業への紹介を強化します。 ', '英語と日本語のバイリンガル通訳者を募集開始。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。外国人材と企業のスムーズなコミュニケーションをサポートします。', NULL, 'OIC　株式会社', '東京都', '工場作業員', '正社員', '250000', 0, '', 0, '', 0, 0, '', 0, '', 'N3', '3年間', '120', '45', '14', '2025-04-24', 'staff001', '2025-04-24 00:00:00'),
(6, 4, 'news', '入社情報', '2 peoplre joiin in rokujyukksi ', 'ai  gfh  f g y hdhf h f hf gfrt hthds gd gdf rthdfgh h', 'fgkhdfig lgjh rtgkj fj jhggy r hryh y dyjtuy ruyytyk jtr jtyeyu tyuvt7yuhert uryuu57u tyg ju uj67e bu u76y, 67r uhj rykyjjg,ku y ujugfj uyr kyuj uyjyfdjyu ft', '../uploads/680aeaa18e1cb_Screenshot 2025-04-23 110826.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-25', 'staff001', '2025-04-25 14:45:00'),
(7, 4, 'news', 'その他', 'this is a sample post and it shoud be in top of news list ', 'and as well in the index site . this is a sample post and it shoud be in top of news list ', 'and as well in the index site . this is a sample post and it shoud be in top of news list . i am tyiping whatever i want to type because its just for test , i want to test that if the text is more than the coverage area of the image or media file how its placed , oh god its jut been 100 words , i need 400 more words more ,like a story , oh yes i can write the postem time here as well , this post i am tyiping wite now in 2025 april 25 friday and the time is 3:32 right now but still more to write so i think it will be 3:38 pm while posting , i want to see the post in the top of the list after i preview and post . isnt it enough , let me think !! no its not . i have to type more words .. lets write my favourate songs lyrics , its goes like ..ocean apart , day after day . and i slowly go insane . i hear your voice on lane but it doesnt stop the pain .If I see you next to never But how can we say forever?\r\nWherever you go, whatever you do I will be right here waiting for you Whatever it takes or how my heart breaks I will be right here waiting for you\r\nI took for granted all the times That I thought would last somehow I hear the laughter, I taste the tears But I can\'t get near you now:):):)  hahhaha .i am gonna post the vocalist images as for this post . ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-25', 'staff001', '2025-04-25 15:39:00'),
(8, 4, 'news', '入社情報', 'image display test ', 'should recent one and image should be dispalyed /.', 'I\'m going under and this time I fear there\'s no one to save me\r\nThis all or nothing really got a way of driving me crazy\r\nI need somebody to heal\r\nSomebody to know\r\nSomebody to have\r\nSomebody to hold\r\nIt\'s easy to say\r\nBut it\'s never the same\r\nI guess I kinda liked the way you numbed all the pain\r\nNow the day bleeds\r\nInto nightfall\r\nAnd you\'re not here\r\nTo get me through it all\r\nI let my guard down\r\nAnd then you pulled the rug', '../uploads/680b44d8bbe2c_Screenshot 2025-04-08 151332.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-25', 'staff001', '2025-04-25 17:16:29'),
(9, 3, 'news', '入社情報', 'インドネシアから7名の介護候補者を受け入れ準備中', 'このたび、私たちはインドネシアから7名の優秀な介護候補者を迎える準備を進めております。彼らは、日本での介護業務に従事するため、日本語や介護に関する専門知識をしっかりと学んできた人材です。今後、日本の介護現場における人手不足の解消に大きく貢献してくれることが期待されています。', '現在、インドネシアより7名の介護候補者を受け入れるための最終準備を進めております。彼らは現地で日本語の基礎と介護技術を学び、日本の介護施設で即戦力として活躍できるよう研修を重ねてきました。日本文化やマナーへの理解も深めており、安心して業務を任せられる人材です。\r\n\r\n受け入れにあたっては、就労に必要な手続きやビザ申請、生活支援なども全面的にサポートしてまいります。日本の高齢化が進む中、介護分野の人材確保はますます重要になっており、今回の取り組みが現場の負担軽減とサービス向上につながることを期待しています。\r\n\r\n今後も当社は、海外から優秀な人材を積極的に受け入れ、介護業界の発展に貢献してまいります。皆様のご理解とご協力を賜りますよう、よろしくお願い申し上げます。', '../uploads/680f167a2c3fc_chris-montgomery-smgTvepind4-unsplash (1).jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-28', 'bikash', '2025-04-28 14:47:40'),
(13, 1, 'job', 'caregiving', 'Caregiver Position', 'Looking for a skilled caregiver', 'Job details here', '', 'Sample Company', 'Tokyo', 'Caregiving', 'Full-time', '200000', 0, '50000', 0, 'Yes', 0, 0, '10000', 0, 'Performance-based', 'N4', '1 year', '10', '50', '5', '2025-05-02', '1', '2025-05-02 09:48:00'),
(14, 3, 'job', NULL, '介護スタッフ（訪問介護・施設介護）', '高齢者の生活支援、食事・入浴・排泄などの介助、レクリエーション活動のサポート、記録の作成など。', '高齢者の生活支援、食事・入浴・排泄などの介助、レクリエーション活動のサポート、記録の作成など。\r\n勤務時間\r\nシフト制（実働8時間）\r\n例）\r\n早番：7:00〜16:00\r\n日勤：9:00〜18:00\r\n遅番：11:00〜20:00\r\n夜勤：17:00〜翌9:00（※夜勤手当あり）\r\n', '../uploads/68147de3329b3_kaigo.jpg', 'ABS 株式会社', '東京都', '介護', '正社員', '240000', 1, '300000', 1, '50', 1, 1, '50000', 0, '', 'N3', '1年より上の経験だったら幸いです。', '120', '51', '10', '2025-05-02', 'bikash', '2025-05-02 17:10:17'),
(15, 3, 'job', NULL, 'ソフトウェアエンジニア (Software Engineer)', '京で活躍するソフトウェアエンジニアを募集しています。私たちのチームに参加し、最新のウェブアプリケーションを開発しませんか？主な業務は、JavaScript、React、Node.jsを使用したアプリケーション開発とチームとのコラボレーションです。技術的な課題に挑戦し、新しいスキルを学びたい方を歓迎します。', 'ウェブアプリケーションの設計、開発、保守\r\nJavaScript、React、Node.jsを使用したコーディング\r\nチームメンバーと協力してプロジェクトを推進\r\n新機能の提案と実装\r\n求めるスキル:\r\nJavaScript、React、Node.jsの経験\r\nチームでの開発経験\r\n日本語レベルN2以上\r\n勤務条件:\r\nフレックスタイム制\r\nリモートワーク可（週2日出社）\r\n年間休日120日以上\r\n応募方法:\r\n履歴書とポートフォリオを添付の上、応募してください。', '../uploads/6814855f837ae_software.jpg', 'ETC 株式会社', '東京都', '介護', '正社員', '300000', 0, '', 0, '', 0, 0, '', 0, '', 'N1', '3 年', '105', '41', '10', '2025-05-02', 'bikash', '2025-05-02 17:42:07'),
(16, 4, 'job', NULL, '緑樹会', '介護募集', '内容', '../uploads/681493e29db82_kaigo.jpg', 'しゃかいｊｈ', '神奈川県', '介護', '正社員', '25', 1, '15', 1, '25000', 1, 1, '', 0, '', 'N3', '未経験OK', '110', '1000', '5', '2025-05-02', 'staff001', '2025-05-02 18:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `branches` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `username`, `email`, `password`, `branches`, `phone_number`, `address`, `failed_attempts`, `is_blocked`) VALUES
(1, 'Sample Staff', '', 'staff@example.com', '', NULL, NULL, '', 0, 0),
(3, 'bikash', 'bikash', 'biaksh4jp@gmail.com', '$2y$10$JK4hdTim6GbNA4iUc0Sy3.xUED7IHHTClweCm.8b2LsNIgCn0BxXS', 'tokyo', NULL, '', 0, 0),
(4, '', 'staff001', '', '$2y$10$hz/W3XVJXuBs7CgYvEt5G.XE9CnOtO81eSvT4XTcHMHpM4uLPMDCm', NULL, NULL, '', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicant`
--
ALTER TABLE `applicant`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicant`
--
ALTER TABLE `applicant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant`
--
ALTER TABLE `applicant`
  ADD CONSTRAINT `applicant_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `posts` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

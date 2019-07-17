--
-- Table structure for table `active`
--

CREATE TABLE IF NOT EXISTS `active` (
  `user` int NOT NULL,
  `path` text NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `focused` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `access`
--

CREATE TABLE IF NOT EXISTS `access` (
  `user` int NOT NULL,
  `project` int NOT NULL,
  `level` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE IF NOT EXISTS `options` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `name` varchar(255) UNIQUE NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` text NOT NULL,
  `owner` int NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` text NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `project` int DEFAULT NULL,
  `access` int NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  UNIQUE KEY `username` ( `username` )
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_options`
--

CREATE TABLE IF NOT EXISTS `user_options` (
  `id` int PRIMARY KEY AUTO_INCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `user` int NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `name_user` (`name`,`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

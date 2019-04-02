--
-- Table structure for table options
--

CREATE TABLE IF NOT EXISTS options (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  value text NOT NULL,
  CONSTRAINT option_name UNIQUE (name)
);

-- --------------------------------------------------------

--
-- Table structure for table projects
--

CREATE TABLE IF NOT EXISTS projects (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  path varchar(255) NOT NULL,
  owner varchar(255) NOT NULL,
  access text,
  CONSTRAINT project UNIQUE (path, owner)
);

-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE IF NOT EXISTS users (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  first_name varchar(255) DEFAULT NULL,
  last_name varchar(255) DEFAULT NULL,
  username varchar(255) NOT NULL,
  password text NOT NULL,
  email varchar(255) DEFAULT NULL,
  project varchar(255) DEFAULT NULL,
  access varchar(255) NOT NULL,
  groups text,
  token text,
  CONSTRAINT username UNIQUE (username)
);

--
-- Table structure for table user_options
--

CREATE TABLE IF NOT EXISTS user_options (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(255) NOT NULL,
  username varchar(255) NOT NULL,
  value text NOT NULL,
  CONSTRAINT option_name UNIQUE (name,username)
);

--
-- Table structure for table options
--

CREATE TABLE IF NOT EXISTS options (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name CHAR(255) NOT NULL UNIQUE,
  value TEXT NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table projects
--

CREATE TABLE IF NOT EXISTS projects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name CHAR(255) NOT NULL,
  path CHAR(255) NOT NULL UNIQUE,
  owner CHAR(255) NOT NULL UNIQUE,
  access text
);

-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  first_name CHAR(255) DEFAULT NULL,
  last_name CHAR(255) DEFAULT NULL,
  username CHAR(255) NOT NULL UNIQUE,
  password text NOT NULL,
  email CHAR(255) DEFAULT NULL,
  project CHAR(255) DEFAULT NULL,
  access CHAR(255) NOT NULL,
  groups text,
  token text
);

--
-- Table structure for table user_options
--

CREATE TABLE IF NOT EXISTS user_options (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name CHAR(255) NOT NULL UNIQUE,
  username CHAR(255) NOT NULL UNIQUE,
  value text NOT NULL
);

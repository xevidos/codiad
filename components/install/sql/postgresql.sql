--
-- Table structure for table options
--

CREATE TABLE IF NOT EXISTS options (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL UNIQUE,
  value TEXT NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table projects
--

CREATE TABLE IF NOT EXISTS projects (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  path varchar(255) NOT NULL UNIQUE,
  owner varchar(255) NOT NULL UNIQUE,
  access text
);

-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  first_name varchar(255) DEFAULT NULL,
  last_name varchar(255) DEFAULT NULL,
  username varchar(255) NOT NULL UNIQUE,
  password text NOT NULL,
  email varchar(255) DEFAULT NULL,
  project varchar(255) DEFAULT NULL,
  access varchar(255) NOT NULL,
  groups text,
  token text
);

--
-- Table structure for table user_options
--

CREATE TABLE IF NOT EXISTS user_options (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL UNIQUE,
  username varchar(255) NOT NULL UNIQUE,
  value text NOT NULL
);

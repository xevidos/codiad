--
-- Table structure for table active
--

CREATE TABLE IF NOT EXISTS active (
  "user" integer NOT NULL,
  path text NOT NULL,
  position varchar(255) DEFAULT NULL,
  focused varchar(255) NOT NULL
);

--
-- Table structure for table access
--

CREATE TABLE IF NOT EXISTS access (
  "user" integer NOT NULL,
  project integer NOT NULL,
  level integer NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table options
--

CREATE TABLE IF NOT EXISTS options (
  id SERIAL PRIMARY KEY,
  name varchar(255) UNIQUE NOT NULL,
  value text NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table projects
--

CREATE TABLE IF NOT EXISTS projects (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  path text NOT NULL,
  owner integer NOT NULL
);


-- --------------------------------------------------------

--
-- Table structure for table users
--

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  first_name varchar(255) DEFAULT NULL,
  last_name varchar(255) DEFAULT NULL,
  username varchar(255) UNIQUE NOT NULL,
  password text NOT NULL,
  email varchar(255) DEFAULT NULL,
  project integer DEFAULT NULL,
  access integer NOT NULL,
  token varchar(255) DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table user_options
--

CREATE TABLE IF NOT EXISTS user_options (
  id SERIAL PRIMARY KEY,
  name varchar(255) NOT NULL,
  "user" integer NOT NULL,
  value text NOT NULL,
  UNIQUE (name,"user")
);

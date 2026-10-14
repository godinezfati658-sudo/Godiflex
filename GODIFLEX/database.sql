CREATE DATABASE IF NOT EXISTS godiflex
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE godiflex;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  poster VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY movies_title_unique (title),
  UNIQUE KEY movies_poster_unique (poster)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
  user_id INT UNSIGNED NOT NULL,
  movie_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, movie_id),
  KEY favorites_movie_id_idx (movie_id),
  CONSTRAINT favorites_user_fk FOREIGN KEY (user_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT favorites_movie_fk FOREIGN KEY (movie_id)
    REFERENCES movies (id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO movies (title, poster) VALUES
  ('A Bugs life', 'A Bugs life.jpg'),
  ('Brave', 'Brave.jpg'),
  ('Hoppers', 'Hoppers.jpg'),
  ('Hotel Transylvania', 'Hotel Transylvania.jpg'),
  ('Inside Out', 'Inside Out.jpg'),
  ('The Wild Robot', 'The Wild Robot.jpg'),
  ('Zootopia2', 'Zootopia2.jpg');

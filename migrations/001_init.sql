CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  handle VARCHAR(32) NOT NULL UNIQUE,
  email_verified_at DATETIME NULL,
  confirm_token CHAR(64) NULL,
  reset_token CHAR(64) NULL,
  reset_expires_at DATETIME NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  banned_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  domain VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_companies_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  company_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  r_leadership TINYINT UNSIGNED NOT NULL,
  r_culture TINYINT UNSIGNED NOT NULL,
  r_benefits TINYINT UNSIGNED NOT NULL,
  r_balance TINYINT UNSIGNED NOT NULL,
  r_growth TINYINT UNSIGNED NOT NULL,
  r_exit TINYINT UNSIGNED NOT NULL,
  recommend TINYINT(1) NOT NULL,
  status ENUM('active','auto_hidden','removed') NOT NULL DEFAULT 'active',
  vote_score INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status_created (status, created_at),
  KEY idx_company (company_id),
  FULLTEXT KEY ft_story (title, body),
  CONSTRAINT fk_stories_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_stories_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE votes (
  user_id INT UNSIGNED NOT NULL,
  story_id INT UNSIGNED NOT NULL,
  value TINYINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, story_id),
  CONSTRAINT fk_votes_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_votes_story FOREIGN KEY (story_id) REFERENCES stories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  status ENUM('active','removed') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_story (story_id, status),
  CONSTRAINT fk_comments_story FOREIGN KEY (story_id) REFERENCES stories(id),
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  story_id INT UNSIGNED NOT NULL,
  reporter_user_id INT UNSIGNED NOT NULL,
  corp_email VARCHAR(255) NOT NULL,
  corp_domain VARCHAR(190) NOT NULL,
  is_company_match TINYINT(1) NOT NULL DEFAULT 0,
  reason ENUM('false_info','doxxing','harassment','spam','other') NOT NULL,
  reason_text TEXT NULL,
  verify_code CHAR(6) NULL,
  code_expires_at DATETIME NULL,
  code_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  verified_at DATETIME NULL,
  status ENUM('pending','dismissed','actioned') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_story_reporter (story_id, reporter_user_id),
  KEY idx_status (status, is_company_match, created_at),
  CONSTRAINT fk_reports_story FOREIGN KEY (story_id) REFERENCES stories(id),
  CONSTRAINT fk_reports_user FOREIGN KEY (reporter_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  k VARCHAR(64) PRIMARY KEY,
  v VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (k, v) VALUES ('report_threshold', '3');

CREATE TABLE rate_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_kind_time (user_id, kind, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

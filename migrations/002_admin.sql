-- Admin panel v2: action audit log, moderator-managed email blocklist,
-- and a couple of settings rows. Idempotent-safe on a fresh install.

CREATE TABLE admin_actions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(40) NOT NULL,
  target_type VARCHAR(20) NULL,
  target_id INT UNSIGNED NULL,
  detail VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  KEY idx_admin (admin_id, created_at),
  CONSTRAINT fk_admin_actions_user FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Moderator-managed corp-email blocklist (disposable providers etc.).
-- Checked in addition to the hardcoded freemail list. Suffix-aware at query time.
CREATE TABLE blocked_domains (
  domain VARCHAR(190) PRIMARY KEY,
  added_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO blocked_domains (domain) VALUES
  ('mailinator.com'), ('guerrillamail.com'), ('guerrillamail.net'),
  ('temp-mail.org'), ('tempmail.com'), ('10minutemail.com'),
  ('trashmail.com'), ('yopmail.com'), ('sharklasers.com'),
  ('getnada.com'), ('dispostable.com'), ('maildrop.cc'),
  ('throwawaymail.com'), ('fakeinbox.com'), ('mohmal.com'),
  ('emailondeck.com'), ('mailnesia.com'), ('spamgourmet.com');

INSERT INTO settings (k, v) VALUES ('announcement', '')
  ON DUPLICATE KEY UPDATE v = v;

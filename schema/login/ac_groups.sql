CREATE TABLE IF NOT EXISTS `ac_groups` (
  id TINYINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY ( id )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

TRUNCATE TABLE `ac_groups`;
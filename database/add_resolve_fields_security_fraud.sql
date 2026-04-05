-- Migration: Add lessons_learned and resolvers columns to security_incidents and fraud_incidents
-- Run once against downtimedb

ALTER TABLE `security_incidents`
  ADD COLUMN IF NOT EXISTS `lessons_learned` TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `resolvers`        JSON DEFAULT NULL;

ALTER TABLE `fraud_incidents`
  ADD COLUMN IF NOT EXISTS `lessons_learned` TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `resolvers`        JSON DEFAULT NULL;

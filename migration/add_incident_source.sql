-- Migration: Add incident_source column to incidents table
-- Run once in phpMyAdmin or MySQL CLI before deploying PHP changes.
-- Safe to run on existing data — DEFAULT 'external' backfills all existing rows.
ALTER TABLE incidents
    ADD COLUMN incident_source ENUM('internal', 'external') NOT NULL DEFAULT 'external'
    AFTER priority;

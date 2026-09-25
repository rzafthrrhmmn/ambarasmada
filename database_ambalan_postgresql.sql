-- PostgreSQL dump converted from MySQL
-- Run this on Supabase PostgreSQL

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

-- MySQL dump 10.13  Distrib 9.7.0, for Win64 (x86_64)
--
-- Host: localhost    Database: database_ambalan
-- ------------------------------------------------------
-- Server version	9.7.0

--
-- GTID state at the beginning of the backup 
--

--
-- Table structure for table "activity_guides"
--

DROP TABLE IF EXISTS "activity_guides";

CREATE TABLE "activity_guides" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "kecamatan" varchar(100) NOT NULL,
  "nama" varchar(200) NOT NULL,
  "teks_susunan_upacara" text,
  "checklist_perlengkapan" jsonb DEFAULT NULL,
  "created_by_user_id" bigint DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "activity_guides_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "activity_guides_created_by_user_id_foreign" FOREIGN KEY ("created_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "activity_guides"
--

--
-- Table structure for table "alumni_profiles"
--

DROP TABLE IF EXISTS "alumni_profiles";

CREATE TABLE "alumni_profiles" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "status_saat_ini" varchar(255) NOT NULL DEFAULT 'Lainnya',
  "instansi_kampus" varchar(255) DEFAULT NULL,
  "pekerjaan" varchar(255) DEFAULT NULL,
  "domisili" varchar(255) DEFAULT NULL,
  "media_sosial" varchar(255) DEFAULT NULL,
  "show_contact" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "alumni_profiles_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "alumni_profiles"
--

--
-- Table structure for table "ambalan_medias"
--

DROP TABLE IF EXISTS "ambalan_medias";

CREATE TABLE "ambalan_medias" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "nama" varchar(200) NOT NULL,
  "lirik" text,
  "file_path" varchar(255) DEFAULT NULL,
  "created_by_user_id" bigint DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "ambalan_medias_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "ambalan_medias_created_by_user_id_foreign" FOREIGN KEY ("created_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "ambalan_medias"
--

--
-- Table structure for table "ambalans"
--

DROP TABLE IF EXISTS "ambalans";

CREATE TABLE "ambalans" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "nama" varchar(255) NOT NULL,
  "kode" varchar(255) NOT NULL,
  "alamat" text,
  "status" varchar(255) NOT NULL DEFAULT 'Aktif',
  "logo_path" varchar(255) DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "ambalans"
--

--
-- Table structure for table "angkatans"
--

DROP TABLE IF EXISTS "angkatans";

CREATE TABLE "angkatans" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "angkatan" varchar(255) NOT NULL,
  "nomor" varchar(3) NOT NULL DEFAULT '001',
  "nama" varchar(255) NOT NULL,
  "is_active" boolean NOT NULL DEFAULT true,
  "is_current" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "angkatans"
--

--
-- Table structure for table "announcements"
--

DROP TABLE IF EXISTS "announcements";

CREATE TABLE "announcements" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "judul" varchar(255) NOT NULL,
  "isi" text NOT NULL,
  "image" varchar(255) DEFAULT NULL,
  "published_at" timestamp NULL,
  "created_by" bigint NOT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "announcements_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "announcements_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE RESTRICT
);

--
-- Dumping data for table "announcements"
--

--
-- Table structure for table "articles"
--

DROP TABLE IF EXISTS "articles";

CREATE TABLE "articles" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "author_id" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "konten" text NOT NULL,
  "kategori" varchar(255) NOT NULL DEFAULT 'Laporan Kegiatan',
  "image" varchar(255) DEFAULT NULL,
  "is_published" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "articles_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "articles_author_id_foreign" FOREIGN KEY ("author_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "articles"
--

--
-- Table structure for table "assessment_details"
--

DROP TABLE IF EXISTS "assessment_details";

CREATE TABLE "assessment_details" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "assessment_id" bigint NOT NULL,
  "kategori" varchar(255) NOT NULL,
  "deskripsi" varchar(255) NOT NULL,
  "nilai" decimal(5,2) NOT NULL DEFAULT '0.00',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "assessment_details_assessment_id_foreign" FOREIGN KEY ("assessment_id") REFERENCES "assessments" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "assessment_details"
--

--
-- Table structure for table "assessments"
--

DROP TABLE IF EXISTS "assessments";

CREATE TABLE "assessments" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "assessor_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "periode" varchar(255) NOT NULL,
  "nilai_kehadiran" decimal(5,2) NOT NULL DEFAULT '0.00',
  "nilai_disiplin" decimal(5,2) NOT NULL DEFAULT '0.00',
  "nilai_keterampilan" decimal(5,2) NOT NULL DEFAULT '0.00',
  "nilai_kepemimpinan" decimal(5,2) NOT NULL DEFAULT '0.00',
  "nilai_keseluruhan" decimal(5,2) NOT NULL DEFAULT '0.00',
  "catatan" text,
  "status" enum('Draft','Selesai') NOT NULL DEFAULT 'Draft',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "assessments_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "assessments_assessor_id_foreign" FOREIGN KEY ("assessor_id") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "assessments_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "assessments"
--

--
-- Table structure for table "attendance_sessions"
--

DROP TABLE IF EXISTS "attendance_sessions";

CREATE TABLE "attendance_sessions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "nama" varchar(255) NOT NULL,
  "tanggal" date NOT NULL,
  "lokasi" varchar(255) DEFAULT NULL,
  "materi_path" varchar(255) DEFAULT NULL,
  "materi_nama" varchar(255) DEFAULT NULL,
  "materi_mime_type" varchar(255) DEFAULT NULL,
  "materi_size" bigint DEFAULT NULL,
  "qr_token" varchar(255) NOT NULL,
  "created_by" bigint NOT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "attendance_sessions_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "attendance_sessions_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE RESTRICT
);

--
-- Dumping data for table "attendance_sessions"
--

--
-- Table structure for table "attendances"
--

DROP TABLE IF EXISTS "attendances";

CREATE TABLE "attendances" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "attendance_session_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "keterangan" varchar(255) NOT NULL DEFAULT 'Hadir',
  "catatan" text,
  "checked_at" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "attendances_attendance_session_id_foreign" FOREIGN KEY ("attendance_session_id") REFERENCES "attendance_sessions" ("id") ON DELETE CASCADE,
  CONSTRAINT "attendances_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "attendances"
--

--
-- Table structure for table "audit_logs"
--

DROP TABLE IF EXISTS "audit_logs";

CREATE TABLE "audit_logs" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "actor_id" bigint DEFAULT NULL,
  "action" varchar(255) NOT NULL,
  "entity_type" varchar(255) NOT NULL,
  "entity_id" bigint NOT NULL,
  "metadata" jsonb DEFAULT NULL,
  "ip_address" varchar(45) DEFAULT NULL,
  "user_agent" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "audit_logs_actor_id_foreign" FOREIGN KEY ("actor_id") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "audit_logs"
--

--
-- Table structure for table "backup_logs"
--

DROP TABLE IF EXISTS "backup_logs";

CREATE TABLE "backup_logs" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "created_by" bigint NOT NULL,
  "keterangan" varchar(255) DEFAULT NULL,
  "file_path" varchar(255) DEFAULT NULL,
  "size" int NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "backup_logs_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "backup_logs"
--

--
-- Table structure for table "cache"
--

DROP TABLE IF EXISTS "cache";

CREATE TABLE "cache" (
  "key" varchar(255) NOT NULL,
  "value" text NOT NULL,
  "expiration" bigint NOT NULL,
  PRIMARY KEY ("key"),
);

--
-- Dumping data for table "cache"
--

INSERT INTO "cache" VALUES ('ekosistem-digital-kepramukaan-cache-5c785c036466adea360111aa28563bfd556b5fba','i:2;',1790132490),('ekosistem-digital-kepramukaan-cache-5c785c036466adea360111aa28563bfd556b5fba:timer','i:1790132489;',1790132490);

--
-- Table structure for table "cache_locks"
--

DROP TABLE IF EXISTS "cache_locks";

CREATE TABLE "cache_locks" (
  "key" varchar(255) NOT NULL,
  "owner" varchar(255) NOT NULL,
  "expiration" bigint NOT NULL,
  PRIMARY KEY ("key"),
);

--
-- Dumping data for table "cache_locks"
--

--
-- Table structure for table "candidates"
--

DROP TABLE IF EXISTS "candidates";

CREATE TABLE "candidates" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "nama_lengkap" varchar(255) NOT NULL,
  "tempat_lahir" varchar(255) DEFAULT NULL,
  "tanggal_lahir" date DEFAULT NULL,
  "jenis_kelamin" varchar(255) DEFAULT NULL,
  "kelas" varchar(255) DEFAULT NULL,
  "no_hp" varchar(255) DEFAULT NULL,
  "riwayat_pramuka" text,
  "catatan" text,
  "status" enum('Pending','Diterima','Ditolak') NOT NULL DEFAULT 'Pending',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "candidates_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "candidates_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "candidates"
--

--
-- Table structure for table "certificates"
--

DROP TABLE IF EXISTS "certificates";

CREATE TABLE "certificates" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "issued_by" bigint NOT NULL,
  "nomor_sertifikat" varchar(255) NOT NULL,
  "jenis" varchar(255) NOT NULL,
  "judul" varchar(255) NOT NULL,
  "deskripsi" text,
  "tanggal_diterbitkan" date NOT NULL,
  "file_path" varchar(255) DEFAULT NULL,
  "status" enum('Draft','Diterbitkan','Dibatalkan') NOT NULL DEFAULT 'Draft',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "certificates_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "certificates_issued_by_foreign" FOREIGN KEY ("issued_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "certificates_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "certificates"
--

--
-- Table structure for table "donations"
--

DROP TABLE IF EXISTS "donations";

CREATE TABLE "donations" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "nominal" decimal(12,2) NOT NULL,
  "bukti_transfer" varchar(255) NOT NULL,
  "keterangan_alokasi" varchar(255) DEFAULT NULL,
  "status_verifikasi" varchar(255) NOT NULL DEFAULT 'Pending',
  "catatan" text,
  "verified_by" bigint DEFAULT NULL,
  "verified_at" timestamp NULL,
  "cancelled_at" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "donations_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "donations_verified_by_foreign" FOREIGN KEY ("verified_by") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "donations"
--

--
-- Table structure for table "event_participants"
--

DROP TABLE IF EXISTS "event_participants";

CREATE TABLE "event_participants" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "event_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "status" enum('Pending','Hadir','Izin','Tidak Hadir') NOT NULL DEFAULT 'Pending',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "event_participants_event_id_foreign" FOREIGN KEY ("event_id") REFERENCES "events" ("id") ON DELETE CASCADE,
  CONSTRAINT "event_participants_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "event_participants"
--

--
-- Table structure for table "events"
--

DROP TABLE IF EXISTS "events";

CREATE TABLE "events" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "nama" varchar(255) NOT NULL,
  "deskripsi" text,
  "tanggal" date NOT NULL,
  "waktu_mulai" time DEFAULT NULL,
  "waktu_selesai" time DEFAULT NULL,
  "lokasi" varchar(255) DEFAULT NULL,
  "image" varchar(255) DEFAULT NULL,
  "jenis" enum('Latihan','Kegiatan','Pertemuan','Outbound','Jambore','Lainnya') NOT NULL DEFAULT 'Kegiatan',
  "status" enum('Draft','Aktif','Selesai','Dibatalkan') NOT NULL DEFAULT 'Draft',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "events_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "events_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "events"
--

--
-- Table structure for table "failed_jobs"
--

DROP TABLE IF EXISTS "failed_jobs";

CREATE TABLE "failed_jobs" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "uuid" varchar(255) NOT NULL,
  "connection" varchar(255) NOT NULL,
  "queue" varchar(255) NOT NULL,
  "payload" text NOT NULL,
  "exception" text NOT NULL,
  "failed_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "failed_jobs"
--

--
-- Table structure for table "field_guides"
--

DROP TABLE IF EXISTS "field_guides";

CREATE TABLE "field_guides" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "konten" text NOT NULL,
  "kategori" varchar(255) NOT NULL,
  "tag" varchar(255) DEFAULT NULL,
  "is_favorited" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "field_guides_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "field_guides_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "field_guides"
--

--
-- Table structure for table "finance_categories"
--

DROP TABLE IF EXISTS "finance_categories";

CREATE TABLE "finance_categories" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "nama" varchar(255) NOT NULL,
  "jenis" varchar(255) NOT NULL DEFAULT 'Masuk',
  "is_active" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "finance_categories_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "finance_categories"
--

--
-- Table structure for table "finance_periods"
--

DROP TABLE IF EXISTS "finance_periods";

CREATE TABLE "finance_periods" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "nama" varchar(255) NOT NULL,
  "starts_at" date NOT NULL,
  "ends_at" date NOT NULL,
  "is_closed" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "finance_periods_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "finance_periods"
--

--
-- Table structure for table "finances"
--

DROP TABLE IF EXISTS "finances";

CREATE TABLE "finances" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "member_id" bigint DEFAULT NULL,
  "category_id" bigint DEFAULT NULL,
  "period_id" bigint DEFAULT NULL,
  "jenis_transaksi" varchar(255) NOT NULL,
  "nominal" decimal(12,2) NOT NULL,
  "keterangan" text NOT NULL,
  "status" varchar(255) NOT NULL DEFAULT 'Draft',
  "receipt_no" varchar(255) DEFAULT NULL,
  "created_by" bigint NOT NULL,
  "tgl_transaksi" date NOT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "finances_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "finances_category_id_foreign" FOREIGN KEY ("category_id") REFERENCES "finance_categories" ("id") ON DELETE SET NULL,
  CONSTRAINT "finances_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE RESTRICT,
  CONSTRAINT "finances_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE SET NULL,
  CONSTRAINT "finances_period_id_foreign" FOREIGN KEY ("period_id") REFERENCES "finance_periods" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "finances"
--

--
-- Table structure for table "galleries"
--

DROP TABLE IF EXISTS "galleries";

CREATE TABLE "galleries" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "uploaded_by" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "deskripsi" text,
  "image" varchar(255) NOT NULL,
  "kategori" varchar(255) NOT NULL DEFAULT 'Umum',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "galleries_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "galleries_uploaded_by_foreign" FOREIGN KEY ("uploaded_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "galleries"
--

--
-- Table structure for table "health_records"
--

DROP TABLE IF EXISTS "health_records";

CREATE TABLE "health_records" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "riwayat_penyakit" text,
  "alergi" text,
  "darah" text,
  "tinggi_badan" text,
  "berat_badan" text,
  "catatan_tambahan" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "health_records_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "health_records_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "health_records_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "health_records"
--

--
-- Table structure for table "inventories"
--

DROP TABLE IF EXISTS "inventories";

CREATE TABLE "inventories" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "kode_barang" varchar(255) NOT NULL,
  "nama_barang" varchar(255) NOT NULL,
  "jenis" varchar(255) NOT NULL DEFAULT 'Aset',
  "satuan" varchar(255) NOT NULL DEFAULT 'Unit',
  "jumlah" integer NOT NULL DEFAULT false,
  "kondisi" varchar(255) NOT NULL DEFAULT 'Baik',
  "status_pinjam" varchar(255) NOT NULL DEFAULT 'Tersedia',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "inventories_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "inventories"
--

--
-- Table structure for table "inventory_loans"
--

DROP TABLE IF EXISTS "inventory_loans";

CREATE TABLE "inventory_loans" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "inventory_id" bigint NOT NULL,
  "member_id" bigint DEFAULT NULL,
  "peminjam_nama" varchar(255) NOT NULL,
  "tgl_pinjam" date NOT NULL,
  "tgl_kembali" date DEFAULT NULL,
  "status" varchar(255) NOT NULL DEFAULT 'Dipinjam',
  "kondisi" varchar(255) NOT NULL DEFAULT 'Baik',
  "approved_by" bigint DEFAULT NULL,
  "catatan" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "inventory_loans_approved_by_foreign" FOREIGN KEY ("approved_by") REFERENCES "users" ("id") ON DELETE SET NULL,
  CONSTRAINT "inventory_loans_inventory_id_foreign" FOREIGN KEY ("inventory_id") REFERENCES "inventories" ("id") ON DELETE CASCADE,
  CONSTRAINT "inventory_loans_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "inventory_loans"
--

--
-- Table structure for table "inventory_movements"
--

DROP TABLE IF EXISTS "inventory_movements";

CREATE TABLE "inventory_movements" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "inventory_id" bigint NOT NULL,
  "jenis" varchar(255) NOT NULL,
  "jumlah" int NOT NULL,
  "referensi" varchar(255) DEFAULT NULL,
  "actor_id" bigint NOT NULL,
  "catatan" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "inventory_movements_actor_id_foreign" FOREIGN KEY ("actor_id") REFERENCES "users" ("id") ON DELETE RESTRICT,
  CONSTRAINT "inventory_movements_inventory_id_foreign" FOREIGN KEY ("inventory_id") REFERENCES "inventories" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "inventory_movements"
--

--
-- Table structure for table "job_batches"
--

DROP TABLE IF EXISTS "job_batches";

CREATE TABLE "job_batches" (
  "id" varchar(255) NOT NULL,
  "name" varchar(255) NOT NULL,
  "total_jobs" int NOT NULL,
  "pending_jobs" int NOT NULL,
  "failed_jobs" int NOT NULL,
  "failed_job_ids" text NOT NULL,
  "options" text,
  "cancelled_at" int DEFAULT NULL,
  "created_at" int NOT NULL,
  "finished_at" int DEFAULT NULL,
  PRIMARY KEY ("id")
);

--
-- Dumping data for table "job_batches"
--

--
-- Table structure for table "jobs"
--

DROP TABLE IF EXISTS "jobs";

CREATE TABLE "jobs" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "queue" varchar(255) NOT NULL,
  "payload" text NOT NULL,
  "attempts" smallinteger NOT NULL,
  "reserved_at" integer DEFAULT NULL,
  "available_at" integer NOT NULL,
  "created_at" integer NOT NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "jobs"
--

--
-- Table structure for table "learning_materials"
--

DROP TABLE IF EXISTS "learning_materials";

CREATE TABLE "learning_materials" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "nama" varchar(200) NOT NULL,
  "deskripsi" varchar(255) DEFAULT NULL,
  "konten" text,
  "file_path" varchar(255) DEFAULT NULL,
  "created_by_user_id" bigint DEFAULT NULL,
  "is_restricted" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "learning_materials_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "learning_materials_created_by_user_id_foreign" FOREIGN KEY ("created_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "learning_materials"
--

--
-- Table structure for table "letter_templates"
--

DROP TABLE IF EXISTS "letter_templates";

CREATE TABLE "letter_templates" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "name" varchar(255) NOT NULL,
  "file_path" varchar(255) DEFAULT NULL,
  "description" text,
  "created_by_user_id" bigint DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "letter_templates_created_by_user_id_foreign" FOREIGN KEY ("created_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "letter_templates"
--

--
-- Table structure for table "letters"
--

DROP TABLE IF EXISTS "letters";

CREATE TABLE "letters" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint DEFAULT NULL,
  "nomor_surat" varchar(100) DEFAULT NULL,
  "jenis_surat" enum('Masuk','Keluar','Keputusan') NOT NULL DEFAULT 'Masuk',
  "perihal" varchar(255) NOT NULL,
  "isi_surat" text,
  "tujuan_pengirim" varchar(150) DEFAULT NULL,
  "tgl_surat" date DEFAULT NULL,
  "waktu_kegiatan" varchar(255) DEFAULT NULL,
  "lokasi_kegiatan" varchar(255) DEFAULT NULL,
  "file_path" varchar(255) DEFAULT NULL,
  "created_by_user_id" bigint DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  "template_id" bigint DEFAULT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "letters_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE SET NULL,
  CONSTRAINT "letters_created_by_user_id_foreign" FOREIGN KEY ("created_by_user_id") REFERENCES "users" ("id") ON DELETE SET NULL,
  CONSTRAINT "letters_template_id_foreign" FOREIGN KEY ("template_id") REFERENCES "letter_templates" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "letters"
--

--
-- Table structure for table "meeting_agendas"
--

DROP TABLE IF EXISTS "meeting_agendas";

CREATE TABLE "meeting_agendas" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "meeting_id" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "deskripsi" text,
  "urutan" int NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "meeting_agendas_meeting_id_foreign" FOREIGN KEY ("meeting_id") REFERENCES "meetings" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "meeting_agendas"
--

--
-- Table structure for table "meeting_attendees"
--

DROP TABLE IF EXISTS "meeting_attendees";

CREATE TABLE "meeting_attendees" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "meeting_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "status" enum('Hadir','Izin','Tidak Hadir') NOT NULL DEFAULT 'Hadir',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "meeting_attendees_meeting_id_foreign" FOREIGN KEY ("meeting_id") REFERENCES "meetings" ("id") ON DELETE CASCADE,
  CONSTRAINT "meeting_attendees_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "meeting_attendees"
--

--
-- Table structure for table "meeting_minutes"
--

DROP TABLE IF EXISTS "meeting_minutes";

CREATE TABLE "meeting_minutes" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "meeting_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "notulen" text NOT NULL,
  "keputusan" text,
  "tindak_lanjut" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "meeting_minutes_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "meeting_minutes_meeting_id_foreign" FOREIGN KEY ("meeting_id") REFERENCES "meetings" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "meeting_minutes"
--

--
-- Table structure for table "meeting_votes"
--

DROP TABLE IF EXISTS "meeting_votes";

CREATE TABLE "meeting_votes" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "meeting_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "agenda_id" bigint DEFAULT NULL,
  "pilihan" enum('Setuju','Tidak Setuju','Abstain') NOT NULL DEFAULT 'Setuju',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "meeting_votes_agenda_id_foreign" FOREIGN KEY ("agenda_id") REFERENCES "meeting_agendas" ("id") ON DELETE CASCADE,
  CONSTRAINT "meeting_votes_meeting_id_foreign" FOREIGN KEY ("meeting_id") REFERENCES "meetings" ("id") ON DELETE CASCADE,
  CONSTRAINT "meeting_votes_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "meeting_votes"
--

--
-- Table structure for table "meetings"
--

DROP TABLE IF EXISTS "meetings";

CREATE TABLE "meetings" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "agenda" text,
  "tanggal" date NOT NULL,
  "waktu_mulai" time DEFAULT NULL,
  "waktu_selesai" time DEFAULT NULL,
  "lokasi" varchar(255) DEFAULT NULL,
  "jenis" enum('Musyawarah','Rapat Pembina','Rapat Anggota','Sidang','Lainnya') NOT NULL DEFAULT 'Musyawarah',
  "status" enum('Draft','Berlangsung','Selesai','Dibatalkan') NOT NULL DEFAULT 'Draft',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "meetings_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "meetings_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "meetings"
--

--
-- Table structure for table "member_logbooks"
--

DROP TABLE IF EXISTS "member_logbooks";

CREATE TABLE "member_logbooks" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "tanggal" date NOT NULL,
  "kegiatan" varchar(255) NOT NULL,
  "refleksi" text NOT NULL,
  "bukti" varchar(255) DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "member_logbooks_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "member_logbooks"
--

--
-- Table structure for table "member_positions"
--

DROP TABLE IF EXISTS "member_positions";

CREATE TABLE "member_positions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "position_id" bigint NOT NULL,
  "assigned_by_user_id" bigint NOT NULL,
  "assigned_at" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "member_positions_assigned_by_user_id_foreign" FOREIGN KEY ("assigned_by_user_id") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "member_positions_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "member_positions_position_id_foreign" FOREIGN KEY ("position_id") REFERENCES "pengurus_positions" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "member_positions"
--

--
-- Table structure for table "member_tku"
--

DROP TABLE IF EXISTS "member_tku";

CREATE TABLE "member_tku" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "awarded_by" bigint DEFAULT NULL,
  "tingkatan" varchar(20) NOT NULL,
  "awarded_at" timestamp NULL,
  "catatan" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "member_tku_awarded_by_foreign" FOREIGN KEY ("awarded_by") REFERENCES "users" ("id") ON DELETE SET NULL,
  CONSTRAINT "member_tku_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "member_tku"
--

--
-- Table structure for table "members"
--

DROP TABLE IF EXISTS "members";

CREATE TABLE "members" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "user_id" bigint NOT NULL,
  "nta" varchar(255) DEFAULT NULL,
  "nama_lengkap" varchar(255) NOT NULL,
  "tempat_lahir" varchar(255) DEFAULT NULL,
  "tanggal_lahir" date DEFAULT NULL,
  "jenis_kelamin" enum('Laki-laki','Perempuan') DEFAULT NULL,
  "kelas" varchar(255) NOT NULL,
  "tingkatan" varchar(255) NOT NULL DEFAULT 'Tamu',
  "tahun_lulus" smallint DEFAULT NULL,
  "status_aktif" enum('Aktif','Alumni','Non-Aktif') NOT NULL DEFAULT 'Aktif',
  "no_hp" varchar(255) DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  "angkatan" varchar(3) NOT NULL,
  "nomor_urut" integer NOT NULL,
  "nta_username" varchar(30) NOT NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "members_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "members_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "members"
--

--
-- Table structure for table "migrations"
--

DROP TABLE IF EXISTS "migrations";

CREATE TABLE "migrations" (
  "id" integer NOT NULL AUTO_INCREMENT,
  "migration" varchar(255) NOT NULL,
  "batch" int NOT NULL,
  PRIMARY KEY ("id")
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table "migrations"
--

INSERT INTO "migrations" VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_10_000001_create_ambalan_tables',1),(5,'2026_09_10_141820_add_angkatan_and_nta_username_to_members',1),(6,'2026_09_11_000000_create_pwa_additional_tables',1),(7,'2026_09_15_000000_create_angkatan_table',1),(8,'2026_09_16_000000_add_status_to_users_table',1),(9,'2026_09_16_000000_create_sku_submission_media_table',1),(10,'2026_09_16_000001_add_logo_path_to_ambalans_table',1),(11,'2026_09_16_073017_add_nomor_to_angkatan_table',1),(12,'2026_09_16_081918_add_image_to_announcements_table',1),(13,'2026_09_16_083000_create_sessions_table',1),(14,'2026_09_16_084500_add_isi_surat_to_letters_table',1),(15,'2026_09_16_085000_create_letter_templates_table',1),(16,'2026_09_16_092118_add_current_flag_to_angkatans_table',1),(17,'2026_09_16_100000_create_pengurus_positions_table',1),(18,'2026_09_16_100000_create_tkk_tables',1),(19,'2026_09_16_100001_create_tkk_submission_media_table',1),(20,'2026_09_16_100100_create_member_positions_table',1),(21,'2026_09_16_101000_add_template_id_to_letters_table',1),(22,'2026_09_16_113500_add_kegiatan_fields_to_letters_table',1),(23,'2026_09_16_120000_add_foto_to_users_table',1),(24,'2026_09_16_120100_add_ktp_fields_to_members_table',1),(25,'2026_09_16_155524_add_timestamps_to_member_positions_table',1),(26,'2026_09_19_160953_add_materi_to_attendance_sessions_table',1),(27,'2026_09_22_000001_create_events_table',2),(28,'2026_09_22_000002_create_meetings_table',2),(29,'2026_09_22_000003_create_assessments_table',2),(30,'2026_09_22_000004_create_certificates_table',2),(31,'2026_09_22_000005_create_field_guides_table',2),(32,'2026_09_22_000006_create_articles_table',2),(33,'2026_09_22_000007_create_galleries_table',2),(34,'2026_09_22_000008_create_teams_table',2),(35,'2026_09_22_000009_create_reminders_table',2),(36,'2026_09_22_000010_create_user_permissions_table',2),(37,'2026_09_22_000011_create_trainings_table',2),(38,'2026_09_22_000012_create_health_safety_table',2),(39,'2026_09_22_000013_create_candidates_table',2),(40,'2026_09_22_000014_create_backup_webhooks_table',2),(41,'2026_09_22_000015_create_system_points_table',2);

--
-- Table structure for table "notifications"
--

DROP TABLE IF EXISTS "notifications";

CREATE TABLE "notifications" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "user_id" bigint NOT NULL,
  "type" varchar(255) NOT NULL,
  "title" varchar(255) NOT NULL,
  "body" text NOT NULL,
  "data" jsonb DEFAULT NULL,
  "read_at" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "notifications_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "notifications"
--

--
-- Table structure for table "password_reset_tokens"
--

DROP TABLE IF EXISTS "password_reset_tokens";

CREATE TABLE "password_reset_tokens" (
  "email" varchar(255) NOT NULL,
  "token" varchar(255) NOT NULL,
  "created_at" timestamp NULL,
  PRIMARY KEY ("email")
);

--
-- Dumping data for table "password_reset_tokens"
--

--
-- Table structure for table "pengurus_positions"
--

DROP TABLE IF EXISTS "pengurus_positions";

CREATE TABLE "pengurus_positions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "name" varchar(255) NOT NULL,
  "code" varchar(255) NOT NULL,
  "description" varchar(255) DEFAULT NULL,
  "is_putra" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "pengurus_positions"
--

--
-- Table structure for table "pwa_devices"
--

DROP TABLE IF EXISTS "pwa_devices";

CREATE TABLE "pwa_devices" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "user_id" bigint NOT NULL,
  "endpoint" varchar(255) NOT NULL,
  "p256dh" varchar(255) DEFAULT NULL,
  "auth" varchar(255) DEFAULT NULL,
  "last_seen" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "pwa_devices_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "pwa_devices"
--

--
-- Table structure for table "reminders"
--

DROP TABLE IF EXISTS "reminders";

CREATE TABLE "reminders" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "created_by" bigint NOT NULL,
  "user_id" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "deskripsi" text,
  "jadwal" timestamp NOT NULL,
  "jenis" enum('Pengingat','Tugas','Iuran','Kegiatan','Lainnya') NOT NULL DEFAULT 'Pengingat',
  "is_sent" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "reminders_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "reminders_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "reminders"
--

--
-- Table structure for table "safety_checks"
--

DROP TABLE IF EXISTS "safety_checks";

CREATE TABLE "safety_checks" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "event_id" bigint DEFAULT NULL,
  "created_by" bigint NOT NULL,
  "kategori" varchar(255) NOT NULL,
  "deskripsi" text NOT NULL,
  "passed" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "safety_checks_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "safety_checks_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "safety_checks_event_id_foreign" FOREIGN KEY ("event_id") REFERENCES "events" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "safety_checks"
--

--
-- Table structure for table "sessions"
--

DROP TABLE IF EXISTS "sessions";

CREATE TABLE "sessions" (
  "id" varchar(255) NOT NULL,
  "user_id" bigint DEFAULT NULL,
  "ip_address" varchar(45) DEFAULT NULL,
  "user_agent" text,
  "payload" text NOT NULL,
  "last_activity" int NOT NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "sessions"
--

INSERT INTO "sessions" VALUES ('GgbwqFiVvngfecUD3deL5xcoPB5xtbIjY8Eb5Rl8',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','ZXlKcGRpSTZJbTB2Y21reWQxcHFiVXR1Y25WTVQwdGxZV0ZWTW1jOVBTSXNJblpoYkhWbElqb2lZa2hzYTNCS05tazJOV0p6UmxSMmVHUlVRMlo0WjBONWQyNUtha1ZOZFRCSWMwMHlVa0ZTV21aUFJFWlJLM05FTXl0TVFrTkJUelpVU0ZkTlMwMUNlVFJwUTFKM1ZuUndhVUV3V0hOTWFubE5TRXRsYnpGQ1ZIbHdkMlJxUVZaeFVVTlpWRXN6ZW5oU1VrOVdkbE5YYW5Kb04zVlJWVGxFVFM5VlVVMDBaR3RQZVhKTkwyMHhPRmxoYTJoRlQyWk5aa3MxUWpBMWRrUk5jM1UyZG5WVlZHRTNhRk01VUZWS1NFMUVNbWt6YWxWQlNDdEZNVmx5Umt4WVpFTlpPSEZOYm5OdFEwcFliRGhQWVM5SE9VSndUVGR1VkdaRGR6MDlJaXdpYldGaklqb2lObU15WTJFMVlUTTVNVE01T0dNMVpEQXlaV0kzWlRVNFltWTBPR0l5TWpNNE1XSXlZakJoWmpJNVlqVmxPVEV6WlRjMk9EYzJOalptWW1RMk9HTXpOQ0lzSW5SaFp5STZJaUo5',1790130819),('hlkIFpR23cBwm5Krci1XBTTP9JhP4HwpFQHzH0Lg',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','ZXlKcGRpSTZJa1JsU1ZBdmJIcEhTRGh2Vm5oek9UTkZWelJtWm5jOVBTSXNJblpoYkhWbElqb2liV3RpVG1FM2NFUTBWM0kwWjB4MWNERkZWVWQyWlVaR1kxWmhjRGR6UjJsWFZrWlZRVTFPYTIxTVFuZFZWMlJuWXpjNEszQTFXbTgwUzFrdk1uWlJXbTU0TkVkNVpscG1lRWhYTlVkbVpEaHVhekIwUWpCWGIyZHdZV2RsWW01elJtMHJSbVZtUkdwQlZrMTBjRk5QZUdSVVVqbERielZ6TTBOU1ZIb3JRalJ3YTIxMVpXZExXVGhaZFVOTFpUaFRkM2N3T1VOV2JrTk1aakl4TmxVNVEyRmpjbkJLVEhoMlkyNU9jU3MyUzBoRlNWZ3laR05OYVZvNWVTdGlLMDlRV2pWTGFGVnNPR1ZKVUZOSFRtVjRNMGN3YlRaMlFUMDlJaXdpYldGaklqb2labVJpTjJKbU9HSmlOV0UyWVRNM056QmpZVEUzTVRjek5qUXpZbU0wWm1ObE4yVTFOak5sTXpVMllUZ3hOMlprTmpobVlUSXlaakEyT0RnM01EUmpPQ0lzSW5SaFp5STZJaUo5',1790131978),('JkkSvLzXvuAz5Po0WaNAyxi0q7CQYJnmfv8FPuww',NULL,'127.0.0.1','curl/8.20.0','ZXlKcGRpSTZJbmsxUW14T1lYSm5VM0pyU21sSmVYRjVZMUZRVGxFOVBTSXNJblpoYkhWbElqb2lZVmhMUzNsSmFuTmhhMFZOWlcwM1QwMXRjWEpLVlU1U1V5dHpWRFUyZUVOdU1WaENkbFZ2VlVOd1ozQTVhalpZVm5aUVlWYzVOR05PTVM5NlpVazVVRE5IUkRCWWRuRkpRWFpNU1cxR0szQllVbFZJV0hWSVV6Rlpja1JXVG5KVE1ETnJhMDlTT0dWaGJGWlRUMDQzUXpocWQzVmtOM05qVFdSYVZuUnhNV1ZSV0VkaFRWZGpkVVZvV21vNFpHZGtObU4yUjJKMFNuSkNWQ3RqWjBReUx6RkpTMGd4WlZOcmVGbE9NR0poY0VRclJuRllOU3R1Ym1wVmVXdFhjMnBVZFdSS09HUmxlbWhDUWpWNGJIQjNTbWxrVkRaMFp6MDlJaXdpYldGaklqb2lOVFF6T1RGbFlUZzROR0ZrT1Roa1l6STFNMkU1WlRrM1pUUmtZakk0TldFME1ESTJNbU15WlRZMFlUSTBPR000TUdJMk5HVTVaVFprTWpFNU56WmxPQ0lzSW5SaFp5STZJaUo5',1790131497),('kJHbU3IdVyIVeDuJoQCam9g0PuXXGVaixqcIuuOX',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT; Windows NT 10.0; id-ID) WindowsPowerShell/5.1.26100.9444','ZXlKcGRpSTZJbFJOY2taSWRDdGpRVVJGY0cxUGRubG9ObTVuYlZFOVBTSXNJblpoYkhWbElqb2lTVGhOTURsSFpEWmlkRmg0Vm5WSWVVSkNjV3haZURRNWMwMWxaQ3RHUkU5VU9VeDJPVU5UVlZRNWQzRmFTM0p1ZUdkbmJrOXpUVnAwVEU1MFpXeFJOSHBGYTFoeVMwaFBWVGx0Tlc5eWQxWjVaa05UY0cwM01ETm5PRzlQUnpSSlNtTlJkM0JGVTI1R1dIY3ZRbEJIY21OWVZFSllhemw2VURjeWVtaDVVWGhLTVhCSFpYbE9OVVpFYlV0UlUwcENkM0pSZFhGcmRDOUJPU3RxZVdWNE1XTldLME5WVWtodk1qaENPWEpHV214UFZscEhTRkpRUzFkRlRGZ3ZTV0ZGWVM5Q1JDOXViWHBsUWxnNFJYVk9LMUZzYWtsTlp6MDlJaXdpYldGaklqb2lZalExWTJFNFlqSXhaV1kxTW1ZNU1EUTNNekl3TldNNU5ETmpZek0yT0dFeU1UTTBOVFExWVRnNVlXRXhNamhoWTJRMk56Rm1aak0xTXpKalpEUmlNU0lzSW5SaFp5STZJaUo5',1790131539),('mHrEmKvAPc86f0QuzTja8TnWReeq0rHJIniPkTaB',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','ZXlKcGRpSTZJamREVEV4d1ZEZFBka05IYVRGWVQyNXlSbko2V21jOVBTSXNJblpoYkhWbElqb2lNV3RXVkRKYU5IQkdSMEZuTW05SWMxaHFUM0prVGpKSFlrOWhjVGxLTHpGWGFXWjVUbU5JZEM4eGNsQXJZVEZ0SzFwNWQxUmtXV2MyWW5GUk9VdFVXVzFzVVZvd05YWjFiekZKT1ZZMVowTmtZVEpqWVRsaFoyaHFkbE52TUdwc05FbDZlRFZoYTBKTFFVbE5ZekptYVV4WmVVcFVjRlZRTm5SVEsxWlpTak5CZDFodGFUQjViRGh0UWtsSFVUazVWamw1U21scVJIbzNka0YyVEZVek0wcHNaVXd2YmpkaWJXVjNNV0UwV0daeU5IcDZSVVE0VFVKak1tVldSek5FVjBabWJVazRSV3BrUkRBMFltODRPWEk1Wm1GMlFUMDlJaXdpYldGaklqb2laREJoTURsaVpEQTROelkwTmpoa05tUTVPVEV4WVdNeU1HRTBaVEE0WmpNME0yWmlZVGsyTmpZM01UVmpNVFJrWWpFM01HSmtOVFl3TkRrMVlUVmhNQ0lzSW5SaFp5STZJaUo5',1790130911),('mruPjjr6LGjB2JShUTiDGot3aZ3Y9CZ55dOdHGOL',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','ZXlKcGRpSTZJbEEzUVZwT01HMVBMemMwU1VoT1YyTnJXV1JyUVVFOVBTSXNJblpoYkhWbElqb2lTSGRrZWxWdFpHZFFWRmRYVUhoa1puYzFORVo1VkhGQlZURktXaloyT1ZORGNIZFpNbTFtT0hkSVpXbEhZMkZYYnpCSldXNXRiRlFyY1RaT1pqZEpRMkZVYlVzdmRYUnVkV3hVZGxGWGNFRnRWRk4zV2xKNldYbEdhSGhsWnpaNlV6SkxRVUZKTW1STllUSjNPRWh6VVZsMFZYbFRabVEwYWxaVU55OVJTWFpLVUM5aU9YUlBkMFZZUm5CR1VYSlRkMGt4Y25Ca1JqbEtiRTl0SzNZMGNIcEtiak5HUjAxaUwyMWpVMWd6UjJoM1RtRmpOWGwxYmtGdE1HTjVlVFZPYUVsaVlXTk5hMHh4TW0xV2JqaDJOSGx6UTNGdlFUMDlJaXdpYldGaklqb2lZV1E0TVRKaU1HVmlabVU1T0RGalptTTRNREkxWWpJeFlURXlPR05qT0dVM1lXTTBaRGxpTWpjNU9HSm1aRGhrWVdWaE16VXlNVEkwWVRJMVl6aGlaaUlzSW5SaFp5STZJaUo5',1790132430);

--
-- Table structure for table "sku_points"
--

DROP TABLE IF EXISTS "sku_points";

CREATE TABLE "sku_points" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "tingkatan" varchar(255) NOT NULL,
  "nomor_poin" smallinteger NOT NULL,
  "deskripsi_poin" text NOT NULL,
  "is_active" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "sku_points"
--

--
-- Table structure for table "sku_submission_media"
--

DROP TABLE IF EXISTS "sku_submission_media";

CREATE TABLE "sku_submission_media" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "sku_submission_id" bigint NOT NULL,
  "file_path" varchar(255) NOT NULL,
  "tipe" varchar(255) NOT NULL DEFAULT 'photo',
  "caption" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "sku_submission_media_sku_submission_id_foreign" FOREIGN KEY ("sku_submission_id") REFERENCES "sku_submissions" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "sku_submission_media"
--

--
-- Table structure for table "sku_submissions"
--

DROP TABLE IF EXISTS "sku_submissions";

CREATE TABLE "sku_submissions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "sku_point_id" bigint NOT NULL,
  "verified_by" bigint DEFAULT NULL,
  "bukti_kegiatan" varchar(255) DEFAULT NULL,
  "status" varchar(255) NOT NULL DEFAULT 'Pending',
  "catatan" text,
  "tgl_verifikasi" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "sku_submissions_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "sku_submissions_sku_point_id_foreign" FOREIGN KEY ("sku_point_id") REFERENCES "sku_points" ("id") ON DELETE CASCADE,
  CONSTRAINT "sku_submissions_verified_by_foreign" FOREIGN KEY ("verified_by") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "sku_submissions"
--

--
-- Table structure for table "system_points"
--

DROP TABLE IF EXISTS "system_points";

CREATE TABLE "system_points" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "kategori" varchar(255) NOT NULL,
  "deskripsi" varchar(255) NOT NULL,
  "poin" int NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "system_points_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "system_points_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "system_points"
--

--
-- Table structure for table "team_members"
--

DROP TABLE IF EXISTS "team_members";

CREATE TABLE "team_members" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "team_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "peran" varchar(255) NOT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "team_members_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "team_members_team_id_foreign" FOREIGN KEY ("team_id") REFERENCES "teams" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "team_members"
--

--
-- Table structure for table "team_tasks"
--

DROP TABLE IF EXISTS "team_tasks";

CREATE TABLE "team_tasks" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "team_id" bigint NOT NULL,
  "assigned_to" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "deskripsi" text,
  "tenggat" date DEFAULT NULL,
  "status" enum('Belum Dimulai','Berlangsung','Selesai','Terlewat') NOT NULL DEFAULT 'Belum Dimulai',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "team_tasks_assigned_to_foreign" FOREIGN KEY ("assigned_to") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "team_tasks_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE,
  CONSTRAINT "team_tasks_team_id_foreign" FOREIGN KEY ("team_id") REFERENCES "teams" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "team_tasks"
--

--
-- Table structure for table "teams"
--

DROP TABLE IF EXISTS "teams";

CREATE TABLE "teams" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "nama" varchar(255) NOT NULL,
  "kode" varchar(255) NOT NULL,
  "deskripsi" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "teams_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "teams"
--

--
-- Table structure for table "tkk_points"
--

DROP TABLE IF EXISTS "tkk_points";

CREATE TABLE "tkk_points" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "nama" varchar(150) NOT NULL,
  "slug" varchar(150) NOT NULL,
  "deskripsi" text,
  "is_active" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "tkk_points"
--

--
-- Table structure for table "tkk_submission_media"
--

DROP TABLE IF EXISTS "tkk_submission_media";

CREATE TABLE "tkk_submission_media" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "tkk_submission_id" bigint NOT NULL,
  "file_path" varchar(255) NOT NULL,
  "tipe" varchar(255) NOT NULL DEFAULT 'photo',
  "caption" varchar(255) DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "tkk_submission_media_tkk_submission_id_foreign" FOREIGN KEY ("tkk_submission_id") REFERENCES "tkk_submissions" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "tkk_submission_media"
--

--
-- Table structure for table "tkk_submissions"
--

DROP TABLE IF EXISTS "tkk_submissions";

CREATE TABLE "tkk_submissions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "member_id" bigint NOT NULL,
  "tkk_point_id" bigint NOT NULL,
  "verified_by" bigint DEFAULT NULL,
  "bukti_kegiatan" varchar(255) DEFAULT NULL,
  "status" varchar(255) NOT NULL DEFAULT 'Pending',
  "catatan" text,
  "tgl_verifikasi" timestamp NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "tkk_submissions_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "tkk_submissions_tkk_point_id_foreign" FOREIGN KEY ("tkk_point_id") REFERENCES "tkk_points" ("id") ON DELETE CASCADE,
  CONSTRAINT "tkk_submissions_verified_by_foreign" FOREIGN KEY ("verified_by") REFERENCES "users" ("id") ON DELETE SET NULL
);

--
-- Dumping data for table "tkk_submissions"
--

--
-- Table structure for table "training_progress"
--

DROP TABLE IF EXISTS "training_progress";

CREATE TABLE "training_progress" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "training_id" bigint NOT NULL,
  "member_id" bigint NOT NULL,
  "completed" boolean NOT NULL DEFAULT false,
  "catatan" text,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "training_progress_member_id_foreign" FOREIGN KEY ("member_id") REFERENCES "members" ("id") ON DELETE CASCADE,
  CONSTRAINT "training_progress_training_id_foreign" FOREIGN KEY ("training_id") REFERENCES "trainings" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "training_progress"
--

--
-- Table structure for table "trainings"
--

DROP TABLE IF EXISTS "trainings";

CREATE TABLE "trainings" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "ambalan_id" bigint NOT NULL,
  "created_by" bigint NOT NULL,
  "judul" varchar(255) NOT NULL,
  "konten" text,
  "kategori" varchar(255) NOT NULL,
  "video_url" varchar(255) DEFAULT NULL,
  "file_path" varchar(255) DEFAULT NULL,
  "tanggal" date DEFAULT NULL,
  "status" enum('Draft','Aktif','Selesai') NOT NULL DEFAULT 'Draft',
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "trainings_ambalan_id_foreign" FOREIGN KEY ("ambalan_id") REFERENCES "ambalans" ("id") ON DELETE CASCADE,
  CONSTRAINT "trainings_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "trainings"
--

--
-- Table structure for table "user_permissions"
--

DROP TABLE IF EXISTS "user_permissions";

CREATE TABLE "user_permissions" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "user_id" bigint NOT NULL,
  "permission" varchar(255) NOT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "user_permissions_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "user_permissions"
--

--
-- Table structure for table "users"
--

DROP TABLE IF EXISTS "users";

CREATE TABLE "users" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "username" varchar(30) NOT NULL,
  "name" varchar(255) DEFAULT NULL,
  "email" varchar(255) DEFAULT NULL,
  "email_verified_at" timestamp NULL,
  "password" varchar(255) NOT NULL,
  "role" enum('Admin','Pembina','Pengurus','Anggota','Alumni') NOT NULL DEFAULT 'Anggota',
  "is_active" boolean NOT NULL DEFAULT true,
  "status" varchar(255) NOT NULL DEFAULT 'pending',
  "remember_token" varchar(100) DEFAULT NULL,
  "foto" varchar(255) DEFAULT NULL,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  "deleted_at" timestamp NULL,
  PRIMARY KEY ("id"),
);

--
-- Dumping data for table "users"
--

--
-- Table structure for table "webhook_logs"
--

DROP TABLE IF EXISTS "webhook_logs";

CREATE TABLE "webhook_logs" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "webhook_id" bigint NOT NULL,
  "payload" jsonb DEFAULT NULL,
  "status_code" int DEFAULT NULL,
  "response" text,
  "success" boolean NOT NULL DEFAULT false,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "webhook_logs_webhook_id_foreign" FOREIGN KEY ("webhook_id") REFERENCES "webhooks" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "webhook_logs"
--

--
-- Table structure for table "webhooks"
--

DROP TABLE IF EXISTS "webhooks";

CREATE TABLE "webhooks" (
  "id" bigint GENERATED ALWAYS AS IDENTITY,
  "created_by" bigint NOT NULL,
  "nama" varchar(255) NOT NULL,
  "url" varchar(255) NOT NULL,
  "event" varchar(255) DEFAULT NULL,
  "headers" text,
  "is_active" boolean NOT NULL DEFAULT true,
  "created_at" timestamp NULL,
  "updated_at" timestamp NULL,
  PRIMARY KEY ("id"),
  CONSTRAINT "webhooks_created_by_foreign" FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE CASCADE
);

--
-- Dumping data for table "webhooks"
--

--
-- Dumping routines for database 'database_ambalan'
--

-- Dump completed on 2026-09-23 12:15:02

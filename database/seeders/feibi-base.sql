/*
 Navicat Premium Data Transfer

 Source Server         : 公司主機
 Source Server Type    : MySQL
 Source Server Version : 50737 (5.7.37-log)
 Source Host           : cs.feibi.site:3306
 Source Schema         : feibi_base

 Target Server Type    : MySQL
 Target Server Version : 50737 (5.7.37-log)
 File Encoding         : 65001

 Date: 11/04/2023 18:20:06
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for activity_log
-- ----------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `subject_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `event` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `causer_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `causer_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `properties` json NULL,
  `batch_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `activity_log_log_name_index`(`log_name`) USING BTREE,
  INDEX `subject`(`subject_id`, `subject_type`) USING BTREE,
  INDEX `causer`(`causer_id`, `causer_type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '操作日誌' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of activity_log
-- ----------------------------

-- ----------------------------
-- Table structure for admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '信箱',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用戶名',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密碼',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_login_time` datetime NULL DEFAULT NULL COMMENT '最後登入時間',
  `status` tinyint(4) UNSIGNED NOT NULL DEFAULT 1 COMMENT '狀態 1:正常 2:禁止',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of admins
-- ----------------------------
INSERT INTO `admins` VALUES (1, '管理員', 'admin@ity.com', 'admin', NULL, '$2y$10$Q3cEg1b6h31jsEWFBmkjB.PqKNWlS8zsNEjmNEHgz0O1KPc8iKZri', NULL, '2023-04-11 16:21:20', 1, '2022-11-03 00:48:47', '2023-04-11 16:21:20');

-- ----------------------------
-- Table structure for dict_data
-- ----------------------------
DROP TABLE IF EXISTS `dict_data`;
CREATE TABLE `dict_data`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dict_type_id` bigint(20) UNSIGNED NOT NULL,
  `sort` tinyint(4) NOT NULL DEFAULT 0 COMMENT '字典排序',
  `label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字典標簽',
  `value` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字典鍵值',
  `list_class` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '表格回顯樣式',
  `default` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否默認 1:是 2:否',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '狀態 1:正常 2:禁止',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '備註',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `dict_data_dict_type_id_foreign`(`dict_type_id`) USING BTREE,
  CONSTRAINT `dict_data_dict_type_id_foreign` FOREIGN KEY (`dict_type_id`) REFERENCES `dict_types` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '字典數據表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of dict_data
-- ----------------------------

-- ----------------------------
-- Table structure for dict_types
-- ----------------------------
DROP TABLE IF EXISTS `dict_types`;
CREATE TABLE `dict_types`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字典名稱',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字典類型',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '狀態 1:正常 2:禁止',
  `remark` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '備註',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `dict_types_type_unique`(`type`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '字典類型表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of dict_types
-- ----------------------------

-- ----------------------------
-- Table structure for exception_errors
-- ----------------------------
DROP TABLE IF EXISTS `exception_errors`;
CREATE TABLE `exception_errors`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '日誌 uid',
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `line` bigint(20) NOT NULL,
  `trace` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trace_as_string` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_solve` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否解決 1是 2否',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `exception_errors_id_unique`(`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of exception_errors
-- ----------------------------

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for gen_table_columns
-- ----------------------------
DROP TABLE IF EXISTS `gen_table_columns`;
CREATE TABLE `gen_table_columns`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gen_table_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '類型',
  `precision` int(11) NOT NULL COMMENT '長度',
  `scale` int(11) NOT NULL COMMENT '小數點',
  `notnull` tinyint(4) NOT NULL COMMENT '不是NULL 1:是 0:否',
  `primary` tinyint(4) NOT NULL COMMENT '主鍵 1:是 0:否',
  `comment` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '註釋',
  `default` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '默認值',
  `autoincrement` tinyint(4) NOT NULL COMMENT '自動遞增 1:是 0:否',
  `unsigned` tinyint(4) NOT NULL COMMENT '無符號 1:是 0:否',
  `_insert` tinyint(4) NOT NULL COMMENT '新增 1:是 0:否',
  `_update` tinyint(4) NOT NULL COMMENT '更新 1:是 0:否',
  `_list` tinyint(4) NOT NULL COMMENT '列表 1:是 0:否',
  `_select` tinyint(4) NOT NULL COMMENT '查詢 1:是 0:否',
  `_query` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '查詢方式',
  `_sort` tinyint(4) NOT NULL COMMENT '排序 1:是 0:否',
  `_required` tinyint(4) NOT NULL COMMENT '必填 1:是 0:否',
  `_show` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '新增類型',
  `_validate` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '驗證類型',
  `dict_type_id` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '字典',
  `_unique` tinyint(4) NOT NULL COMMENT '唯壹 1:是 0:否',
  `_foreign` tinyint(4) NOT NULL COMMENT '外鍵 1:是 0:否',
  `_foreign_table` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '外鍵表',
  `_foreign_column` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '外鍵字段',
  `_foreign_show` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '外鍵顯示字段',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `gen_table_columns_gen_table_id_foreign`(`gen_table_id`) USING BTREE,
  INDEX `gen_table_columns_dict_type_id_foreign`(`dict_type_id`) USING BTREE,
  CONSTRAINT `gen_table_columns_dict_type_id_foreign` FOREIGN KEY (`dict_type_id`) REFERENCES `dict_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `gen_table_columns_gen_table_id_foreign` FOREIGN KEY (`gen_table_id`) REFERENCES `gen_tables` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代碼生成字段表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of gen_table_columns
-- ----------------------------

-- ----------------------------
-- Table structure for gen_tables
-- ----------------------------
DROP TABLE IF EXISTS `gen_tables`;
CREATE TABLE `gen_tables`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '表名稱',
  `entity_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '實體名稱',
  `comment` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '表描述',
  `engine` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '表引擎',
  `charset` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '字符集',
  `collation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '排序規則',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '代碼生成表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of gen_tables
-- ----------------------------

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of migrations
-- ----------------------------


-- ----------------------------
-- Table structure for model_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions`  (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_permissions_model_id_model_type_index`(`model_id`, `model_type`) USING BTREE,
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of model_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for model_has_roles
-- ----------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles`  (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_roles_model_id_model_type_index`(`model_id`, `model_type`) USING BTREE,
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of model_has_roles
-- ----------------------------
INSERT INTO `model_has_roles` VALUES (1, 'App\\Models\\Backend\\Admin\\Admin', 1);

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications`  (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `notifications_notifiable_type_notifiable_id_index`(`notifiable_type`, `notifiable_id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of notifications
-- ----------------------------

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  INDEX `password_resets_email_index`(`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of password_resets
-- ----------------------------

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父級 ID',
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '權限',
  `title` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
  `icon` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '圖標',
  `path` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '訪問路徑',
  `component` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'vue 對應的組件地址',
  `sort` bigint(20) UNSIGNED NOT NULL DEFAULT 1 COMMENT '排序',
  `hidden` tinyint(4) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否隱藏 2=false|不隱藏 1=true|隱藏',
  `active_menu` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '高亮',
  `guard_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 84 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of permissions
-- ----------------------------
INSERT INTO `permissions` VALUES (1, 0, 'system', '系統', 'el-icon-s-tools', '/system', 'layout/Layout', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (2, 0, 'permission-manage', '權限管理', 'fa fa-paste', '/permission', 'layout/Layout', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2023-04-11 17:58:07');
INSERT INTO `permissions` VALUES (3, 2, 'permission.permissions', '權限列表', 'el-icon-key', 'permissions', 'permission/permissions', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (4, 3, 'permission.create', '添加權限', 'icon', 'permission/create', 'permission/create', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (5, 3, 'permission.update', '編輯權限', 'icon', 'permission/update', 'permission/update', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (6, 3, 'permission.delete', '刪除權限', 'icon', 'permission/delete', 'permission/delete', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (7, 3, 'permission.permission', '權限詳情', 'icon', 'permission', 'permission/permission', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (8, 2, 'role.roles', '角色列表', 'el-icon-s-custom', 'roles', 'role/roles', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (9, 8, 'role.create', '添加角色', 'icon', 'role/create', 'role/create', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (10, 8, 'role.update', '編輯角色', 'icon', 'role/update', 'role/update', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (11, 8, 'role.delete', '刪除角色', 'icon', 'role/delete', 'role/delete', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (12, 8, 'role.role', '角色詳情', 'icon', 'role/role', 'role/role', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (13, 8, 'role.syncPermissions', '分配權限/目錄', 'icon', 'role/syncPermissions', 'role/syncPermissions', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (14, 8, 'role.syncRoles', '分配用戶', 'icon', 'role/syncRoles', 'role/syncRoles', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (15, 2, 'admin.admins', '管理員列表', 'el-icon-user-solid', '/admins', 'admin/admins', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2023-04-11 17:17:35');
INSERT INTO `permissions` VALUES (16, 15, 'admin.create', '添加管理員', 'icon', 'admin/create', 'admin/create', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (17, 15, 'admin.update', '編輯管理員', 'icon', 'admin/update', 'admin/update', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (18, 15, 'admin.delete', '刪除管理員', 'icon', 'admin/delete', 'admin/delete', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (19, 15, 'admin.admin', '管理員詳情', 'icon', 'admin/admin', 'admin/admin', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (20, 15, 'admin.syncPermissions', '授權權限', 'icon', 'admin/syncPermissions', 'admin/syncPermissions', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (21, 1, 'activeLog.activeLogs', '操作記錄', 'el-icon-tickets', '/activeLogs', 'activeLog/activeLogs', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (22, 1, 'nginx.logs', 'NGINX記錄', 'el-icon-tickets', '/nginxLogs', 'nginx/logs', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (23, 1, 'exceptionError.exceptionErrors', '異常記錄', 'el-icon-warning', '/exceptionErrors', 'exceptionError/exceptionErrors', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (24, 23, 'exceptionError.amended', '修復異常', 'el-icon-warning', 'exceptionErrors/amended', 'exceptionError/amended', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (25, 1, 'exceptionError.logFiles', 'LOG日誌', 'el-icon-tickets', '/exceptionErrors/logFiles', 'exceptionError/logFiles', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (31, 1, 'file.files', '文件管理', 'el-icon-folder', '/files', 'file/files', 1, 2, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (32, 31, 'file.makeDirectory', '創建文件夾', 'el-icon-folder-add', 'file/makeDirectory', 'file/makeDirectory', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (33, 31, 'file.deleteDirectory', '刪除文件夾', 'el-icon-folder-delete', 'file/deleteDirectory', 'file/deleteDirectory', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (34, 31, 'file.upload', '上傳文件', 'el-icon-upload', 'file/upload', 'file/upload', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (35, 31, 'file.download', '下載文件', 'el-icon-download', 'file/download', 'file/download', 1, 1, '', 'admin', '2022-11-03 00:48:47', '2022-11-03 00:48:47');
INSERT INTO `permissions` VALUES (36, 31, 'file.delete', '刪除文件', 'el-icon-delete', 'file/delete', 'file/delete', 1, 1, '', 'admin', '2022-11-03 00:48:48', '2022-11-03 00:48:48');
INSERT INTO `permissions` VALUES (37, 1, 'dict', '字典管理', 'el-icon-collection-tag', '/dict', 'dict/index', 1, 2, '', 'admin', '2022-11-03 00:48:48', '2022-11-03 00:48:48');
INSERT INTO `permissions` VALUES (38, 1, 'genTable.genTables', '代碼生成', 'fa fa-code', '/genTables', 'genTable/index', 1, 2, '', 'admin', '2022-11-03 00:48:48', '2022-11-03 00:48:48');
INSERT INTO `permissions` VALUES (80, 0, 'user.list', '會員管理', 'el-icon-star-on', '/user', 'user/index', 1, 2, '', 'admin', '2023-04-11 17:32:31', '2023-04-11 17:32:31');
INSERT INTO `permissions` VALUES (81, 80, 'user.create', '創建會員管理', 'el-icon-star-on', 'user/create', 'user/create', 1, 1, '', 'admin', '2023-04-11 17:32:31', '2023-04-11 17:32:31');
INSERT INTO `permissions` VALUES (82, 80, 'user.update', '編輯會員管理', 'el-icon-star-on', 'user/update', 'user/update', 1, 1, '', 'admin', '2023-04-11 17:32:31', '2023-04-11 17:32:31');
INSERT INTO `permissions` VALUES (83, 80, 'user.delete', '刪除會員管理', 'el-icon-star-on', 'user/delete', 'user/delete', 1, 1, '', 'admin', '2023-04-11 17:32:31', '2023-04-11 17:32:31');

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for role_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions`  (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`) USING BTREE,
  INDEX `role_has_permissions_role_id_foreign`(`role_id`) USING BTREE,
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of role_has_permissions
-- ----------------------------


-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '角色',
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
  `guard_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '狀態 1啟用 2禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'super_admin', 'Super Admin', 'admin', 1, '2022-11-03 00:48:48', '2022-11-03 00:48:48');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '姓名',
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '手機號碼',
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '信箱',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT '信箱確認時間',
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '密碼',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'token',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '狀態 1啟用 2禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '會員表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of users
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;

/*
Navicat MySQL Data Transfer

Source Server         : dev本地mysql
Source Server Version : 50726
Source Host           : localhost:3306
Source Database       : pms

Target Server Type    : MYSQL
Target Server Version : 50726
File Encoding         : 65001

Date: 2021-03-17 11:52:42
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for pms_log
-- ----------------------------
DROP TABLE IF EXISTS `pms_log`;
CREATE TABLE `pms_log` (
  `id` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  `level` tinyint(3) NOT NULL DEFAULT '4' COMMENT '日志层级',
  `action_name` varchar(255) NOT NULL DEFAULT '' COMMENT '路由地址',
  `category` varchar(255) DEFAULT '' COMMENT '分类',
  `message` text COMMENT '消息',
  `prefix` varchar(255) DEFAULT '' COMMENT 'IP地址',
  `user_id` int(11) DEFAULT '0' COMMENT '用户编号',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `level` (`level`),
  KEY `action_name` (`action_name`),
  KEY `user_id` (`user_id`),
  KEY `create_time` (`create_time`),
  KEY `category` (`category`)
) ENGINE=MyISAM AUTO_INCREMENT=4580 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='日志库';

-- ----------------------------
-- Records of pms_log
-- ----------------------------

-- ----------------------------
-- Table structure for pms_member
-- ----------------------------
DROP TABLE IF EXISTS `pms_member`;
CREATE TABLE `pms_member` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(100) NOT NULL DEFAULT '' COMMENT '密码',
  `real_name` varchar(50) DEFAULT '' COMMENT '真实姓名',
  `email` varchar(50) DEFAULT '' COMMENT '邮箱',
  `phone_number` varchar(20) NOT NULL,
  `is_leave` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否离职，1为在职，0为离职',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `last_signin_time` int(10) NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `signin_ip` int(10) unsigned DEFAULT '0' COMMENT '登陆ip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COMMENT='团队表';

-- ----------------------------
-- Records of pms_member
-- ----------------------------
INSERT INTO `pms_member` VALUES ('1', 'doowan', '$2y$13$3Laf/aXBBwSdkPxonHouNOYXeX25LrF9z27EwipnAjLaC1G.OyFF6', 'doowan', 'test@qq.com', '13838888888', '1', '0', '1615163471', '1615163471', '3232241017');

-- ----------------------------
-- Table structure for pms_member_config
-- ----------------------------
DROP TABLE IF EXISTS `pms_member_config`;
CREATE TABLE `pms_member_config` (
  `member_id` int(10) NOT NULL DEFAULT '0' COMMENT '成员编号',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
  `value` varchar(500) DEFAULT '' COMMENT '配置信息',
  PRIMARY KEY (`member_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='成员配置信息';

-- ----------------------------
-- Records of pms_member_config
-- ----------------------------
INSERT INTO `pms_member_config` VALUES ('1', 'whichTasksOfPrioritySendToEmail', '');
INSERT INTO `pms_member_config` VALUES ('1', 'focusProject', '0');

-- ----------------------------
-- Table structure for pms_member_password_recover
-- ----------------------------
DROP TABLE IF EXISTS `pms_member_password_recover`;
CREATE TABLE `pms_member_password_recover` (
  `id` varchar(80) NOT NULL DEFAULT '' COMMENT 'hash',
  `member_id` int(10) NOT NULL,
  `expired_time` int(10) DEFAULT '0' COMMENT '过期时间',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='密码找回存放hash值';

-- ----------------------------
-- Records of pms_member_password_recover
-- ----------------------------

-- ----------------------------
-- Table structure for pms_member_role
-- ----------------------------
DROP TABLE IF EXISTS `pms_member_role`;
CREATE TABLE `pms_member_role` (
  `id` smallint(4) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '团队成员角色描述',
  `show_order` smallint(4) NOT NULL DEFAULT '999' COMMENT '显示顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COMMENT='团队角色';

-- ----------------------------
-- Records of pms_member_role
-- ----------------------------
INSERT INTO `pms_member_role` VALUES ('1', '后端', '999');
INSERT INTO `pms_member_role` VALUES ('2', '前端', '999');
INSERT INTO `pms_member_role` VALUES ('3', 'UI', '999');
INSERT INTO `pms_member_role` VALUES ('4', '测试', '999');
INSERT INTO `pms_member_role` VALUES ('5', '产品', '999');
INSERT INTO `pms_member_role` VALUES ('6', '团队管理', '999');
INSERT INTO `pms_member_role` VALUES ('7', '内部用户', '999');
INSERT INTO `pms_member_role` VALUES ('9', 'app开发', '999');
INSERT INTO `pms_member_role` VALUES ('10', '外部用户', '999');

-- ----------------------------
-- Table structure for pms_member_role_groups
-- ----------------------------
DROP TABLE IF EXISTS `pms_member_role_groups`;
CREATE TABLE `pms_member_role_groups` (
  `member_id` smallint(4) NOT NULL,
  `role_id` tinyint(4) NOT NULL,
  PRIMARY KEY (`member_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='团队身份';

-- ----------------------------
-- Records of pms_member_role_groups
-- ----------------------------
INSERT INTO `pms_member_role_groups` VALUES ('1', '1');
INSERT INTO `pms_member_role_groups` VALUES ('1', '2');
INSERT INTO `pms_member_role_groups` VALUES ('1', '4');
INSERT INTO `pms_member_role_groups` VALUES ('1', '5');

-- ----------------------------
-- Table structure for pms_message
-- ----------------------------
DROP TABLE IF EXISTS `pms_message`;
CREATE TABLE `pms_message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` varchar(1000) NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '状态，1，未读，2，已读',
  `url` varchar(180) CHARACTER SET utf8 DEFAULT '' COMMENT '链接地址',
  `sender_id` smallint(4) unsigned DEFAULT '0' COMMENT '发送者编号，如果为0，则为系统发送',
  `receiver_id` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '接收人',
  `send_time` int(10) DEFAULT NULL,
  `expire_time` int(10) DEFAULT '0' COMMENT '过期时间，基于send_time',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=772 DEFAULT CHARSET=utf8mb4 COMMENT='消息表';

-- ----------------------------
-- Records of pms_message
-- ----------------------------

-- ----------------------------
-- Table structure for pms_projects
-- ----------------------------
DROP TABLE IF EXISTS `pms_projects`;
CREATE TABLE `pms_projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '项目名称',
  `description` text COMMENT '项目描述',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '项目状态，1：待开发，2：开发中，3：完成开发，4开发终止，5，维护中',
  `vcs_host` varchar(255) DEFAULT '' COMMENT '版本控制地址',
  `version_number` varchar(20) NOT NULL DEFAULT '' COMMENT '项目版本',
  `expected_start_time` int(10) NOT NULL DEFAULT '0' COMMENT '预期开始时间',
  `expected_end_time` int(10) NOT NULL DEFAULT '0' COMMENT '预期截至时间',
  `real_start_time` int(10) NOT NULL DEFAULT '0' COMMENT '实际开始时间',
  `real_end_time` int(10) NOT NULL DEFAULT '0' COMMENT '实际完成时间',
  `project_doc_attachement` varchar(255) DEFAULT '' COMMENT '项目文档',
  `create_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '创建用户',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除，默认为否',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COMMENT='项目';

-- ----------------------------
-- Records of pms_projects
-- ----------------------------

-- ----------------------------
-- Table structure for pms_setting
-- ----------------------------
DROP TABLE IF EXISTS `pms_setting`;
CREATE TABLE `pms_setting` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称',
  `value` text COMMENT '值',
  `comment` varchar(255) DEFAULT '' COMMENT '配置注释',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- ----------------------------
-- Records of pms_setting
-- ----------------------------
INSERT INTO `pms_setting` VALUES ('9', 'findPasswordExpiredTime', 'O:27:\"app\\models\\SettingSerialize\":1:{s:34:\"\0app\\models\\SettingSerialize\0value\";i:300;}', '找回密码过期时间');
INSERT INTO `pms_setting` VALUES ('10', 'messageExpireTime', 'O:27:\"app\\models\\SettingSerialize\":1:{s:34:\"\0app\\models\\SettingSerialize\0value\";i:120;}', '消息过期时间');
INSERT INTO `pms_setting` VALUES ('11', 'admin', 'O:27:\"app\\models\\SettingSerialize\":1:{s:34:\"		', '管理员用户名');
INSERT INTO `pms_setting` VALUES ('12', 'accessControlRoute', 'O:27:\"app\\models\\SettingSerialize\":1:{s:34:\"\0app\\models\\SettingSerialize\0value\";O:8:\"stdClass\":4:{s:6:\"member\";a:1:{i:0;s:4:\"list\";}s:7:\"project\";s:1:\"*\";s:2:\"my\";s:1:\"*\";s:15:\"task-change-log\";s:1:\"*\";}}', '非管理员访问控制');
INSERT INTO `pms_setting` VALUES ('13', 'systemTitle', 'O:27:\"app\\models\\SettingSerialize\":1:{s:34:\"\0app\\models\\SettingSerialize\0value\";s:25:\"任务管理系统(alpha)\";}', '站点title');

-- ----------------------------
-- Table structure for pms_shortcut_signin
-- ----------------------------
DROP TABLE IF EXISTS `pms_shortcut_signin`;
CREATE TABLE `pms_shortcut_signin` (
  `id` int(10) NOT NULL,
  `member_id` smallint(4) DEFAULT '0' COMMENT '成员编号，发送时若无匹配的成员邮箱时，则将匿名登录',
  `email` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `token` varchar(80) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '登录token',
  `expired_timestamp` int(10) DEFAULT '0' COMMENT '登陆后过期时间戳',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='邮箱消息快捷登录，如果member_id为空时，仅能对部分页面进行查询';

-- ----------------------------
-- Records of pms_shortcut_signin
-- ----------------------------

-- ----------------------------
-- Table structure for pms_tasks
-- ----------------------------
DROP TABLE IF EXISTS `pms_tasks`;
CREATE TABLE `pms_tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) NOT NULL DEFAULT '0' COMMENT '项目编号',
  `task_id` int(10) NOT NULL DEFAULT '0' COMMENT '如果大于0，则是由此编号的任务产生的一个子任务 ',
  `fork_activity_count` smallint(5) NOT NULL DEFAULT '0' COMMENT '子任务活跃数量，该字段用于记录直接生成的子任务数量，子任务完成或者终止，则将该值递减',
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '任务名称',
  `description` longtext COMMENT '任务描述',
  `priority` tinyint(2) NOT NULL DEFAULT '20' COMMENT '任务优先级，1为紧急，10为中等紧急，20为普通',
  `difficulty` float(5,5) DEFAULT '0.00000' COMMENT '任务难度值，取值为0-1之间，由接受人与直接上级沟通后确认，默认为中等',
  `type` smallint(3) NOT NULL DEFAULT '1' COMMENT '任务类型，1为后端开发维护，2为前端开发维护，3为页面设计，4为后端bug修改，5为前端bug修改，99为其他',
  `is_valid` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效任务，1为有效，0为无效',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '任务状态，1为待领取，20为实施中，30为待测试验收，40为完成',
  `publisher_id` int(10) NOT NULL DEFAULT '0' COMMENT '发布人',
  `publish_time` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `receive_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '接受人',
  `receive_time` int(10) NOT NULL DEFAULT '0' COMMENT '接受任务时间',
  `expected_finish_time` int(10) DEFAULT '0' COMMENT '期望完成工作日（针对发布人）',
  `real_finish_time` int(10) NOT NULL DEFAULT '0' COMMENT '实际完成时间',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除，默认为否',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `task_id` (`task_id`),
  KEY `status` (`status`),
  KEY `receive_user_id` (`receive_user_id`),
  KEY `publisher_id` (`publisher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=436 DEFAULT CHARSET=utf8mb4 COMMENT='项目任务';

-- ----------------------------
-- Records of pms_tasks
-- ----------------------------

-- ----------------------------
-- Table structure for pms_tasks_log
-- ----------------------------
DROP TABLE IF EXISTS `pms_tasks_log`;
CREATE TABLE `pms_tasks_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id` int(10) unsigned NOT NULL,
  `project_id` int(10) NOT NULL DEFAULT '0' COMMENT '项目编号',
  `task_id` int(10) NOT NULL DEFAULT '0' COMMENT '如果大于0，则是由此编号的任务产生的一个子任务 ',
  `fork_activity_count` smallint(5) NOT NULL DEFAULT '0' COMMENT '子任务活跃数量，该字段用于记录直接生成的子任务数量，子任务完成或者终止，则将该值递减',
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '任务名称',
  `description` longtext COMMENT '任务描述',
  `priority` tinyint(2) NOT NULL DEFAULT '20' COMMENT '任务优先级，1为紧急，10为中等紧急，20为普通',
  `difficulty` float(5,5) NOT NULL DEFAULT '0.50000' COMMENT '任务难度值，取值为0-1之间，由接受人与直接上级沟通后确认，默认为中等',
  `type` smallint(3) NOT NULL DEFAULT '1' COMMENT '任务类型，1为后端开发维护，2为前端开发维护，3为页面设计，4为后端bug修改，5为前端bug修改，99为其他',
  `is_valid` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效任务，1为有效，0为无效',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '任务状态，1为待领取，20为实施中，30为待测试验收，40为完成',
  `publisher_id` int(10) NOT NULL DEFAULT '0' COMMENT '发布人',
  `publish_time` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `receive_user_id` int(10) NOT NULL DEFAULT '0' COMMENT '接受人',
  `receive_time` int(10) NOT NULL DEFAULT '0' COMMENT '接受任务时间',
  `expected_finish_time` int(10) DEFAULT '0' COMMENT '期望完成工作日（针对发布人）',
  `real_finish_time` int(10) NOT NULL DEFAULT '0' COMMENT '实际完成时间',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `is_delete` tinyint(1) DEFAULT '0' COMMENT '是否删除，默认为否',
  `log_time` int(10) DEFAULT '0' COMMENT '记录时间',
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1471 DEFAULT CHARSET=utf8mb4 COMMENT='项目任务更细日志表，该表作为任务的更修改记录，该表与task表为N:1关系';

-- ----------------------------
-- Records of pms_tasks_log
-- ----------------------------

-- ----------------------------
-- Table structure for pms_task_category
-- ----------------------------
DROP TABLE IF EXISTS `pms_task_category`;
CREATE TABLE `pms_task_category` (
  `id` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '类型名称',
  `show_order` smallint(3) DEFAULT '999' COMMENT '显示顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of pms_task_category
-- ----------------------------

-- ----------------------------
-- Table structure for pms_task_code_fragment
-- ----------------------------
DROP TABLE IF EXISTS `pms_task_code_fragment`;
CREATE TABLE `pms_task_code_fragment` (
  `task_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '任务编号',
  `code_fragment` text COMMENT '代码片段',
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='该任务所涉及的代码片段';

-- ----------------------------
-- Records of pms_task_code_fragment
-- ----------------------------

-- ----------------------------
-- Table structure for pms_task_details
-- ----------------------------
DROP TABLE IF EXISTS `pms_task_details`;
CREATE TABLE `pms_task_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) NOT NULL DEFAULT '0' COMMENT '任务',
  `content` longtext COMMENT '工作内容',
  `start_time` int(10) NOT NULL DEFAULT '0' COMMENT '任务开始时间',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '任务截至时间',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=384 DEFAULT CHARSET=utf8mb4 COMMENT='此表为任务细节进度，比如一个任务持续多天等等';

-- ----------------------------
-- Records of pms_task_details
-- ----------------------------

-- ----------------------------
-- Table structure for pms_task_lifecycle
-- ----------------------------
DROP TABLE IF EXISTS `pms_task_lifecycle`;
CREATE TABLE `pms_task_lifecycle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) NOT NULL,
  `message` varchar(255) DEFAULT '' COMMENT '备注信息',
  `member_id` int(10) DEFAULT '0' COMMENT '参与人',
  `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1949 DEFAULT CHARSET=utf8mb4 COMMENT='指定任务的过程信息';

-- ----------------------------
-- Records of pms_task_lifecycle
-- ----------------------------

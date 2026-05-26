# 需求文档 - 教师推荐奖励功能

## 简介

教师推荐奖励功能是一个用户增长激励系统，允许所有用户（无需认证）生成推荐码并分享给其他用户。当推荐成功后，推荐人和被推荐人各获得20元抵扣券。抵扣券采用人工核销方式，由管理员在后台操作。系统包含活动页面、推荐排行榜、小程序端展示和管理后台功能。

## 术语表

- **System**: 教师推荐奖励系统
- **Referral_Code_Generator**: 推荐码生成器
- **Coupon_Manager**: 抵扣券管理器
- **Ranking_System**: 排行榜系统
- **Admin_Backend**: 管理后台
- **Mini_Program**: 小程序端
- **User**: 用户（推荐人或被推荐人）
- **Administrator**: 系统管理员
- **Referral_Code**: 推荐码
- **Coupon**: 抵扣券
- **Referral_Record**: 推荐记录

## 需求

### 需求 1: 推荐码生成

**用户故事:** 作为用户，我希望能够生成唯一的推荐码，以便分享给其他用户并获得奖励。

#### 验收标准

1. THE Referral_Code_Generator SHALL generate a unique referral code for any user without requiring authentication
2. WHEN a user requests a referral code, THE Referral_Code_Generator SHALL return a code that is unique across the system
3. THE Referral_Code_Generator SHALL ensure each referral code is associated with the generating user's identifier
4. WHEN a referral code is generated, THE System SHALL store the code with timestamp and user association in the database

### 需求 2: 推荐码分享

**用户故事:** 作为推荐人，我希望能够方便地分享我的推荐码，以便邀请其他用户注册。

#### 验收标准

1. WHEN a user views their referral code, THE Mini_Program SHALL display the code in a shareable format
2. THE Mini_Program SHALL provide a share button that allows users to share their referral code through WeChat
3. WHEN a user clicks the share button, THE System SHALL generate a share link containing the referral code
4. THE Mini_Program SHALL display a QR code containing the referral code for offline sharing

### 需求 3: 推荐关系建立

**用户故事:** 作为被推荐人，我希望通过推荐码注册，以便与推荐人建立关联并获得奖励。

#### 验收标准

1. WHEN a new user accesses the system through a referral link, THE System SHALL capture and validate the referral code
2. WHEN a valid referral code is provided during registration, THE System SHALL create a referral relationship between the referrer and the new user
3. IF an invalid or expired referral code is provided, THEN THE System SHALL reject the referral and notify the user
4. THE System SHALL prevent users from using their own referral code
5. WHEN a referral relationship is established, THE System SHALL record the timestamp and both user identifiers

### 需求 4: 抵扣券发放

**用户故事:** 作为系统，我需要在推荐成功后为双方发放抵扣券，以便激励用户参与推荐活动。

#### 验收标准

1. WHEN a referral relationship is successfully established, THE Coupon_Manager SHALL create two coupons of 20 yuan each
2. THE Coupon_Manager SHALL assign one coupon to the referrer and one coupon to the referred user
3. WHEN coupons are created, THE System SHALL set their initial status to "pending verification"
4. THE System SHALL record the coupon creation timestamp and associated referral record
5. THE Coupon_Manager SHALL ensure each coupon contains the user identifier, amount, status, and expiration information

### 需求 5: 抵扣券人工核销

**用户故事:** 作为管理员，我希望能够手动核销抵扣券，以便控制奖励发放流程。

#### 验收标准

1. WHEN an administrator views the coupon management interface, THE Admin_Backend SHALL display all pending coupons with user and referral information
2. WHEN an administrator selects a coupon for verification, THE Admin_Backend SHALL display detailed information including user details and referral record
3. WHEN an administrator approves a coupon, THE Coupon_Manager SHALL update the coupon status to "approved" and record the administrator identifier and timestamp
4. WHEN an administrator rejects a coupon, THE Coupon_Manager SHALL update the coupon status to "rejected" and require a rejection reason
5. THE Admin_Backend SHALL provide batch approval functionality for multiple coupons
6. WHEN a coupon status is updated, THE System SHALL notify the associated user through the Mini_Program

### 需求 6: 推荐排行榜

**用户故事:** 作为用户，我希望查看推荐排行榜，以便了解自己的推荐成绩和其他用户的表现。

#### 验收标准

1. THE Ranking_System SHALL calculate user rankings based on the number of successful referrals
2. WHEN a user views the ranking page, THE Mini_Program SHALL display the top users with their referral counts
3. THE Ranking_System SHALL update rankings in real-time when new referral relationships are established
4. WHEN displaying rankings, THE System SHALL show user nickname, avatar, and referral count
5. THE Mini_Program SHALL highlight the current user's position in the ranking list
6. WHERE a user has no successful referrals, THE System SHALL display their ranking as unranked

### 需求 7: 活动页面展示

**用户故事:** 作为用户，我希望在小程序中看到清晰的活动信息，以便了解推荐奖励规则和参与方式。

#### 验收标准

1. THE Mini_Program SHALL display an activity banner on the home page promoting the referral reward program
2. WHEN a user clicks the activity banner, THE Mini_Program SHALL navigate to the activity detail page
3. THE Mini_Program SHALL display activity rules including reward amount, eligibility, and terms
4. THE Mini_Program SHALL show the user's current referral code prominently on the activity page
5. THE Mini_Program SHALL display the user's referral statistics including total referrals and pending rewards

### 需求 8: 推荐列表管理

**用户故事:** 作为推荐人，我希望查看我的推荐记录，以便跟踪我邀请的用户和获得的奖励。

#### 验收标准

1. WHEN a user views their referral list, THE Mini_Program SHALL display all users they have referred
2. THE Mini_Program SHALL show each referral record with referred user information, registration time, and reward status
3. THE System SHALL allow users to filter referral records by status (pending, approved, rejected)
4. WHEN displaying referral records, THE Mini_Program SHALL show the associated coupon status for each referral
5. THE Mini_Program SHALL provide pagination for referral lists with more than 20 records

### 需求 9: 管理后台 - 邀请管理

**用户故事:** 作为管理员，我希望管理所有推荐记录，以便监控推荐活动和处理异常情况。

#### 验收标准

1. WHEN an administrator accesses the invitation management module, THE Admin_Backend SHALL display all referral records with filtering and search capabilities
2. THE Admin_Backend SHALL allow administrators to search referral records by user identifier, referral code, or date range
3. WHEN viewing a referral record, THE Admin_Backend SHALL display complete information including referrer, referred user, timestamp, and coupon status
4. THE Admin_Backend SHALL allow administrators to manually invalidate suspicious referral relationships
5. WHEN a referral is invalidated, THE System SHALL update associated coupon status to "cancelled" and record the reason

### 需求 10: 管理后台 - 抵扣券管理

**用户故事:** 作为管理员，我希望全面管理抵扣券，以便确保奖励发放的准确性和公平性。

#### 验收标准

1. WHEN an administrator accesses the coupon management module, THE Admin_Backend SHALL display all coupons with their current status
2. THE Admin_Backend SHALL provide filtering options by status (pending, approved, rejected, used, expired)
3. WHEN viewing coupon details, THE Admin_Backend SHALL display user information, referral record, creation time, and verification history
4. THE Admin_Backend SHALL allow administrators to manually adjust coupon amounts with reason documentation
5. THE Admin_Backend SHALL generate reports showing coupon statistics including total issued, approved, used, and expired coupons
6. WHEN exporting coupon data, THE Admin_Backend SHALL generate CSV files with complete coupon information

### 需求 11: 抵扣券使用

**用户故事:** 作为用户，我希望使用已核销的抵扣券，以便在支付时获得折扣。

#### 验收标准

1. WHEN a user initiates a payment, THE System SHALL display all approved and unused coupons available to the user
2. WHEN a user selects a coupon for payment, THE System SHALL validate the coupon status and expiration date
3. IF a coupon is expired or already used, THEN THE System SHALL prevent its application and notify the user
4. WHEN a coupon is successfully applied, THE System SHALL deduct the coupon amount from the total payment
5. WHEN a payment is completed with a coupon, THE Coupon_Manager SHALL update the coupon status to "used" and record the usage timestamp and order identifier

### 需求 12: 数据持久化

**用户故事:** 作为系统，我需要可靠地存储所有推荐和抵扣券数据，以便确保数据完整性和可追溯性。

#### 验收标准

1. THE System SHALL persist all referral codes with user associations to the database
2. THE System SHALL persist all referral relationships with timestamps and status information
3. THE System SHALL persist all coupons with complete lifecycle information including creation, verification, and usage
4. WHEN any data modification occurs, THE System SHALL record the operation timestamp and operator identifier
5. THE System SHALL implement database transactions to ensure data consistency across related tables
6. THE System SHALL maintain audit logs for all administrative actions on referrals and coupons

### 需求 13: 通知机制

**用户故事:** 作为用户，我希望及时收到推荐奖励相关的通知，以便了解我的奖励状态。

#### 验收标准

1. WHEN a new user registers through a referral code, THE System SHALL send a notification to the referrer
2. WHEN a coupon is approved by an administrator, THE System SHALL send a notification to the coupon owner
3. WHEN a coupon is rejected, THE System SHALL send a notification with the rejection reason to the user
4. WHEN a coupon is about to expire, THE System SHALL send a reminder notification to the user
5. THE System SHALL support WeChat template messages for all notification types
6. WHERE a notification fails to send, THE System SHALL retry up to three times and log the failure

### 需求 14: 安全和防作弊

**用户故事:** 作为系统管理员，我希望防止推荐系统被滥用，以便保护活动的公平性和公司利益。

#### 验收标准

1. THE System SHALL prevent users from referring themselves
2. THE System SHALL detect and flag suspicious referral patterns such as multiple referrals from the same device or IP address
3. WHEN suspicious activity is detected, THE System SHALL automatically mark associated coupons as "pending review"
4. THE System SHALL limit the maximum number of referrals per user per day to prevent abuse
5. THE System SHALL validate that referred users are genuine new users and not duplicate accounts
6. WHEN fraud is confirmed, THE Admin_Backend SHALL allow administrators to blacklist users from future participation

### 需求 15: 性能和可扩展性

**用户故事:** 作为系统架构师，我希望系统能够处理大量并发请求，以便支持活动的规模增长。

#### 验收标准

1. WHEN generating referral codes, THE Referral_Code_Generator SHALL complete the operation within 200 milliseconds
2. WHEN calculating rankings, THE Ranking_System SHALL update results within 1 second for up to 10,000 users
3. THE System SHALL support at least 1,000 concurrent users accessing the activity page
4. WHEN querying referral records, THE System SHALL return results within 500 milliseconds for datasets up to 100,000 records
5. THE System SHALL implement caching for frequently accessed data such as rankings and activity rules

# Campaign Scheduling API Integration Guide

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [API Endpoints](#api-endpoints)
4. [Request Structure](#request-structure)
5. [Response Format](#response-format)
6. [Dynamic Variables](#dynamic-variables)
7. [WhatsApp Meta Templates](#whatsapp-meta-templates)
8. [Communication Groups](#communication-groups)
9. [Record Structure](#record-structure)
10. [Database Schema](#database-schema)
11. [Campaign Execution](#campaign-execution)
12. [Error Handling](#error-handling)
13. [Testing Guide](#testing-guide)
14. [Troubleshooting](#troubleshooting)

---

## Overview

This document describes the unified campaign scheduling system that powers both the web UI (`/campaigns/create`) and the API endpoints (`/api/campaigns/schedule/*`). Both flows use the same `CampaignSchedulingService` to ensure consistent behavior across all channels.

### Key Features

- **Unified Logic**: Web and API use identical scheduling service
- **Multi-Channel Support**: Email, SMS, and WhatsApp campaigns
- **Template System**: Use stored templates or provide messages directly
- **Dynamic Variables**: Personalize messages with recipient data
- **Audience Management**: Automatic group creation and persistence
- **System Messages**: Send transactional messages without cluttering campaign list
- **Comprehensive Logging**: Track every step of campaign creation
- **Instant Execution**: API campaigns are fired immediately upon creation
- **Flexible Scheduling**: Schedule campaigns for immediate or future delivery

---

## System Messages vs Regular Campaigns

The API supports two types of communications:

### Regular Campaigns

- **Purpose**: Marketing campaigns, newsletters, bulk communications
- **Visibility**: Visible in the campaign list UI (`/campaigns`)
- **Audience Persistence**: Creates a `CommunicationGroup` for reuse
- **Requirement**: Must provide `campaign_name` parameter

### System Messages

- **Purpose**: Transactional messages, OTPs, notifications, alerts
- **Visibility**: Hidden from campaign list UI (stored only for records and credit tracking)
- **Audience Persistence**: No group created (saves database space)
- **Requirement**: Send `campaign_name` as empty string `""` or omit it entirely
- **Use Cases**:
    - OTP verification codes
    - Password reset emails
    - Order confirmations
    - Payment receipts
    - Account notifications
    - Automated alerts

**How to Send System Messages:**
Simply omit the `campaign_name` field or send it as an empty string:

```json
{
  "account_id": "acct_123",
  "campaign_name": "",  // Empty = System Message
  "schedule_at": "2026-02-17T10:00:00Z",
  "message": "Your OTP is: 123456",
  "records": [...]
}
```

Or omit it completely:

```json
{
  "account_id": "acct_123",
  // No campaign_name field
  "schedule_at": "2026-02-17T10:00:00Z",
  "message": "Your OTP is: 123456",
  "records": [...]
}
```

**Benefits of System Messages:**

- ✅ Credits are properly tracked and deducted
- ✅ Full delivery logs maintained in `mail_logs`, `sms_logs`, `whatsapp_logs`
- ✅ Lead timeline entries created (if `leadid` provided)
- ✅ No clutter in campaign management UI
- ✅ No unnecessary audience groups created
- ✅ Perfect for high-volume transactional messages

---

## Architecture

### Service Layer

The `CampaignSchedulingService` (located at `app/Services/CampaignSchedulingService.php`) handles:

- **Platform payload resolution**: Merges user data with stored templates
- **Outbox creation**: Creates `CommunicationOutbox` entries for each recipient
- **Channel-specific logging**: Generates `MailLog`, `SmsLog`, and `WhatsappLog` entries
- **Dynamic variable replacement**: Processes placeholders like `{{name}}`, `{#var#}`, etc.
- **Template normalization**: Handles HTML entity decoding and message formatting

### Request Flow

```
API Request
    ↓
CampaignsController::schedule()
    ↓
Validation & User Resolution
    ↓
CampaignSchedulingService::buildPlatformConfigs()
    ↓
Create CommunicationManager & CommunicationGroup
    ↓
CampaignSchedulingService::createCampaignLogs()
    ↓
Create CommunicationOutbox & Platform Logs
    ↓
Dispatch Jobs Immediately (Email/SMS/WhatsApp)
    ↓
Return campaign_id
```

**Note**: Campaigns created via API are executed immediately. The system dispatches the appropriate jobs (SendScheduledCampaignEmails, SendScheduledCampaignSms, SendScheduledCampaignWhatsapps) right after campaign creation, ensuring instant delivery.

---

## API Endpoints

### Base URL

```
http://your-domain.com/api
```

### Campaign Scheduling Endpoints

#### 1. Email Campaign

```
POST /api/campaigns/schedule/email
```

#### 2. SMS Campaign

```
POST /api/campaigns/schedule/sms
```

#### 3. WhatsApp Campaign

```
POST /api/campaigns/schedule/whatsapp
```

### Resource Endpoints

#### 4. Get Credits

```
GET /api/credits?account_id={account_id}
```

Returns available communication credits for email, SMS, and WhatsApp.

**Response Example:**

```json
{
    "status": "success",
    "data": {
        "account_id": "HaRXbhoCzM",
        "credits": {
            "email": 1000,
            "sms": 500,
            "whatsapp": 250
        },
        "total": 1750
    }
}
```

#### 5. Get Email Templates

```
GET /api/templates/email?account_id={account_id}
```

Returns all email templates for the account.

**Response Example:**

```json
{
    "status": "success",
    "data": {
        "account_id": "HaRXbhoCzM",
        "templates": [
            {
                "id": "mail_template_42",
                "name": "Welcome Email",
                "subject": "Welcome to Our Platform",
                "body": "<html>...</html>",
                "exported_body": "<html>...</html>",
                "editor_type": "mjml",
                "created_at": "2026-01-15 10:30:00"
            }
        ],
        "count": 1
    }
}
```

#### 6. Get SMS Templates

```
GET /api/templates/sms?account_id={account_id}
```

Returns all SMS templates for the account.

**Response Example:**

```json
{
    "status": "success",
    "data": {
        "account_id": "HaRXbhoCzM",
        "templates": [
            {
                "id": "sms_template_15",
                "name": "Flash Sale SMS",
                "body": "Hi {{name}}, Flash Sale! Get 30% off today.",
                "sender_id": "CAMPIO",
                "created_at": "2026-01-20 14:00:00"
            }
        ],
        "count": 1
    }
}
```

#### 7. Get WhatsApp Templates

```
GET /api/templates/whatsapp?account_id={account_id}
```

Returns all approved WhatsApp templates for the account.

**Response Example:**

```json
{
    "status": "success",
    "data": {
        "account_id": "HaRXbhoCzM",
        "templates": [
            {
                "id": "wa_template_42",
                "name": "enrollment_confirmation_v2",
                "language": "en_US",
                "category": "MARKETING",
                "status": "APPROVED",
                "header_type": "TEXT",
                "header_value": "Enrollment Confirmed",
                "header_variable": 0,
                "body_variable": 3,
                "template_body": "Hello {{1}}, your enrollment for {{2}} is confirmed!",
                "meta_template_id": "1209484998039912",
                "created_at": "2026-01-25 09:00:00"
            }
        ],
        "count": 1
    }
}
```

---

#### 8. Communication Log

```
POST /api/communication-log
```

Returns the full log details (subject, body, recipient, attachments) for a timeline entry. Used by CRM to populate the "View" modal on communication timeline entries.

**Request:**

```json
{
    "account_id": "ACC123",
    "tlid": "TL789"
}
```

| Field        | Type   | Required | Description                                |
| ------------ | ------ | -------- | ------------------------------------------ |
| `account_id` | string | Yes      | Account identifier                         |
| `tlid`       | string | Yes      | Timeline ID from the `lead_timeline` table |

**Success Response (200):**

```json
{
    "status": "success",
    "data": {
        "tlid": "TL789",
        "leadid": "LEAD456",
        "accountid": "ACC123",
        "channel": "email",
        "subject": "Welcome to Our Platform!",
        "message": "<html><body><h1>Hello John</h1><p>Welcome!</p></body></html>",
        "sent_to": "john@example.com",
        "from": "team@company.com",
        "from_name": "Company Team",
        "attachments_count": 2,
        "attachments": [
            {
                "file_url": "https://cdn.example.com/docs/brochure.pdf",
                "file_name": "brochure.pdf",
                "content_type": "application/pdf",
                "category": "attachments",
                "url_type": "url_type_0"
            }
        ],
        "entry_by": "John Doe",
        "note": "Email Sent | Source: API",
        "dt": "2026-02-12 10:00:00"
    }
}
```

**Channel resolution** (first match wins):

1. MailLog for this `accountid` + `tlid` → `"email"`
2. SmsLog → `"sms"`
3. WhatsappLog → `"whatsapp"`
4. None → `"timeline"` (no communication data)

**Attachment resolution** (email only):

- Resolved from `CommunicationManager.email_attachments` (JSON array of doc IDs) or from `email_data` payload
- Documents fetched via `Docmanager` model and returned with `file_url` and `file_name`

**Error Responses:**

```json
// 422 - Missing fields
{ "status": "error", "message": "Missing required fields: account_id, tlid" }

// 404 - Not found
{ "status": "error", "message": "Timeline entry not found" }
```

---

#### 9. Send Email (Quick-Send)

```
POST /api/mail/send
```

Sends a single transactional email. Creates a MailLog entry, lead timeline entry (if `lead_id` provided), and dispatches delivery. Used by CRM for quick email sends.

**Request:**

```json
{
    "from_email": "team@company.com",
    "from_name": "Company Team",
    "to_email": "john@example.com",
    "subject": "Welcome!",
    "body": "<html><body><h1>Hello</h1></body></html>",
    "url": "https://crm.app/leads/123",
    "source": "crm",
    "account_id": "ACC123",
    "lead_id": "LEAD456",
    "userid": "USER789"
}
```

| Field         | Type   | Required | Description                                    |
| ------------- | ------ | -------- | ---------------------------------------------- |
| `from_email`  | string | Yes      | Sender email address                           |
| `from_name`   | string | Yes      | Sender name                                    |
| `to_email`    | string | Yes      | Recipient email address                        |
| `subject`     | string | Yes      | Email subject line                             |
| `body`        | string | Yes      | Email body (HTML)                              |
| `url`         | string | Yes      | Origin URL for tracking                        |
| `source`      | string | Yes      | Source identifier                              |
| `account_id`  | string | No       | Account identifier (auto-resolved if omitted)  |
| `lead_id`     | string | No       | Lead ID for timeline entry creation            |
| `userid`      | string | No       | User ID for timeline entry attribution         |
| `attachments` | array  | No       | Array of `{file_url, file_name, content_type}` |

---

#### 10. Send SMS (Quick-Send)

```
POST /api/sms/send
```

Sends a single SMS message. Creates an SmsLog entry, lead timeline entry (if `leadid` provided), and dispatches delivery. Used by CRM for quick SMS sends.

**Request:**

```json
{
    "accountid": "ACC123",
    "message": "Your OTP is 123456",
    "mobileNos": "+919000000000",
    "userid": "USER789",
    "leadid": "LEAD456"
}
```

| Field              | Type   | Required | Description                           |
| ------------------ | ------ | -------- | ------------------------------------- |
| `accountid`        | string | Yes      | Account identifier                    |
| `message`          | string | Yes      | SMS text                              |
| `mobileNos`        | string | No\*     | Recipient phone number (E.164 format) |
| `mobile_nos`       | string | No\*     | Alias for mobileNos                   |
| `lead_mobile_list` | string | No\*     | Comma-separated phone numbers         |
| `lead_mobile`      | string | No\*     | Single phone number                   |
| `userid`           | string | No       | User ID for timeline attribution      |
| `leadid`           | string | No       | Lead ID for timeline entry creation   |
| `senderId`         | string | No       | SMS sender ID                         |

\*At least one of `mobileNos`, `mobile_nos`, `lead_mobile_list`, or `lead_mobile` is required.

---

#### 11. Send WhatsApp (Quick-Send)

```
POST /api/whatsapp/send
```

Sends a single WhatsApp message using a WABA template. Creates a WhatsappLog entry, lead timeline entry (if `leadid` provided), and dispatches delivery.

**Request:**

```json
{
    "accountid": "ACC123",
    "waba_template": "enrollment_confirmation_v2",
    "lead_mobile": "+919000000000",
    "userid": "USER789",
    "leadid": "LEAD456",
    "headerText": "Enrollment Confirmed",
    "body_variables": ["John", "Python Course"]
}
```

| Field              | Type   | Required | Description                                                |
| ------------------ | ------ | -------- | ---------------------------------------------------------- |
| `accountid`        | string | Yes      | Account identifier                                         |
| `waba_template`    | string | Yes      | WABA template name (must be approved)                      |
| `lead_mobile`      | string | Yes      | Recipient phone number (E.164 format)                      |
| `userid`           | string | No       | User ID for timeline attribution                           |
| `leadid`           | string | No       | Lead ID for timeline entry creation                        |
| `headerText`       | string | No       | Header text for templates with text headers                |
| `media_url`        | string | No       | URL for image/video/document headers                       |
| `media_filename`   | string | No       | Filename for document headers (falls back to URL basename) |
| `header_variables` | array  | No       | Values for header placeholders                             |
| `body_variables`   | array  | No       | Values for body placeholders                               |

---

## Request Structure

### Required Headers

```
Content-Type: application/json
```

### Required Fields

| Field                      | Type     | Description                                                                     |
| -------------------------- | -------- | ------------------------------------------------------------------------------- |
| `account_id`               | string   | Account identifier (also accepts `accountid` base64 encoded)                    |
| `campaign_name`            | string   | Descriptive name for the campaign (send empty `""` or omit for system messages) |
| `schedule_at`              | datetime | ISO 8601 formatted datetime (e.g., "2026-02-12T10:00:00Z")                      |
| `records`                  | array    | Array of recipient objects (minimum 1 required)                                 |
| `message` OR `template_id` | string   | Either direct message or template ID (at least one required)                    |

**Note on campaign_name:**

- **Regular Campaign**: Provide a descriptive name (e.g., "Welcome Email Campaign")
- **System Message**: Send as empty string `""` or omit the field entirely
    - System messages are hidden from campaign list UI
    - No audience group is created
    - Perfect for transactional messages (OTPs, notifications, alerts)

### Optional Fields

| Field               | Type   | Description                                                          |
| ------------------- | ------ | -------------------------------------------------------------------- |
| `subject`           | string | Email subject line (email campaigns only)                            |
| `template_id`       | string | ID of stored template to use                                         |
| `template_name`     | string | Name of template (auto-populated if using template_id)               |
| `sender_id`         | string | Sender identifier (SMS sender ID, email from name)                   |
| `meta_template_id`  | string | Meta/WABA template identifier for WhatsApp                           |
| `header_text`       | string | Header text for WhatsApp templates                                   |
| `media_url`         | string | URL for media attachments (WhatsApp)                                 |
| `media_filename`    | string | Filename for document headers (WhatsApp, falls back to URL basename) |
| `dynamic_context`   | object | Variable mapping configuration                                       |
| `variables`         | array  | Sequential variable values for positional placeholders               |
| `source_url`        | string | Origin URL for tracking (recommended)                                |
| `notes`             | string | Campaign notes/description                                           |
| `group_source`      | string | Source identifier for audience group (defaults to "api")             |
| `platforms`         | array  | Full platform configuration array (alternative to shorthand)         |
| `attachments`       | array  | Array of `{file_url, file_name, content_type}` objects (email only)  |
| `attachment_docids` | array  | Array of Docmanager document IDs to attach (email only)              |

### Request Format Options

You can structure requests in two ways:

**Option 1: Shorthand Fields** (Recommended for single platform)

```json
{
  "account_id": "acct_123",
  "campaign_name": "My Campaign",
  "schedule_at": "2026-02-12T10:00:00Z",
  "message": "Hello {{name}}",
  "subject": "Welcome",
  "records": [...]
}
```

**Option 2: Full Platforms Array** (For advanced use cases)

```json
{
  "account_id": "acct_123",
  "campaign_name": "My Campaign",
  "schedule_at": "2026-02-12T10:00:00Z",
  "platforms": [
    {
      "platform": "email",
      "template_id": "mail_template_42",
      "payload": {
        "subject": "Welcome",
        "message": "Hello {{name}}"
      }
    }
  ],
  "records": [...]
}
```

---

## Response Format

### Success Response (200 OK)

```json
{
    "status": "success",
    "campaign_id": "camp_abc123xyz",
    "message": "Success"
}
```

**Response Fields:**

- `status`: Always "success" for successful requests
- `campaign_id`: Unique identifier for the created campaign
- `message`: Confirmation that the campaign has been created and is being executed

**Use the campaign_id to:**

- Track campaign status in `communication_manager` table
- Query delivery logs in `mail_logs`, `sms_logs`, or `whatsapp_logs`
- Reference the campaign in analytics or reporting

**Important**: Campaigns created via API are executed immediately. The system dispatches jobs to send messages right after creation, so you should see delivery logs appearing within seconds.

### Error Responses

#### 403 Forbidden - Invalid Account

```json
{
    "status": "error",
    "message": "Invalid account_id provided."
}
```

#### 422 Unprocessable Entity - Validation Errors

```json
{
    "status": "error",
    "message": "The campaign name field is required.",
    "errors": {
        "campaign_name": ["The campaign name field is required."],
        "records": ["The records field is required."]
    }
}
```

#### 422 Unprocessable Entity - Platform Error

```json
{
    "status": "error",
    "message": "Email payload could not be built."
}
```

#### 422 Unprocessable Entity - No Valid Platforms

```json
{
    "status": "error",
    "message": "No valid platform configurations provided."
}
```

---

## Complete Request Examples

### 1. Email Campaign - Basic

```json
POST /api/campaigns/schedule/email

{
  "account_id": "acct_123",
  "campaign_name": "Welcome Email Campaign",
  "schedule_at": "2026-02-12T10:00:00Z",
  "subject": "Welcome to Our Platform!",
  "message": "<html><body><h1>Hello {{name}}</h1><p>Welcome to our platform. We're excited to have you!</p></body></html>",
  "sender_id": "Campio Team",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      }
    ]
  },
  "records": [
    {
      "id": "lead-001",
      "email": "john.doe@example.com",
      "student_customer_name": "John Doe",
      "mobile": "+919000000001",
      "city": "Mumbai"
    },
    {
      "id": "lead-002",
      "email": "jane.smith@example.com",
      "student_customer_name": "Jane Smith",
      "mobile": "+919000000002",
      "city": "Delhi"
    }
  ],
  "source_url": "https://partner.app/campaigns/welcome-email",
  "notes": "Welcome campaign for new signups"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP001XYZ"
}
```

### 1a. Email Campaign - With Attachments

```json
POST /api/campaigns/schedule/email

{
  "account_id": "acct_123",
  "campaign_name": "Course Brochure Email",
  "schedule_at": "2026-02-12T10:00:00Z",
  "subject": "Course Brochure Attached",
  "message": "<html><body><h1>Hello {{name}}</h1><p>Please find the brochure attached.</p></body></html>",
  "platforms": [
    {
      "platform": "email",
      "payload": {
        "subject": "Course Brochure Attached",
        "message": "<html><body><h1>Hello {{name}}</h1><p>Please find the brochure attached.</p></body></html>",
        "attachment_docids": ["doc_abc123", "doc_def456"]
      }
    }
  ],
  "records": [
    {
      "id": "lead-001",
      "email": "john.doe@example.com",
      "student_customer_name": "John Doe"
    }
  ]
}
```

**Notes on attachments:**

- `attachment_docids` accepts an array of Docmanager document IDs
- Attachments are resolved at send time from the `docmanager` table
- Attachments appear in the timeline "View" modal via `POST /api/communication-log`
- The `attachments` field (array of `{file_url, file_name, content_type}`) can be used as an alternative to reference external URLs
- Quick-send emails (`/api/mail/send`) do not support Docmanager-based attachments

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP001BRO"
}
```

### 2. Email Campaign - Using Template

```json
POST /api/campaigns/schedule/email

{
  "account_id": "acct_123",
  "campaign_name": "Course Launch Email",
  "schedule_at": "2026-02-15T09:00:00Z",
  "template_id": "mail_template_42",
  "subject": "New Course Alert: {{course_name}}",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      },
      {
        "key": "course_name",
        "type": "custom",
        "value": "Advanced Python Programming"
      },
      {
        "key": "start_date",
        "type": "custom",
        "value": "March 1, 2026"
      }
    ]
  },
  "records": [
    {
      "id": "lead-101",
      "email": "student1@example.com",
      "student_customer_name": "Rahul Kumar",
      "mobile": "+919111111111"
    },
    {
      "id": "lead-102",
      "email": "student2@example.com",
      "student_customer_name": "Priya Sharma",
      "mobile": "+919222222222"
    }
  ],
  "source_url": "https://partner.app/campaigns/course-launch"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP002ABC"
}
```

### 3. SMS Campaign - Basic

```json
POST /api/campaigns/schedule/sms

{
  "account_id": "acct_123",
  "campaign_name": "Flash Sale SMS",
  "schedule_at": "2026-02-12T11:00:00Z",
  "message": "Hi {{name}}, Flash Sale! Get 30% off on all courses today. Use code: FLASH30. Visit: https://example.com/sale",
  "sender_id": "CAMPIO",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      }
    ]
  },
  "records": [
    {
      "id": "lead-201",
      "mobile": "+919000000001",
      "student_customer_name": "Amit Patel"
    },
    {
      "id": "lead-202",
      "mobile": "+919000000002",
      "student_customer_name": "Sneha Reddy"
    }
  ],
  "source_url": "https://partner.app/campaigns/flash-sale-sms"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP003SMS"
}
```

### 4. SMS Campaign - Using Template

```json
POST /api/campaigns/schedule/sms

{
  "account_id": "acct_123",
  "campaign_name": "Class Reminder SMS",
  "schedule_at": "2026-02-13T08:00:00Z",
  "template_id": "sms_template_15",
  "message": "Reminder: Your {{course}} class is tomorrow at {{time}}. Location: {{location}}. See you there!",
  "dynamic_context": {
    "fields": [
      {
        "key": "course",
        "type": "attribute",
        "value": "product_class"
      },
      {
        "key": "time",
        "type": "custom",
        "value": "10:00 AM"
      },
      {
        "key": "location",
        "type": "custom",
        "value": "Room 301"
      }
    ]
  },
  "records": [
    {
      "id": "reg-301",
      "mobile": "+919333333333",
      "student_customer_name": "Vikram Singh",
      "product_class": "Data Science"
    },
    {
      "id": "reg-302",
      "mobile": "+919444444444",
      "student_customer_name": "Anjali Mehta",
      "product_class": "Web Development"
    }
  ],
  "source_url": "https://partner.app/campaigns/class-reminder"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP004REM"
}
```

### 5. WhatsApp Campaign - Text Template

```json
POST /api/campaigns/schedule/whatsapp

{
  "account_id": "acct_123",
  "campaign_name": "Course Enrollment Confirmation",
  "schedule_at": "2026-02-12T12:00:00Z",
  "template_id": "wa_template_42",
  "meta_template_id": "enrollment_confirmation_v2",
  "message": "Hello {{name}}, your enrollment for {{course}} is confirmed! Classes start on {{start_date}}. Welcome aboard!",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      },
      {
        "key": "course",
        "type": "attribute",
        "value": "product_class"
      },
      {
        "key": "start_date",
        "type": "custom",
        "value": "March 1, 2026"
      }
    ]
  },
  "records": [
    {
      "id": "enroll-401",
      "mobile": "+919555555555",
      "student_customer_name": "Rohan Gupta",
      "product_class": "Python Programming",
      "email": "rohan@example.com"
    },
    {
      "id": "enroll-402",
      "mobile": "+919666666666",
      "student_customer_name": "Kavya Iyer",
      "product_class": "Machine Learning",
      "email": "kavya@example.com"
    }
  ],
  "source_url": "https://partner.app/campaigns/enrollment-confirmation"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP005WA"
}
```

### 6. WhatsApp Campaign - Media Template

```json
POST /api/campaigns/schedule/whatsapp

{
  "account_id": "acct_123",
  "campaign_name": "Course Brochure Distribution",
  "schedule_at": "2026-02-14T10:00:00Z",
  "template_id": "wa_template_media_99",
  "meta_template_id": "brochure_template_2026",
  "header_text": "2026 Course Catalog",
    "media_url": "https://cdn.example.com/brochures/2026-catalog.pdf",
    "media_filename": "2026-catalog.pdf",
  "message": "Hi {{name}}, check out our complete 2026 course catalog! Find the perfect course for your career goals.",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      }
    ]
  },
  "records": [
    {
      "id": "lead-501",
      "mobile": "+919777777777",
      "student_customer_name": "Arjun Nair",
      "city": "Bangalore"
    },
    {
      "id": "lead-502",
      "mobile": "+919888888888",
      "student_customer_name": "Divya Kapoor",
      "city": "Pune"
    }
  ],
  "source_url": "https://partner.app/campaigns/brochure-distribution"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP006BRO"
}
```

### 7. Multi-Record Campaign with Complex Variables

```json
POST /api/campaigns/schedule/email

{
  "account_id": "acct_123",
  "campaign_name": "Personalized Course Recommendations",
  "schedule_at": "2026-02-16T14:00:00Z",
  "subject": "{{name}}, courses perfect for you in {{city}}",
  "message": "<html><body><h2>Hi {{name}}</h2><p>Based on your interest in {{interest}}, we recommend our {{course}} program.</p><p>Next batch starts: {{start_date}}</p><p>Location: {{city}}</p><a href='{{enrollment_link}}'>Enroll Now</a></body></html>",
  "dynamic_context": {
    "fields": [
      {
        "key": "name",
        "type": "attribute",
        "value": "student_customer_name"
      },
      {
        "key": "city",
        "type": "attribute",
        "value": "city"
      },
      {
        "key": "interest",
        "type": "attribute",
        "value": "lead_type"
      },
      {
        "key": "course",
        "type": "attribute",
        "value": "product_class"
      },
      {
        "key": "start_date",
        "type": "custom",
        "value": "March 15, 2026"
      },
      {
        "key": "enrollment_link",
        "type": "custom",
        "value": "https://example.com/enroll"
      }
    ]
  },
  "records": [
    {
      "id": "lead-601",
      "email": "tech.enthusiast@example.com",
      "student_customer_name": "Karthik Reddy",
      "mobile": "+919999999991",
      "city": "Hyderabad",
      "lead_type": "Technology",
      "product_class": "Full Stack Development"
    },
    {
      "id": "lead-602",
      "email": "data.lover@example.com",
      "student_customer_name": "Meera Joshi",
      "mobile": "+919999999992",
      "city": "Mumbai",
      "lead_type": "Data Science",
      "product_class": "Data Analytics"
    },
    {
      "id": "lead-603",
      "email": "design.pro@example.com",
      "student_customer_name": "Aditya Sharma",
      "mobile": "+919999999993",
      "city": "Delhi",
      "lead_type": "Design",
      "product_class": "UI/UX Design"
    }
  ],
  "source_url": "https://partner.app/campaigns/personalized-recommendations",
  "notes": "Personalized campaign based on user interests"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP007PER"
}
```

### 8. System Message - OTP Email (No Campaign Name)

```json
POST /api/campaigns/schedule/email

{
  "account_id": "acct_123",
  "campaign_name": "",
  "schedule_at": "2026-02-17T10:00:00Z",
  "subject": "Your OTP Code",
  "message": "<html><body><h2>Your OTP Code</h2><p>Your one-time password is: <strong>{{otp}}</strong></p><p>This code expires in 10 minutes.</p></body></html>",
  "dynamic_context": {
    "fields": [
      {
        "key": "otp",
        "type": "attribute",
        "value": "otp_code"
      }
    ]
  },
  "records": [
    {
      "id": "user-001",
      "email": "user@example.com",
      "otp_code": "123456",
      "leadid": "LEAD789"
    }
  ],
  "source_url": "https://partner.app/auth/otp"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP008SYS"
}
```

**Note:** This message will NOT appear in the campaign list UI but will be tracked for credits and logs.

### 9. System Message - SMS Notification (Omit Campaign Name)

```json
POST /api/campaigns/schedule/sms

{
  "account_id": "acct_123",
  "schedule_at": "2026-02-17T10:05:00Z",
  "message": "Your payment of Rs.{{amount}} has been received. Transaction ID: {{txn_id}}. Thank you!",
  "sender_id": "CAMPIO",
  "dynamic_context": {
    "fields": [
      {
        "key": "amount",
        "type": "attribute",
        "value": "payment_amount"
      },
      {
        "key": "txn_id",
        "type": "attribute",
        "value": "transaction_id"
      }
    ]
  },
  "records": [
    {
      "id": "txn-12345",
      "mobile": "+919000000001",
      "payment_amount": "5000",
      "transaction_id": "TXN123456789",
      "leadid": "LEAD456"
    }
  ],
  "source_url": "https://partner.app/payments/confirm"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP009SYS"
}
```

### 10. System Message - WhatsApp Alert (Empty Campaign Name)

```json
POST /api/campaigns/schedule/whatsapp

{
  "account_id": "acct_123",
  "campaign_name": "",
  "schedule_at": "2026-02-17T10:10:00Z",
  "template_id": "wa_template_alert_01",
  "meta_template_id": "account_alert_v1",
  "message": "ALERT: Your account password was changed on {{date}} at {{time}}. If this wasn't you, contact support immediately.",
  "dynamic_context": {
    "fields": [
      {
        "key": "date",
        "type": "custom",
        "value": "Feb 17, 2026"
      },
      {
        "key": "time",
        "type": "custom",
        "value": "10:10 AM"
      }
    ]
  },
  "records": [
    {
      "id": "user-789",
      "mobile": "+919000000002",
      "student_customer_name": "Rajesh Kumar",
      "leadid": "LEAD999"
    }
  ],
  "source_url": "https://partner.app/security/alerts"
}
```

**Response:**

```json
{
    "status": "success",
    "campaign_id": "CAMP010SYS"
}
```

**System Message Benefits:**

- ✅ Hidden from campaign list UI
- ✅ No audience group created
- ✅ Credits properly tracked
- ✅ Full delivery logs maintained
- ✅ Lead timeline entries created (if leadid provided)
- ✅ Perfect for high-volume transactional messages

---

```

```

#### SMS Campaign

```json
{
    "account_id": "acct_123",
    "campaign_name": "SMS flash sale - Weekend offer",
    "schedule_at": "2026-03-02T09:00:00Z",
    "template_id": "sms_template_42",
    "message": "Hi {{name}}, get 30% off this weekend! Use code: FLASH30. Reply STOP to opt out.",
    "dynamic_context": {
        "fields": [
            {
                "key": "name",
                "type": "attribute",
                "value": "student_customer_name"
            }
        ]
    },
    "source_url": "https://partner.app/campaigns/sms-flash",
    "records": [
        {
            "id": "lead-124",
            "mobile": "+919000000000",
            "student_customer_name": "Anuj Kumar"
        },
        {
            "id": "lead-125",
            "mobile": "+919111111111",
            "student_customer_name": "Priya Sharma"
        }
    ]
}
```

#### WhatsApp Campaign (Text Template)

```json
{
    "account_id": "acct_123",
    "campaign_name": "WhatsApp nurture - Course reminder",
    "schedule_at": "2026-03-02T10:00:00Z",
    "template_id": "wa_template_42",
    "message": "Hello {{name}}, your {{course}} class starts on {{date}}. See you there!",
    "dynamic_context": {
        "fields": [
            {
                "key": "name",
                "type": "attribute",
                "value": "student_customer_name"
            },
            {
                "key": "course",
                "type": "custom",
                "value": "Python Programming"
            },
            { "key": "date", "type": "custom", "value": "March 15, 2026" }
        ]
    },
    "meta_template_id": "meta_whatsapp_abc123",
    "source_url": "https://partner.app/campaigns/whatsapp-nurture",
    "records": [
        {
            "id": "lead-125",
            "mobile": "+919111111111",
            "student_customer_name": "Maya Patel",
            "email": "maya@example.com"
        }
    ]
}
```

#### WhatsApp Campaign (Media Template)

```json
{
    "account_id": "acct_123",
    "campaign_name": "WhatsApp brochure - New courses",
    "schedule_at": "2026-03-02T11:00:00Z",
    "template_id": "wa_template_media_99",
    "message": "Check out our new course catalog!",
    "meta_template_id": "meta_whatsapp_media_xyz",
    "header_text": "Course Catalog 2026",
    "media_url": "https://cdn.example.com/brochure-2026.pdf",
    "media_filename": "brochure-2026.pdf",
    "dynamic_context": {
        "fields": [
            {
                "key": "name",
                "type": "attribute",
                "value": "student_customer_name"
            }
        ]
    },
    "source_url": "https://partner.app/campaigns/whatsapp-brochure",
    "records": [
        {
            "id": "lead-126",
            "mobile": "+919222222222",
            "student_customer_name": "Rahul Singh"
        }
    ]
}
```

````

### Response Format

#### Success Response (200 OK)

```json
{
  "status": "success",
  "campaign_id": "camp_abc123xyz"
}
````

The `campaign_id` can be used to:

- Track campaign status in `communication_manager` table
- Query delivery logs in `mail_logs`, `sms_logs`, or `whatsapp_logs`
- Reference the campaign in analytics or reporting

#### Error Responses

**403 Forbidden - Invalid Account**

```json
{
    "status": "error",
    "message": "Invalid account_id provided."
}
```

**422 Unprocessable Entity - Validation Errors**

```json
{
    "status": "error",
    "message": "The campaign name field is required.",
    "errors": {
        "campaign_name": ["The campaign name field is required."]
    }
}
```

**422 Unprocessable Entity - Platform Configuration Error**

```json
{
    "status": "error",
    "message": "Email payload could not be built."
}
```

**422 Unprocessable Entity - No Valid Platforms**

```json
{
    "status": "error",
    "message": "No valid platform configurations provided."
}
```

### Validation Rules

The API validates the following:

1. **account_id**: Required, must exist in the system
2. **campaign_name**: Required string
3. **schedule_at**: Required, valid datetime format
4. **platforms**: Required array with at least 1 entry
5. **platforms.\*.platform**: Must be one of: email, sms, whatsapp
6. **platforms._.template_id OR platforms._.payload.message**: At least one required
7. **platforms.\_.payload.attachments**: Array of `{file_url, file_name, content_type}` (email only)
8. **platforms.\_.payload.attachment_docids**: Array of Docmanager document IDs (email only)
9. **records**: Required array with at least 1 entry
10. **records.\*.email**: Required for email campaigns
11. **records._.mobile OR records._.phone**: Required for SMS/WhatsApp campaigns

### Dynamic Variables

The `dynamic_context` object controls how placeholders in messages are replaced with actual values.

#### Structure

```json
{
    "dynamic_context": {
        "fields": [
            {
                "key": "placeholder_name",
                "type": "attribute|custom",
                "value": "field_name_or_custom_value"
            }
        ]
    }
}
```

#### Field Types

**attribute**: Maps to a field in the record

```json
{ "key": "name", "type": "attribute", "value": "student_customer_name" }
```

This replaces `{{name}}` with the value of `record.student_customer_name`.

**custom**: Uses a static value for all recipients

```json
{ "key": "course", "type": "custom", "value": "Data Science Bootcamp" }
```

This replaces `{{course}}` with "Data Science Bootcamp" for all recipients.

#### Placeholder Syntax

The service supports multiple placeholder formats:

- `{{name}}` - Standard curly braces
- `{#name#}` - Hash syntax
- `{{var}}`, `{{var_1}}`, `{{var_2}}` - Sequential variables

#### Fallback Behavior

If a placeholder isn't found in `dynamic_context`, the service falls back to:

1. Direct record field lookup (e.g., `{{email}}` → `record.email`)
2. Common field aliases:
    - `{{name}}` → `student_customer_name` → `student_name` → `name`
    - `{{email}}` → `email` → `student_email`
    - `{{phone}}` → `mobile` → `student_mobile` → `student_whatsapp` → `phone`
    - `{{mobile}}` → `mobile` → `student_mobile`

#### Example with Multiple Variables

```json
{
    "message": "Hi {{name}}, your {{course}} class on {{date}} at {{time}} is confirmed!",
    "dynamic_context": {
        "fields": [
            {
                "key": "name",
                "type": "attribute",
                "value": "student_customer_name"
            },
            {
                "key": "course",
                "type": "custom",
                "value": "Python Programming"
            },
            { "key": "date", "type": "attribute", "value": "class_date" },
            { "key": "time", "type": "custom", "value": "10:00 AM" }
        ]
    },
    "records": [
        {
            "id": "lead-123",
            "student_customer_name": "Anuj Kumar",
            "class_date": "March 15, 2026",
            "email": "anuj@example.com"
        }
    ]
}
```

Result: "Hi Anuj Kumar, your Python Programming class on March 15, 2026 at 10:00 AM is confirmed!"

### WhatsApp Meta Templates

WhatsApp campaigns can use Meta's Business API (WABA) templates, which require specific formatting and approval.

#### Template Types

1. **Text templates**: Simple text messages with variables
2. **Media templates**: Include header images, videos, or documents
3. **CTA templates**: Include call-to-action buttons
4. **Interactive templates**: Include quick reply buttons

#### Required Fields for Meta Templates

- **template_id**: References the `WabaTemplate` record (must be approved and category="marketing")
- **meta_template_id**: The Meta/WABA template identifier
- **dynamic_context**: Maps variables to record fields or custom values

#### Optional Fields Based on Template Type

- **header_text**: For text headers
- **media_url**: For image/video/document headers (must be publicly accessible)
- **media_filename**: Custom filename for document headers (optional, falls back to basename of media_url)
- **footer_text**: Footer text (if template supports it)
- **variables**: Array of sequential values for positional placeholders

#### Example: Text Template

```json
{
    "template_id": "wa_template_42",
    "meta_template_id": "course_reminder_v2",
    "message": "Hello {{1}}, your {{2}} class starts on {{3}}.",
    "variables": ["Anuj Kumar", "Python Programming", "March 15"]
}
```

#### Example: Media Template

```json
{
    "template_id": "wa_template_media_99",
    "meta_template_id": "brochure_template",
    "header_text": "Course Catalog 2026",
    "media_url": "https://cdn.example.com/brochure.pdf",
    "message": "Check out our new courses!",
    "dynamic_context": {
        "fields": [
            {
                "key": "name",
                "type": "attribute",
                "value": "student_customer_name"
            }
        ]
    }
}
```

#### Template Resolution Process

1. API receives `template_id` and looks up `WabaTemplate` in database
2. Service validates template is approved and category is "marketing"
3. Service extracts `template_body`, `header_type`, `header_value`, etc.
4. Service merges user-provided `message` with template body (user message takes precedence)
5. Service applies `dynamic_context` variable replacements
6. Service creates `WhatsappLog` entry with resolved message and metadata

#### Named Parameter Format (parameter_format = NAMED)

Templates with `parameter_format: "NAMED"` use named variables like `{{client_business_name}}` instead of positional ones like `{{1}}`. When such a template is synced from Meta, the system automatically detects the NAMED format from the stored `WabaTemplateComponent` record and includes `parameter_name` in body parameters sent to Meta's API.

**How it works:**

1. The stored `components_json` (from Meta's template sync) contains `parameter_format: "NAMED"` and `body_text_named_params` array with the order of parameter names.
2. The system reads `param_name` values from `body_text_named_params[].param_name` in order.
3. When building the Meta API request body, each parameter includes the `parameter_name` field.
4. No changes needed on your end — send `body_variables` as a flat array; the system maps them to the correct parameter names automatically.

**Example Meta API payload generated (internal):**

```json
{
    "messaging_product": "whatsapp",
    "recipient_type": "individual",
    "type": "template",
    "template": {
        "name": "pi_and_ti",
        "language": { "code": "en" },
        "components": [
            {
                "type": "header",
                "parameters": [
                    {
                        "type": "document",
                        "document": {
                            "link": "https://cdn.example.com/invoice.pdf",
                            "filename": "invoice.pdf"
                        }
                    }
                ]
            },
            {
                "type": "body",
                "parameters": [
                    {
                        "type": "text",
                        "parameter_name": "client_business_name",
                        "text": "Acme Solutions Inc."
                    },
                    {
                        "type": "text",
                        "parameter_name": "invoice_title",
                        "text": "testing"
                    },
                    {
                        "type": "text",
                        "parameter_name": "pi_number",
                        "text": "PI-1011-2026"
                    },
                    {
                        "type": "text",
                        "parameter_name": "total_amount",
                        "text": "INR 16,520.00"
                    },
                    {
                        "type": "text",
                        "parameter_name": "due_date",
                        "text": "15 Jul 2026"
                    }
                ]
            }
        ]
    }
}
```

**Sending body variables for a NAMED template via `/api/whatsapp/send`:**

Send `body_variables` as a flat array. Values are matched to parameter names in the order defined by `body_text_named_params`:

```json
{
    "accountid": "acct_123",
    "lead_mobile": "919000000000",
    "waba_template": "B54O6E",
    "body_variables": [
        "Acme Solutions Inc.",
        "testing",
        "PI-1011-2026",
        "INR 16,520.00",
        "15 Jul 2026"
    ],
    "media_url_manual": "https://cdn.example.com/invoice.pdf",
    "media_filename": "Proforma-Invoice-PI-1011-2026.pdf"
}
```

The system automatically includes `parameter_name` in each body parameter when `parameter_format` is NAMED. No additional configuration is required.

### Communication Group Persistence

Every successful campaign request creates a `CommunicationGroup` entry that persists the audience for future reference and reuse.

#### Stored Information

The `communication_groups` table stores:

- **groupid**: Unique identifier for the audience group
- **accountid**: Account that owns the group
- **group_name**: Set to the `campaign_name`
- **group_source**: Defaults to "api" (can be overridden with `group_source` parameter)
- **name**: First recipient's name (from first record)
- **email**: First recipient's email
- **phone**: First recipient's phone
- **raw_data**: JSON object containing:
    - `campaign_name`: Original campaign name
    - `group_name`: Group name
    - `platforms`: Array of platform configurations used
    - `records`: Complete array of all recipient records
    - `lead_count`: Total number of recipients
    - `userid`: User who created the campaign
    - `source_url`: Origin URL (if provided)
    - `notes`: Campaign notes (if provided)

#### Benefits

1. **Reusability**: The same audience can be used for future campaigns via the web UI
2. **Tracking**: Links campaigns to their source audiences via `communication_manager.groupid`
3. **Auditing**: Preserves the original record set for compliance and reporting
4. **Analytics**: Enables audience-based performance analysis

#### Example raw_data Structure

```json
{
    "campaign_name": "Email drip - Course launch",
    "group_name": "Email drip - Course launch",
    "platforms": [
        {
            "platform": "email",
            "template_id": "mail_template_42",
            "payload": {
                "subject": "Welcome!",
                "message": "<p>Hi {{name}}</p>"
            }
        }
    ],
    "records": [
        {
            "id": "lead-123",
            "email": "anuj@example.com",
            "student_customer_name": "Anuj Kumar",
            "mobile": "+919000000000"
        }
    ],
    "lead_count": 1,
    "userid": "user_456",
    "source_url": "https://partner.app/campaigns"
}
```

## Record Structure

Each record in the `records` array represents a single recipient and should contain contact information plus any custom fields needed for personalization.

### Required Fields

**For Email Campaigns:**

- `email` (string): Valid email address

**For SMS/WhatsApp Campaigns:**

- `mobile` OR `phone` (string): Phone number (E.164 format recommended, e.g., +919000000000)

### Recommended Fields

- `id` (string): Unique identifier (falls back to `leadid`, `regid`, or array index)
- `student_customer_name` OR `student_name` OR `name` (string): Recipient's name

### Optional Fields

These fields are stored in `CommunicationOutbox` and available for personalization:

- `parent_business_name` (string): Business/organization name
- `product_class` (string): Product category
- `sub_product_class` (string): Product subcategory
- `designation` (string): Job title or role
- `address` (string): Physical address
- `city` (string): City name
- `website` (string): Website URL
- `lead_type` (string): Lead classification
- `leadid` (string): CRM lead identifier
- `regid` (string): Registration identifier

### Custom Fields

You can include any additional fields in records - they will be:

1. Stored in the `communication_groups.raw_data`
2. Available for use in `dynamic_context` variable mapping
3. Accessible in the message templating system

### Example Record

```json
{
    "id": "lead-12345",
    "leadid": "CRM-12345",
    "email": "anuj.kumar@example.com",
    "mobile": "+919000000000",
    "student_customer_name": "Anuj Kumar",
    "parent_business_name": "Kumar Enterprises",
    "city": "Mumbai",
    "product_class": "Data Science",
    "sub_product_class": "Python Programming",
    "lead_type": "hot",
    "custom_field_1": "Value 1",
    "custom_field_2": "Value 2"
}
```

## Database Tables

### Campaign Flow

1. **communication_groups**: Stores the audience
2. **communication_manager**: Stores campaign metadata
3. **communication_outbox**: One entry per recipient
4. **mail_logs / sms_logs / whatsapp_logs**: One entry per recipient per platform

### communication_manager Fields

- `campaignid`: Unique campaign identifier
- `accountid`: Account owner
- `userid`: User who created the campaign
- `campaign`: Campaign name
- `groupid`: Links to communication_groups
- `subject`: Email subject (email campaigns)
- `templateid`: Template used
- `template_name`: Template name
- `send_email`: "yes" or "no"
- `send_sms`: "yes" or "no"
- `send_whatsapp`: "yes" or "no"
- `email_data`: JSON of email platform config
- `sms_data`: JSON of SMS platform config
- `whatsapp_data`: JSON of WhatsApp platform config
- `status`: "scheduled", "running", "completed", "paused"
- `dt`: Creation timestamp

### communication_outbox Fields

- `rowid`: Auto-increment primary key
- `campaignid`: Links to communication_manager
- `accountid`: Account owner
- `title`: Campaign name
- `student_customer_name`: Recipient name
- `email`: Recipient email
- `mobile`: Recipient phone
- `scheduled_dt`: When to send
- `status`: "pending", "sent", "failed"
- Plus all optional record fields (city, product_class, etc.)

### Platform-Specific Log Fields

**mail_logs:**

- `mail_log_id`: Unique identifier
- `email_subject`: Email subject
- `email_body`: Resolved HTML body
- `from_email`: Sender email
- `from_name`: Sender name

**sms_logs:**

- `sms_log_id`: Unique identifier
- `message`: Resolved SMS text
- `from_phone_number`: Sender phone/ID
- `meta_template_id`: Template identifier

**whatsapp_logs:**

- `whatsapp_log_id`: Unique identifier
- `message`: Resolved message text
- `from_phone_number`: Sender phone
- `meta_template_id`: WABA template identifier

## Campaign Execution

### API vs Web UI Execution

**API Campaigns (Instant Execution)**:

- Campaigns created via API endpoints are executed immediately
- Jobs are dispatched right after campaign creation
- Messages start sending within seconds
- Status changes from "scheduled" → "running" → "completed" automatically

**Web UI Campaigns (Scheduled Execution)**:

- Campaigns created via web UI follow the scheduled time
- Processed by Laravel console commands at scheduled intervals
- Allows for future scheduling and batch processing

### Scheduling

Campaigns are created with `status: "scheduled"` and entries in `communication_outbox` with `status: "pending"`.

For API campaigns, the following jobs are dispatched immediately:

- `SendScheduledCampaignEmails` - for email campaigns
- `SendScheduledCampaignSms` - for SMS campaigns
- `SendScheduledCampaignWhatsapps` - for WhatsApp campaigns

### Delivery

Laravel console commands process scheduled campaigns (primarily for web UI):

- `campaigns:send-scheduled-emails` (defined in `app/Console/Commands/`)
- `campaigns:send-scheduled-sms` (defined in `app/Console/Commands/`)
- `campaigns:send-scheduled-whatsapps` (defined in `app/Console/Commands/`)

These commands:

1. Query `communication_outbox` for pending entries where `scheduled_dt <= now()`
2. Query corresponding log tables (`mail_logs`, `sms_logs`, `whatsapp_logs`)
3. Send messages via respective services (`EmailService`, `SmsGatewayService`, `WhatsappService`)
4. Update log status to "sent" or "failed"
5. Update outbox status accordingly

### Jobs

The system uses Laravel jobs for actual sending:

- `SendScheduledCampaignEmails`
- `SendScheduledCampaignSms`
- `SendScheduledCampaignWhatsapps`

These jobs are dispatched by the console commands (for web UI) or immediately after API campaign creation (for API campaigns), and handle the actual API calls to email/SMS/WhatsApp providers.

## Integration Checklist

### Prerequisites

1. Valid `account_id` with active credits for the desired platform(s)
2. Approved templates (for template-based campaigns)
3. Clean, deduplicated recipient data
4. Valid phone numbers in E.164 format (for SMS/WhatsApp)
5. Valid email addresses (for email campaigns)

### Implementation Steps

1. **Authenticate**: Obtain valid `account_id`
2. **Prepare Audience**:
    - Collect recipient records
    - Validate contact information
    - Add unique `id` to each record
    - Include fields needed for personalization
3. **Configure Platform**:
    - Choose email, SMS, or WhatsApp endpoint
    - Provide `message` or `template_id`
    - Set up `dynamic_context` for variable replacement
    - Add platform-specific fields (subject, sender_id, etc.)
4. **Schedule Campaign**:
    - Set `schedule_at` datetime
    - Provide `campaign_name`
    - Include `source_url` for tracking
    - Add optional `notes`
5. **Make API Request**: POST to `/api/campaigns/schedule/{platform}`
6. **Store campaign_id**: Save returned `campaign_id` for tracking
7. **Monitor Delivery**: Query log tables using `campaign_id`

### Testing

1. **Test with single recipient** first
2. **Verify variable replacement** works correctly
3. **Check log entries** in database
4. **Confirm delivery** via email/SMS/WhatsApp
5. **Scale to full audience** after validation

## Advanced Features

### Multi-Platform Campaigns

While the single-platform endpoints (`/email`, `/sms`, `/whatsapp`) are recommended, you can schedule multi-platform campaigns by calling multiple endpoints with the same `campaign_name` and `records`, or by using the base `/api/campaigns/schedule` endpoint with multiple platforms in the `platforms` array.

### Template Inheritance

When using `template_id`:

- The service loads the template from the database
- User-provided fields override template defaults
- `message` overrides `template.body`
- `subject` overrides `template.subject`
- `sender_id` overrides `template.verified_number` (SMS)

### Credit Management

The system checks `communication_credits` table for available credits:

- Email campaigns require `credittype: "email"`
- SMS campaigns require `credittype: "sms"`
- WhatsApp campaigns require `credittype: "whatsapp"`

Credits are validated before campaign creation but deducted during actual delivery.

### Error Handling

The API returns detailed error messages for:

- Invalid account_id
- Missing required fields
- Invalid platform configuration
- Template not found
- No available credits
- Invalid record structure

Always check the `status` field in responses and handle errors appropriately.

## Payload Scalability

### Extensibility

The `platforms[]` array structure is designed for extensibility:

- New platforms can be added by extending the `match` expression in `CampaignSchedulingService::resolvePlatformPayload()`
- New metadata fields can be added to `payload` without breaking existing integrations
- The service automatically filters out empty/invalid configurations

### Future Enhancements

The architecture supports:

- Additional communication channels (RCS, IVR, Push notifications)
- Advanced scheduling rules (time zones, send windows)
- A/B testing variants
- Dynamic content blocks
- Conditional logic in templates

## Best Practices

### Data Quality

1. **Validate emails**: Use proper email validation before sending
2. **Format phone numbers**: Use E.164 format (+[country][number])
3. **Deduplicate**: Remove duplicate contacts before submission
4. **Segment wisely**: Target relevant audiences for better engagement

### Performance

1. **Batch size**: Keep records array under 1000 entries per request
2. **Rate limiting**: Implement exponential backoff for retries
3. **Async processing**: Don't wait for delivery confirmation
4. **Monitor logs**: Query log tables periodically for status updates

### Security

1. **Protect account_id**: Never expose in client-side code
2. **Validate input**: Sanitize all user-provided data
3. **Use HTTPS**: Always use secure connections
4. **Audit trails**: Log all API calls with `source_url`

### Compliance

1. **Opt-out handling**: Respect unsubscribe requests
2. **GDPR compliance**: Include data processing agreements
3. **CAN-SPAM**: Include physical address and unsubscribe link (email)
4. **TCPA**: Obtain consent before SMS/WhatsApp (US)

## Troubleshooting

### Common Issues

**"Invalid account_id provided"**

- Verify account exists in database
- Check if `accountid` needs base64 decoding
- Ensure account is active

**"Email payload could not be built"**

- Provide either `message` or `template_id`
- Check template exists and belongs to account
- Verify template has content

**"No valid platform configurations provided"**

- Ensure `platforms` array is not empty
- Check platform name matches endpoint (email/sms/whatsapp)
- Verify payload has required fields

**"Template not found"**

- Confirm `template_id` exists in database
- Check template belongs to correct account
- Verify template is not deleted

**Messages not sending**

- Check `scheduled_dt` is in the future or past
- Verify console commands are running (cron jobs)
- Check queue workers are active
- Review log tables for error messages

### Debug Steps

1. Check `communication_manager` table for campaign entry
2. Check `communication_groups` table for audience
3. Check `communication_outbox` table for recipient entries
4. Check platform-specific log tables for delivery status
5. Review Laravel logs for exceptions
6. Verify cron jobs are running scheduled commands

## Support

For additional assistance:

- Review Laravel logs: `storage/logs/laravel.log`
- Check database tables directly
- Enable debug mode for detailed error messages
- Contact technical support with `campaign_id` for specific issues

## Database Schema

### Campaign Flow Tables

1. **communication_groups**: Stores the audience
2. **communication_manager**: Stores campaign metadata
3. **communication_outbox**: One entry per recipient
4. **mail_logs / sms_logs / whatsapp_logs**: One entry per recipient per platform

### communication_manager Fields

| Field               | Type     | Description                                                                       |
| ------------------- | -------- | --------------------------------------------------------------------------------- |
| `campaignid`        | string   | Unique campaign identifier                                                        |
| `accountid`         | string   | Account owner                                                                     |
| `userid`            | string   | User who created the campaign                                                     |
| `campaign`          | string   | Campaign name                                                                     |
| `groupid`           | string   | Links to communication_groups                                                     |
| `subject`           | string   | Email subject (email campaigns)                                                   |
| `from_name`         | string   | Sender name                                                                       |
| `from_email`        | string   | Sender email                                                                      |
| `reply_to_name`     | string   | Reply-to name                                                                     |
| `reply_to_email`    | string   | Reply-to email                                                                    |
| `templateid`        | string   | Template used                                                                     |
| `template_name`     | string   | Template name                                                                     |
| `send_email`        | enum     | "yes" or "no"                                                                     |
| `send_sms`          | enum     | "yes" or "no"                                                                     |
| `send_whatsapp`     | enum     | "yes" or "no"                                                                     |
| `email_data`        | json     | Email platform config (includes subject, message, attachments, attachment_docids) |
| `sms_data`          | json     | SMS platform config                                                               |
| `whatsapp_data`     | json     | WhatsApp platform config                                                          |
| `email_attachments` | json     | Array of Docmanager document IDs for email attachments                            |
| `campaign_payload`  | json     | Raw campaign payload for draft persistence                                        |
| `status`            | enum     | "scheduled", "running", "completed", "paused"                                     |
| `dt`                | datetime | Creation timestamp                                                                |

### communication_outbox Fields

| Field                                                       | Type     | Description                    |
| ----------------------------------------------------------- | -------- | ------------------------------ |
| `rowid`                                                     | int      | Auto-increment primary key     |
| `campaignid`                                                | string   | Links to communication_manager |
| `accountid`                                                 | string   | Account owner                  |
| `title`                                                     | string   | Campaign name                  |
| `student_customer_name`                                     | string   | Recipient name                 |
| `email`                                                     | string   | Recipient email                |
| `mobile`                                                    | string   | Recipient phone                |
| `scheduled_dt`                                              | datetime | When to send                   |
| `status`                                                    | enum     | "pending", "sent", "failed"    |
| Plus all optional record fields (city, product_class, etc.) |

### Platform-Specific Log Fields

**mail_logs:**

- `mail_log_id`: Unique identifier
- `email_subject`: Email subject
- `email_body`: Resolved HTML body
- `from_email`: Sender email
- `from_name`: Sender name
- `sent_to`: Recipient email
- `status`: "pending", "sent", "failed"
- `credits_used`: Credits consumed

**sms_logs:**

- `sms_log_id`: Unique identifier
- `message`: Resolved SMS text
- `from_phone_number`: Sender phone/ID
- `sent_to`: Recipient phone
- `meta_template_id`: Template identifier
- `status`: "pending", "sent", "failed"
- `credits_used`: Credits consumed

**whatsapp_logs:**

- `whatsapp_log_id`: Unique identifier
- `message`: Resolved message text
- `from_phone_number`: Sender phone
- `sent_to`: Recipient phone
- `meta_template_id`: WABA template identifier
- `status`: "pending", "sent", "failed"
- `credits_used`: Credits consumed

---

## Campaign Execution

### Scheduling

Campaigns are created with `status: "scheduled"` and entries in `communication_outbox` with `status: "pending"`.

### Delivery

Laravel console commands process scheduled campaigns:

- `campaigns:send-scheduled-emails`
- `campaigns:send-scheduled-sms`
- `campaigns:send-scheduled-whatsapps`

These commands:

1. Query `communication_outbox` for pending entries where `scheduled_dt <= now()`
2. Query corresponding log tables (`mail_logs`, `sms_logs`, `whatsapp_logs`)
3. Send messages via respective services (`EmailService`, `SmsGatewayService`, `WhatsappService`)
4. Update log status to "sent" or "failed"
5. Update outbox status accordingly

### Jobs

The system uses Laravel jobs for actual sending:

- `SendScheduledCampaignEmails`
- `SendScheduledCampaignSms`
- `SendScheduledCampaignWhatsapps`

These jobs are dispatched by the console commands and handle the actual API calls to email/SMS/WhatsApp providers.

---

## Error Handling

### Validation Errors

The API performs comprehensive validation before processing:

```json
{
    "status": "error",
    "message": "The given data was invalid.",
    "errors": {
        "account_id": ["The account id field is required."],
        "campaign_name": ["The campaign name field is required."],
        "schedule_at": ["The schedule at field must be a valid date."],
        "records": ["The records field must contain at least 1 items."],
        "records.0.email": [
            "The records.0.email field is required when platform is email."
        ]
    }
}
```

### Common Error Scenarios

#### 1. Invalid Account

**Error**: `Invalid account_id provided.`
**Cause**: Account doesn't exist or is inactive
**Solution**: Verify account_id is correct

#### 2. No Message or Template

**Error**: `Email payload could not be built.`
**Cause**: Neither `message` nor `template_id` provided
**Solution**: Provide at least one

#### 3. Template Not Found

**Cause**: `template_id` doesn't exist or doesn't belong to account
**Solution**: Use valid template ID or provide message directly

#### 4. Missing Contact Information

**Cause**: Records missing required fields (email for email, mobile for SMS/WhatsApp)
**Solution**: Ensure all records have required contact fields

#### 5. Invalid Schedule Date

**Error**: `The schedule at field must be a valid date.`
**Cause**: Invalid datetime format
**Solution**: Use ISO 8601 format: `2026-02-12T10:00:00Z`

### Logging

The API logs every step of campaign creation:

- Request payload
- Validation results
- User resolution
- Platform configuration
- Record processing
- Group creation
- Campio creation
- Log creation

Check `storage/logs/laravel.log` for detailed information.

---

## Testing Guide

### Prerequisites

1. Valid `account_id` with active credits
2. Approved templates (for template-based campaigns)
3. Clean, deduplicated recipient data
4. Valid phone numbers in E.164 format (for SMS/WhatsApp)
5. Valid email addresses (for email campaigns)

### Test Email Campaign

```bash
curl -X POST http://your-domain.com/api/campaigns/schedule/email \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "YOUR_ACCOUNT_ID",
    "campaign_name": "Test Email Campaign",
    "schedule_at": "2026-02-12T10:00:00Z",
    "subject": "Test Subject",
    "message": "<p>Hello {{name}}, this is a test email.</p>",
    "dynamic_context": {
      "fields": [
        {"key": "name", "type": "attribute", "value": "student_customer_name"}
      ]
    },
    "records": [
      {
        "id": "test-001",
        "email": "test@example.com",
        "student_customer_name": "Test User"
      }
    ],
    "source_url": "https://test.app/campaigns"
  }'
```

### Test SMS Campaign

```bash
curl -X POST http://your-domain.com/api/campaigns/schedule/sms \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "YOUR_ACCOUNT_ID",
    "campaign_name": "Test SMS Campaign",
    "schedule_at": "2026-02-12T11:00:00Z",
    "message": "Hi {{name}}, this is a test SMS.",
    "dynamic_context": {
      "fields": [
        {"key": "name", "type": "attribute", "value": "student_customer_name"}
      ]
    },
    "records": [
      {
        "id": "test-002",
        "mobile": "+919000000000",
        "student_customer_name": "Test User"
      }
    ],
    "source_url": "https://test.app/campaigns"
  }'
```

### Test WhatsApp Campaign

```bash
curl -X POST http://your-domain.com/api/campaigns/schedule/whatsapp \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "YOUR_ACCOUNT_ID",
    "campaign_name": "Test WhatsApp Campaign",
    "schedule_at": "2026-02-12T12:00:00Z",
    "message": "Hello {{name}}, this is a test WhatsApp message.",
    "template_id": "YOUR_WHATSAPP_TEMPLATE_ID",
    "dynamic_context": {
      "fields": [
        {"key": "name", "type": "attribute", "value": "student_customer_name"}
      ]
    },
    "records": [
      {
        "id": "test-003",
        "mobile": "+919000000000",
        "student_customer_name": "Test User"
      }
    ],
    "source_url": "https://test.app/campaigns"
  }'
```

### Test Get Credits API

```bash
curl -X GET "http://your-domain.com/api/credits?account_id=YOUR_ACCOUNT_ID" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "account_id": "YOUR_ACCOUNT_ID",
        "credits": {
            "email": 1000,
            "sms": 500,
            "whatsapp": 250
        },
        "total": 1750
    }
}
```

### Test Get Email Templates API

```bash
curl -X GET "http://your-domain.com/api/templates/email?account_id=YOUR_ACCOUNT_ID" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "account_id": "YOUR_ACCOUNT_ID",
        "templates": [
            {
                "id": "TEMPLATE_ID_1",
                "name": "Welcome Email",
                "subject": "Welcome to our platform",
                "body": "<p>Hello {{name}}, welcome!</p>",
                "exported_body": "<html>...</html>",
                "editor_type": "drag",
                "created_at": "2026-01-15 10:30:00"
            }
        ],
        "count": 1
    }
}
```

### Test Get SMS Templates API

```bash
curl -X GET "http://your-domain.com/api/templates/sms?account_id=YOUR_ACCOUNT_ID" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "account_id": "YOUR_ACCOUNT_ID",
        "templates": [
            {
                "id": "SMS_TEMPLATE_ID_1",
                "name": "Welcome SMS",
                "body": "Hi {{name}}, welcome to our service!",
                "sender_id": "SENDER",
                "created_at": "2026-01-15 10:30:00"
            }
        ],
        "count": 1
    }
}
```

### Test Get WhatsApp Templates API

```bash
curl -X GET "http://your-domain.com/api/templates/whatsapp?account_id=YOUR_ACCOUNT_ID" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "status": "success",
    "data": {
        "account_id": "YOUR_ACCOUNT_ID",
        "templates": [
            {
                "id": "WA_TEMPLATE_ID_1",
                "name": "greeting",
                "language": "en_US",
                "category": "MARKETING",
                "status": "APPROVED",
                "header_type": "IMAGE",
                "header_value": "https://example.com/image.png",
                "header_variable": 0,
                "body_variable": 1,
                "template_body": "Hello {{1}}, welcome!",
                "meta_template_id": "1234567890",
                "created_at": "2026-01-15 10:30:00"
            }
        ],
        "count": 1
    }
}
```

### Verify Campaign Creation

```sql
-- Check communication_manager
SELECT campaignid, campaign, groupid, status, send_email, send_sms, send_whatsapp
FROM communication_manager
WHERE campaign LIKE 'Test%'
ORDER BY dt DESC
LIMIT 5;

-- Check communication_groups
SELECT groupid, group_name, source, raw_data
FROM communication_groups
WHERE group_name LIKE 'Test%'
ORDER BY groupid DESC
LIMIT 5;

-- Check communication_outbox
SELECT rowid, campaignid, student_customer_name, email, mobile, status
FROM communication_outbox
WHERE title LIKE 'Test%'
ORDER BY dt DESC
LIMIT 5;

-- Check platform logs
SELECT mail_log_id, campaignid, sent_to, email_subject, status
FROM mail_logs
WHERE title LIKE 'Test%'
ORDER BY mail_log_id DESC
LIMIT 5;
```

---

## Troubleshooting

### Issue: "0 contacts" showing in campaign list

**Cause**: The `raw_data` field in `communication_groups` wasn't being decoded properly.

**Fix**: Updated `CampaignController::index()` to decode JSON before reading `lead_count`.

**Verify**: Check the `raw_data` field in `communication_groups` table - it should contain a JSON string with `lead_count` field.

```sql
SELECT groupid, group_name, raw_data
FROM communication_groups
WHERE groupid = 'YOUR_GROUP_ID';
```

### Issue: Records not being processed

**Cause**: Records might not have required fields (email for email campaigns, mobile for SMS/WhatsApp).

**Check logs for**: "Skipping log - no recipient" warnings

**Fix**: Ensure records have the correct contact fields for the platform.

### Issue: Template not found

**Cause**: `template_id` doesn't exist or doesn't belong to the account.

**Check logs for**: Platform config errors

**Fix**: Use valid template IDs or provide `message` directly.

### Issue: Class "App\Http\Controllers\Api\Log" not found

**Cause**: Missing `Log` facade import.

**Fix**: Already fixed - `use Illuminate\Support\Facades\Log;` added to controller.

### Debugging Steps

1. **Check Laravel logs**:

    ```bash
    tail -f storage/logs/laravel.log
    ```

2. **Verify account exists**:

    ```sql
    SELECT * FROM accounts WHERE accountid = 'YOUR_ACCOUNT_ID';
    ```

3. **Check credits**:

    ```sql
    SELECT * FROM communication_credits
    WHERE accountid = 'YOUR_ACCOUNT_ID'
    AND status = 'active';
    ```

4. **Verify templates**:

    ```sql
    -- Email templates
    SELECT * FROM mail_templates
    WHERE accountid = 'YOUR_ACCOUNT_ID'
    AND deleted_at IS NULL;

    -- SMS templates
    SELECT * FROM sms_templates
    WHERE accountid = 'YOUR_ACCOUNT_ID';

    -- WhatsApp templates
    SELECT * FROM waba_templates
    WHERE accountid = 'YOUR_ACCOUNT_ID'
    AND LOWER(status) = 'approved'
    AND LOWER(category) = 'marketing';
    ```

---

## Best Practices

### Data Quality

1. **Validate emails**: Use proper email validation before sending
2. **Format phone numbers**: Use E.164 format (+[country][number])
3. **Deduplicate**: Remove duplicate contacts before submission
4. **Segment wisely**: Target relevant audiences for better engagement

### Performance

1. **Batch size**: Keep records array under 1000 entries per request
2. **Rate limiting**: Implement exponential backoff for retries
3. **Async processing**: Don't wait for delivery confirmation
4. **Monitor logs**: Query log tables periodically for status updates

### Security

1. **Protect account_id**: Never expose in client-side code
2. **Validate input**: Sanitize all user-provided data
3. **Use HTTPS**: Always use secure connections
4. **Audit trails**: Log all API calls with `source_url`

### Compliance

1. **Opt-out handling**: Respect unsubscribe requests
2. **GDPR compliance**: Include data processing agreements
3. **CAN-SPAM**: Include physical address and unsubscribe link (email)
4. **TCPA**: Obtain consent before SMS/WhatsApp (US)

---

## Integration Checklist

- [ ] Obtain valid `account_id`
- [ ] Verify account has active credits for desired platform(s)
- [ ] Create or identify templates to use
- [ ] Prepare recipient data with required fields
- [ ] Format phone numbers in E.164 format
- [ ] Set up `dynamic_context` for personalization
- [ ] Choose appropriate `schedule_at` datetime
- [ ] Include `source_url` for tracking
- [ ] Test with single recipient first
- [ ] Verify campaign creation in database
- [ ] Check logs for any errors
- [ ] Monitor delivery status
- [ ] Scale to full audience after validation

---

## Support

For additional assistance:

- Review Laravel logs: `storage/logs/laravel.log`
- Check database tables directly
- Enable debug mode for detailed error messages
- Contact technical support with `campaign_id` for specific issues

---

## Changelog

### 2026-07-06

- **NEW**: `POST /api/communication-log` endpoint — returns full communication details (subject, body, recipient, attachments) for timeline "View" modal
- **NEW**: Email attachment support via `attachment_docids` (Docmanager document IDs) and `attachments` (direct URLs) in platform payloads
- **NEW**: `email_attachments` field added to `communication_manager` table — stores Docmanager document IDs for email attachments
- **NEW**: Documented quick-send endpoints: `POST /api/mail/send`, `POST /api/sms/send`, `POST /api/whatsapp/send`
- **FIX**: Campaign API now properly persists `attachment_docids` to `email_attachments` column for timeline log resolution

### 2026-02-11 (Latest)

- **CRITICAL FIX**: Fixed email subject missing in scheduled campaigns
    - Root cause: Laravel validation was stripping `subject` and other payload fields
    - Solution: Added validation rules for all `platforms.*.payload.*` fields
    - Impact: Email campaigns now properly save and send with correct subject lines
- Added comprehensive debug logging to WhatsApp API service
- Added comprehensive debug logging to SMS API service
- Enhanced logging includes: incoming requests, validation, template resolution, API responses

### 2026-02-11 (Earlier)

- Fixed "0 contacts" bug in campaign list
- Added comprehensive logging throughout API flow
- Fixed missing `Log` facade import
- Updated documentation with complete examples
- Added troubleshooting guide

### Initial Release

- Unified web and API campaign scheduling
- Multi-channel support (Email, SMS, WhatsApp)
- Dynamic variable system
- Template management
- Audience persistence

---

## LeadTimeline Integration

### Overview

All communications sent via email, SMS, and WhatsApp (through campaigns or direct API) automatically create entries in the `lead_timeline` table when a valid lead exists.

### Recent Fixes (February 2026)

**Issue 1: Wrong User Name in `entry_by` Field**

- **Problem:** SMS and WhatsApp services were using wrong User model, showing userid instead of user name
- **Fix:** Changed to use `AccountUser` model to fetch correct user name from `account_users` table
- **Files Modified:** `app/Services/SmsGatewayService.php`, `app/Services/WhatsappService.php`

**Issue 2: SMS and WhatsApp Not Creating Timeline Entries**

- **Problem:** Timeline entries only created when `leadid` parameter is provided
- **Solution:** Ensure you pass `leadid` and `accountid` parameters in API requests

### Requirements for Timeline Creation

Timeline entries are ONLY created if:

- ✅ `leadid` parameter is provided (or `lead_id` for email)
- ✅ `accountid` parameter is provided (or `account_id` for email)
- ✅ A matching lead exists in the `leads` table
- ✅ The lead belongs to the specified account

### API Parameters for Timeline

**Email API (`/api/mail/send`):**

```json
{
  "account_id": "ACC123",
  "lead_id": "LEAD456",
  "userid": "USER789",
  ...
}
```

**SMS API (`/api/sms/send`):**

```json
{
  "accountid": "ACC123",
  "leadid": "LEAD456",
  "userid": "USER789",
  ...
}
```

**WhatsApp API (`/api/whatsapp/send`):**

```json
{
  "accountid": "ACC123",
  "leadid": "LEAD456",
  "userid": "USER789",
  ...
}
```

**Campaign APIs:**

```json
{
  "account_id": "ACC123",
  "records": [{
    "leadid": "LEAD456",
    ...
  }]
}
```

### Timeline Entry Details

**Email:**

- `entry_by` = User's name (from `account_users` table)
- `activity` = "Manual Entry"
- `communication_type` = NULL
- `note` = "Email Sent | Source: API" (or "Source: CAMPAIGN")

**SMS:**

- `entry_by` = User's name (from `account_users` table)
- `activity` = "SMS"
- `communication_type` = "sms"
- `note` = "SMS sent" or "SMS failed"

**WhatsApp:**

- `entry_by` = User's name (from `account_users` table)
- `activity` = "WhatsApp template"
- `communication_type` = "template"
- `note` = "WhatsApp template 'template_name' sent" or "failed"

### Verification

Check timeline entries:

```sql
SELECT
    tlid,
    leadid,
    accountid,
    entry_by,
    activity,
    communication_type,
    note,
    dt
FROM lead_timeline
WHERE leadid = 'YOUR_LEAD_ID'
ORDER BY dt DESC
LIMIT 5;
```

Check communication logs:

```sql
-- Email
SELECT * FROM mail_logs WHERE leadid = 'YOUR_LEAD_ID' ORDER BY created_at DESC LIMIT 1;

-- SMS
SELECT * FROM sms_logs WHERE leadid = 'YOUR_LEAD_ID' ORDER BY created_at DESC LIMIT 1;

-- WhatsApp
SELECT * FROM whatsapp_logs WHERE leadid = 'YOUR_LEAD_ID' ORDER BY created_at DESC LIMIT 1;
```

### Troubleshooting

**Timeline Entry Not Created:**

1. **Check if lead exists:**

```sql
SELECT * FROM leads WHERE leadid = 'YOUR_LEAD_ID' AND accountid = 'YOUR_ACCOUNT_ID';
```

2. **Verify parameters provided:**
    - Email: `lead_id` and `account_id`
    - SMS: `leadid` and `accountid`
    - WhatsApp: `leadid` and `accountid`

3. **Check communication logs:**
    - If communication was sent but no timeline entry, lead might not exist
    - Check the respective log table (mail_logs, sms_logs, whatsapp_logs)

**Wrong Entry Name:**

1. **Check if user exists:**

```sql
SELECT userid, name FROM account_users WHERE userid = 'YOUR_USER_ID';
```

2. **Ensure userid parameter is provided:**
    - Pass `userid` in the API request
    - The userid should match a user in `account_users` table

3. **Verify user belongs to account:**

```sql
SELECT userid, accountid, name
FROM account_users
WHERE userid = 'YOUR_USER_ID'
AND accountid = 'YOUR_ACCOUNT_ID';
```

### Database Tables

**lead_timeline** (Connection: mysql3)

- `tlid` - Timeline ID (unique identifier)
- `mlid` - Master Lead ID
- `leadid` - Lead ID (foreign key to leads table)
- `accountid` - Account ID
- `entry_by` - User name who created the entry
- `activity` - Activity type
- `communication_type` - Type of communication
- `note` - Descriptive note
- `dt` - Timestamp

**Related Tables:**

- `communication_log` - Logs all communications with credit tracking
- `mail_logs` - Detailed email sending logs
- `sms_logs` - Detailed SMS sending logs
- `whatsapp_logs` - Detailed WhatsApp sending logs

**Viewing Communication Details:**

The CRM calls `POST /api/communication-log` with `account_id` and `tlid` to fetch full communication details (subject, body, recipient, attachments) for the timeline "View" modal. See [Section 8](#8-communication-log) for API details.

---

### Debugging Timeline Creation

Comprehensive logging has been added to track timeline creation. Check these log files:

**SMS Logs:**

```bash
tail -f storage/logs/sms_api.log
```

**WhatsApp Logs:**

```bash
tail -f storage/logs/whatsapp_api.log
```

**Key Log Events:**

1. **timeline_lead_ids_extracted** - Shows what lead IDs were found in the payload

    ```json
    {
        "timelineLeadIds": ["LEAD456"],
        "payload_leadid": "LEAD456",
        "payload_lid": "NOT_SET",
        "accountid": "ACC123"
    }
    ```

2. **timeline_skipped_no_lead_ids** - Timeline creation skipped (no leadid provided)

    ```json
    {
        "timelineLeadIds": [],
        "payload_leadid": "NOT_SET",
        "accountid": "ACC123"
    }
    ```

3. **fetching_timeline_rows** - Attempting to fetch lead data

    ```json
    {
        "timelineLeadIds": ["LEAD456"],
        "accountId": "ACC123"
    }
    ```

4. **timeline_rows_fetched** - Lead data retrieved from database

    ```json
    {
      "count": 1,
      "rows": [{"leadid": "LEAD456", "mlid": "ML123", ...}]
    }
    ```

5. **timeline_row_skipped_no_leadid** - Row skipped (no leadid in result)

    ```json
    {
      "row": {...}
    }
    ```

6. **creating_timeline_entry** - About to create timeline entry

    ```json
    {
        "leadid": "LEAD456",
        "accountid": "ACC123",
        "entry_by_userid": "USER789",
        "entry_by_name": "John Doe"
    }
    ```

7. **timeline_entry_created** - Timeline entry successfully created
    ```json
    {
        "tlid": "TL123456",
        "leadid": "LEAD456",
        "accountid": "ACC123"
    }
    ```

**Common Issues:**

- **Empty timelineLeadIds**: You didn't pass `leadid` parameter
- **timeline_rows_fetched count: 0**: Lead doesn't exist in database
- **timeline_row_skipped_no_leadid**: Database query returned empty leadid
- **No timeline_entry_created log**: Check if lead exists and belongs to the account

---

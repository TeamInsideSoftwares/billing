# Campio Invoice Channel Integration

This document explains how invoice compose send now integrates with Campio for:

- Email
- WhatsApp
- SMS

Implementation file:

- `app/Http/Controllers/InvoicesController.php`

## 1. What was implemented

When user clicks **Send** in invoice compose:

1. Validate compose input (`channel`, `attachment_type`, message fields).
2. Resolve/create channel+type draft row in `invoice_emails`.
3. Build channel-specific Campio payload.
4. Call Campio endpoint:
   - `/api/campaigns/schedule/email`
   - `/api/campaigns/schedule/whatsapp`
   - `/api/campaigns/schedule/sms`
5. On Campio success:
   - mark local row as `sent`
   - set `sent_at`
   - append Campio `campaign_id` into body for traceability
6. On Campio failure:
   - no sent status update
   - show error back in compose page

## 2. Tenant/SaaS Account Mapping

No per-tenant Campio settings are required for account mapping.

- `account_id` sent to Campio is taken directly from current tenant account context:
  - `currentAccountId` from invoice/account auth flow.

This is SaaS-safe: each send automatically uses the tenant's own `accountid`.

## 3. Global Runtime Config

Global env keys (shared runtime config):

- `CAMPIO_BASE_URL` (default fallback used: `http://alpha.skoolready.com/campio`)
- `CAMPIO_AUTH_TOKEN` (optional, bearer token)
- `CAMPIO_API_KEY` (optional, sent as `X-API-KEY`)

## 4. Endpoint Mapping

- Email send -> `POST {CAMPIO_BASE_URL}/api/campaigns/schedule/email`
- WhatsApp send -> `POST {CAMPIO_BASE_URL}/api/campaigns/schedule/whatsapp`
- SMS send -> `POST {CAMPIO_BASE_URL}/api/campaigns/schedule/sms`

All calls are JSON with timeout 30s.

## 5. Payload Mapping

Common fields:

- `account_id`: from current tenant `accountid`
- `campaign_name`: `""` (system message mode)
- `schedule_at`: current ISO timestamp (`now()->toIso8601String()`)
- `records`: single recipient array built from invoice/client
- `source_url`: current compose URL
- `notes`: invoice channel marker

Email payload:

- `subject`: compose subject
- `message`: compose body
- `records[0].email`: client billing email

WhatsApp/SMS payload:

- `message`: plain text body
- `records[0].mobile` and `records[0].phone`: resolved client phone

For WhatsApp, message body also appends selected invoice document URLs (PI/TI/custom attachment) under `Documents:`.

## 6. Recipient Record Fields

`records[0]` includes:

- `id`
- `name`
- `invoice_number`
- `pi_number`
- `ti_number`
- `invoice_title`
- `due_date`
- `amount`
- `email` (email channel)
- `mobile` + `phone` (sms/whatsapp channels)

## 7. Error Handling

The flow returns compose errors when:

- `CAMPIO_BASE_URL` missing/invalid
- email channel is selected but billing emails are not configured
- sms/whatsapp selected but phone is missing
- Campio request throws exception
- Campio returns non-2xx response

On failure, no local sent-status update is performed.

## 8. Local DB Behavior

- Viewing compose does not auto-save.
- Save/Send uses row key by `(invoiceid, accountid, attachment_type, channel)`.
- Max rows per invoice remains 6 (`pi/ti * email/whatsapp/sms`).

## 9. Testing Checklist

1. Set global Campio runtime config (`CAMPIO_BASE_URL`, optional auth envs).
2. Open invoice compose.
3. Send Email:
   - should show success
   - row status becomes `sent`
4. Send WhatsApp:
   - should show success
   - payload should include message + document links
5. Send SMS:
   - should show success
6. Break `CAMPIO_BASE_URL` intentionally:
   - should show validation error in compose
   - no sent status update

## 10. Important Notes

- This integration uses Campio campaign scheduling APIs as transactional/system messages.
- If you want strict campaign-id persistence, add a dedicated DB column (e.g. `provider_campaign_id`) in `invoice_emails`.
- Current implementation appends campaign id text into message body for audit trail.

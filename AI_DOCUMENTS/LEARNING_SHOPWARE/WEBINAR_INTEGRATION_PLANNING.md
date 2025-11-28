# GoTo Webinar Integration - Planning Documentation

**Project Type:** Shopware App (Third-party API Integration)  
**Complexity:** Intermediate  
**Timeline:** 3-4 days for MVP/POC

---

## 1. Business Requirements

### Overview
Enable customers to purchase webinar tickets through Shopware shop. After successful checkout, automatically register the customer for the webinar via GoTo Webinar API.

### User Stories

**As a customer, I want to:**
- Browse available webinars in the shop
- Add a webinar ticket to my cart
- Complete checkout like any other product
- Receive automatic registration confirmation
- Get webinar access details via email

**As a shop administrator, I want to:**
- Sync webinars from GoTo Webinar to Shopware products
- Manage webinar product details
- Track registrations and attendance
- Handle registration errors gracefully
- View registration analytics

### Success Criteria
- ✅ Webinars are sold as virtual products
- ✅ Customer data is sent to GoTo Webinar after successful payment
- ✅ Registration confirmation is sent to customer
- ✅ Failed registrations are logged and retryable
- ✅ Admin can view registration status

---

## 2. Technical Architecture

### Architecture Decision: Plugin vs App

**Why Shopware App (Not Plugin)?**

GoTo Webinar integration is a **third-party service integration** which makes it an ideal candidate for a Shopware App:

✅ **App Advantages:**
- **Isolated architecture** - Runs independently, easier to maintain
- **OAuth flow built-in** - Shopware handles OAuth for GoTo Webinar
- **Webhook support** - Can receive updates from GoTo Webinar
- **Update safety** - No core file access, safer updates
- **Cloud-ready** - Can be hosted separately
- **Marketplace ready** - Easier to distribute

❌ **Plugin Disadvantages for this use case:**
- Requires core access (unnecessary for API integration)
- More complex OAuth implementation
- Tightly coupled to Shopware version
- Harder to distribute/sell

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  SHOPWARE STOREFRONT                                        │
├─────────────────────────────────────────────────────────────┤
│  1. Customer browses webinar products                       │
│  2. Adds to cart                                            │
│  3. Completes checkout                                      │
│  4. Payment confirmed                                       │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│  SHOPWARE APP (Our Integration)                             │
├─────────────────────────────────────────────────────────────┤
│  5. Receives order.placed webhook                           │
│  6. Extracts customer + webinar data                        │
│  7. Calls GoTo Webinar API                                  │
│  8. Stores registration status                              │
│  9. Sends confirmation email                                │
└─────────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│  GOTO WEBINAR API                                           │
├─────────────────────────────────────────────────────────────┤
│  10. Creates registrant                                     │
│  11. Returns join URL + confirmation                        │
│  12. (Optional) Sends GoTo's own confirmation email         │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

**Happy Path:**
1. **Order Placed** → Webhook triggers
2. **Validate Order** → Check if contains webinar products
3. **Call GoTo API** → Register customer for webinar
4. **Store Registration** → Save join URL, registrant ID
5. **Send Email** → Custom confirmation with join link
6. **Update Order** → Add custom field with webinar details

**Error Handling:**
- **API Down** → Retry mechanism with exponential backoff
- **Invalid Data** → Log error, notify admin
- **Duplicate Registration** → Update existing registration
- **Partial Failure** → Process successful registrations, flag failed ones

---

## 3. GoTo Webinar API Integration

### Authentication Flow (OAuth 2.0)

```
1. User clicks "Connect GoTo Webinar" in Shopware Admin
2. Shopware redirects to GoTo OAuth page
3. User authorizes app
4. GoTo redirects back with authorization code
5. App exchanges code for access token + refresh token
6. Store tokens securely in app configuration
7. Use access token for API calls
8. Refresh token when expired
```

### Required API Endpoints

**1. Get Webinars** (`GET /organizers/{organizerKey}/webinars`)
- List all upcoming webinars
- Used for syncing webinars to products

**2. Create Registrant** (`POST /organizers/{organizerKey}/webinars/{webinarKey}/registrants`)
- Register customer for webinar
- Returns join URL and registrant key

**3. Get Registrant** (`GET /organizers/{organizerKey}/webinars/{webinarKey}/registrants/{registrantKey}`)
- Check registration status
- Used for sync/verification

### API Request Example

```http
POST https://api.getgo.com/G2W/rest/v2/organizers/{organizerKey}/webinars/{webinarKey}/registrants
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "firstName": "Max",
  "lastName": "Mustermann",
  "email": "max@example.com",
  "responses": [
    {
      "questionKey": 12345,
      "responseText": "Shopware Developer"
    }
  ]
}
```

### API Response Example

```json
{
  "registrantKey": "5678901234567890123",
  "joinUrl": "https://attendee.gotowebinar.com/register/123456789",
  "confirmationUrl": "https://www.gotomeeting.com/webinar/confirm/123456789"
}
```

---

## 4. Database Schema

### Custom Fields on Order Line Items

Instead of new tables, use Shopware's custom fields:

**order_line_item.customFields:**
```json
{
  "webinar_registration": {
    "webinarKey": "1234567890",
    "registrantKey": "5678901234567890123",
    "joinUrl": "https://attendee.gotowebinar.com/...",
    "registrationStatus": "confirmed",
    "registeredAt": "2025-11-28T10:30:00Z",
    "error": null
  }
}
```

**product.customFields:**
```json
{
  "webinar_details": {
    "webinarKey": "1234567890",
    "organizerKey": "9876543210",
    "webinarType": "single_session",
    "startTime": "2025-12-15T18:00:00Z",
    "endTime": "2025-12-15T20:00:00Z",
    "timezone": "Europe/Berlin",
    "maxAttendees": 500,
    "registrationUrl": "https://register.gotowebinar.com/..."
  }
}
```

### Optional: Sync Status Table

For tracking sync operations:

```sql
CREATE TABLE IF NOT EXISTS `webinar_sync_log` (
    `id` BINARY(16) NOT NULL,
    `operation` VARCHAR(50) NOT NULL,
    `webinar_key` VARCHAR(50),
    `status` VARCHAR(20) NOT NULL,
    `details` JSON,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
);
```

---

## 5. App Structure

### Directory Layout

```
WebinarIntegration/
├── manifest.xml                      # App configuration
├── Resources/
│   ├── app/
│   │   └── administration/
│   │       └── src/
│   │           └── module/
│   │               └── webinar-config/    # Admin UI
│   ├── config/
│   │   └── config.xml               # App settings
│   └── views/
│       └── storefront/
│           └── page/
│               └── checkout/
│                   └── confirm/
│                       └── webinar-info.html.twig
├── src/
│   ├── Service/
│   │   ├── GoToWebinarClient.php    # API client
│   │   ├── WebinarRegistrationService.php
│   │   ├── WebinarSyncService.php
│   │   └── TokenManager.php         # OAuth token handling
│   ├── Webhook/
│   │   ├── OrderPlacedHandler.php   # Handle order webhooks
│   │   └── WebinarUpdateHandler.php # Handle GoTo webhooks
│   ├── Command/
│   │   ├── SyncWebinarsCommand.php  # CLI sync
│   │   └── TestRegistrationCommand.php
│   └── Controller/
│       └── Admin/
│           └── WebinarController.php # Admin API
├── tests/
│   ├── Service/
│   └── Webhook/
└── composer.json
```

---

## 6. Key Features Breakdown

### Feature 1: OAuth Connection Setup
**Effort:** 2-3 hours  
**Priority:** Critical

- Admin UI to connect GoTo Webinar account
- OAuth flow implementation
- Token storage and refresh mechanism
- Connection status indicator

### Feature 2: Webinar Product Sync
**Effort:** 3-4 hours  
**Priority:** High

- Fetch webinars from GoTo API
- Create/update products in Shopware
- Map webinar details to product custom fields
- CLI command for manual sync
- Scheduled task for automatic sync

### Feature 3: Order Processing & Registration
**Effort:** 4-5 hours  
**Priority:** Critical

- Webhook handler for order placement
- Extract customer data
- Call GoTo registration API
- Store registration details
- Error handling and retry logic

### Feature 4: Customer Communication
**Effort:** 2-3 hours  
**Priority:** High

- Custom email template with webinar details
- Include join URL and calendar invite
- Reminder emails (optional)
- Order confirmation page showing webinar info

### Feature 5: Admin Dashboard
**Effort:** 3-4 hours  
**Priority:** Medium

- View registration status per order
- Manual registration retry
- Registration analytics
- Error log viewer

### Feature 6: Testing & Error Handling
**Effort:** 3-4 hours  
**Priority:** High

- Unit tests for services
- Integration tests with mock API
- Error scenario handling
- Logging and monitoring

---

## 7. Configuration Requirements

### App Configuration (config.xml)

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <card>
        <title>GoTo Webinar Connection</title>
        <title lang="de-DE">GoTo Webinar Verbindung</title>
        
        <input-field type="text">
            <name>organizerKey</name>
            <label>Organizer Key</label>
            <placeholder>Enter your organizer key</placeholder>
            <required>true</required>
        </input-field>
        
        <input-field type="bool">
            <name>autoSync</name>
            <label>Auto-sync webinars</label>
            <defaultValue>true</defaultValue>
        </input-field>
        
        <input-field type="int">
            <name>syncInterval</name>
            <label>Sync interval (hours)</label>
            <defaultValue>6</defaultValue>
        </input-field>
        
        <input-field type="bool">
            <name>sendCustomEmails</name>
            <label>Send custom confirmation emails</label>
            <defaultValue>true</defaultValue>
        </input-field>
    </card>
</config>
```

---

## 8. API Endpoints (Our App)

### Admin API

**GET** `/api/_action/webinar-integration/status`
- Check connection status with GoTo Webinar
- Returns: OAuth status, last sync time, error count

**POST** `/api/_action/webinar-integration/sync`
- Manually trigger webinar sync
- Returns: Number of webinars synced, errors

**GET** `/api/_action/webinar-integration/registrations`
- Get list of all webinar registrations
- Filters: date range, status, webinar

**POST** `/api/_action/webinar-integration/registrations/{orderLineItemId}/retry`
- Retry failed registration
- Returns: New registration status

### Webhooks (Incoming from Shopware)

**POST** `/api/webhooks/order-placed`
- Triggered when order is placed
- Processes webinar registrations

**POST** `/api/webhooks/order-paid`
- Triggered when payment confirmed
- Alternative trigger point for registrations

---

## 9. Testing Strategy

### Unit Tests
- GoToWebinarClient methods
- Token refresh logic
- Data mapping/transformation
- Error handling

### Integration Tests
- Full registration flow with mock API
- Webhook handling
- OAuth flow (with test credentials)
- Email sending

### Manual Testing Checklist
- [ ] Connect GoTo Webinar account
- [ ] Sync webinars to products
- [ ] Purchase webinar ticket
- [ ] Verify registration in GoTo
- [ ] Receive confirmation email
- [ ] Test error scenarios
- [ ] Test with multiple webinars in cart
- [ ] Test registration retry
- [ ] Test admin dashboard

---

## 10. Deployment Checklist

### Prerequisites
- [ ] GoTo Webinar Developer account created
- [ ] OAuth client credentials obtained
- [ ] Test webinar created in GoTo account
- [ ] Shopware 6.5+ instance running
- [ ] App server accessible via HTTPS (for webhooks)

### Installation Steps
1. Upload app to Shopware instance
2. Install app via Admin → Extensions
3. Configure OAuth credentials
4. Connect GoTo Webinar account
5. Run initial webinar sync
6. Create test product manually
7. Test complete purchase flow
8. Monitor logs for errors

### Production Considerations
- [ ] Set up proper logging (Sentry/Datadog)
- [ ] Configure retry limits and timeouts
- [ ] Set up monitoring alerts
- [ ] Document support procedures
- [ ] Plan for API rate limits
- [ ] Consider webhook signature verification
- [ ] Set up backup email notification

---

## 11. Known Limitations & Future Enhancements

### MVP Limitations
- Single organizer support only
- No support for recurring webinars
- No automated refund handling
- Basic error recovery
- English only

### Future Enhancements
- **Multi-organizer support** - Manage multiple GoTo accounts
- **Recurring webinars** - Handle series/recurring sessions
- **Cancellation handling** - Unregister when order cancelled
- **Attendance tracking** - Sync attendance data back to Shopware
- **Custom registration questions** - Map to GoTo custom fields
- **Webinar categories** - Better product organization
- **Capacity management** - Prevent overselling
- **Waiting list** - When webinar is full
- **Multi-language** - German translations

---

## 12. Cost & Resource Estimation

### Development Time (MVP)
- **Setup & Planning:** 4 hours
- **OAuth Integration:** 4 hours
- **Product Sync:** 6 hours
- **Registration Flow:** 8 hours
- **Email Templates:** 3 hours
- **Admin Interface:** 6 hours
- **Testing:** 6 hours
- **Documentation:** 3 hours
- **Total:** ~40 hours (5 days)

### Technical Resources Needed
- Shopware 6.5+ instance
- GoTo Webinar Developer account (free)
- SSL certificate (for webhooks)
- Email service (SMTP/SendGrid)
- Optional: External app server (if not hosting in Shopware)

### Ongoing Costs
- GoTo Webinar subscription (varies by plan)
- Hosting/server costs
- Email service costs (if high volume)
- Monitoring service (optional)

---

## 13. Risk Assessment

### Technical Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|-----------|------------|
| GoTo API changes | High | Medium | Version API calls, monitor changelog |
| Rate limiting | Medium | Low | Implement queue, respect limits |
| Token expiry | Medium | Medium | Robust refresh mechanism, alerts |
| Webhook delivery failure | High | Low | Retry logic, manual trigger option |
| Duplicate registrations | Low | Medium | Check existing before creating |

### Business Risks

| Risk | Impact | Likelihood | Mitigation |
|------|--------|-----------|------------|
| Customer doesn't receive access | High | Low | Send immediate confirmation, log all attempts |
| Webinar capacity exceeded | Medium | Low | Sync capacity, show availability |
| GoTo service outage | High | Low | Queue registrations, retry automatically |
| Payment but no registration | High | Low | Transaction logging, manual retry |

---

## 14. Success Metrics

### Technical KPIs
- Registration success rate > 99%
- API response time < 2s
- Webhook processing time < 5s
- Zero data loss
- Error rate < 1%

### Business KPIs
- Customer satisfaction (survey)
- Support ticket volume
- Registration completion rate
- Revenue from webinar products
- Repeat webinar purchases

---

## 15. Next Steps

### Phase 1: MVP Development (Week 1-2)
1. ✅ Planning and architecture (this document)
2. ⏳ OAuth setup and testing
3. ⏳ Basic registration flow
4. ⏳ Essential error handling
5. ⏳ Simple admin interface

### Phase 2: Polish & Testing (Week 3)
1. Comprehensive testing
2. Email templates
3. Admin dashboard
4. Documentation
5. Bug fixes

### Phase 3: Production Deployment (Week 4)
1. Production deployment
2. Monitoring setup
3. Support documentation
4. User training
5. Feedback collection

### Phase 4: Enhancements (Month 2+)
1. Feature requests from Phase 1
2. Performance optimizations
3. Advanced analytics
4. Multi-language support
5. Additional GoTo features

---

## 16. Reference Links

**GoTo Webinar API:**
- [API Overview](https://developer.goto.com/GoToWebinarV2#section/GoTo-Webinar-API-Overview)
- [Getting Started](https://developer.goto.com/guides/Get%20Started/00_Ref-Get-Started/)
- [Create Client](https://developer.goto.com/guides/Get%20Started/02_HOW_createClient/)
- [Create Registrant](https://developer.goto.com/GoToWebinarV2#tag/Registrants/operation/createRegistrant)

**Shopware App Development:**
- [App Base Guide](https://developer.shopware.com/docs/guides/plugins/apps/app-base-guide.html)
- [Apps Overview](https://developer.shopware.com/docs/guides/plugins/apps/)
- [Webhooks](https://developer.shopware.com/docs/guides/plugins/apps/app-scripts/)
- [Admin SDK](https://developer.shopware.com/docs/guides/plugins/apps/admin-sdk)

---

**Document Version:** 1.0  
**Last Updated:** November 28, 2025  
**Status:** Ready for Development  
**Approved By:** [Pending Review]

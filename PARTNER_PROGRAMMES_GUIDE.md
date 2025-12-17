# 🚀 Partner Programmes - Setup & User Guide

## 🎯 Overview

The **Partner Programme** system allows brands and admins to create referral opportunities for users to earn commissions by referring clients.

---

## ⚡ Quick Setup (5 Minutes)

### **Step 1: Create Database Tables**

Run the SQL file in phpMyAdmin or MySQL:

```bash
mysql -u root -p your_database < admin/database_partner_programmes.sql
```

**OR** run these queries manually:
- Creates `partner_programmes` table
- Creates `partner_referrals` table

### **Step 2: Configure Email**

Edit both files and update admin email:

**`admin/src/ui/partner_programmes.php`** (line ~90)
**`user/src/ui/biz.php`** (line ~155)

```php
$admin_email = "your-admin@zokli.com"; // Change this
```

### **Step 3: Test Access**

**Admin**: `http://localhost/qr/admin/src/ui/partner_programmes.php`  
**User**: `http://localhost/qr/user/src/ui/biz.php`

---

## 📦 What's Included

### **Admin Side** (`partner_programmes.php`)

✅ **Create Partner Programmes**
- Programme Header (e.g., "Sell Life Insurance")
- Company Name
- Product/Company Link  
- Description
- Commission Details
- Company Email

✅ **Manage Programmes**
- View all programmes
- Toggle Active/Inactive
- Delete programmes
- View referral count

✅ **Track Referrals**
- View all referrals across programmes
- Filter by status (Open/In Process/Closed)
- Update referral status
- Add notes

### **User Side - "Biz"** (`biz.php`)

✅ **Browse Programmes**
- View active partner programmes
- See commission structure
- Visit company websites

✅ **Submit Referrals**
- Enter client details (Name, Phone, Email)
- Specify product required
- Instant submission

✅ **Track Referrals**
- View all your referrals
- See status updates
- Monitor progress

---

## 🎨 Features

### **Email Notifications (3 Parties)**

When a user submits a referral, emails are sent to:

1. **Company/Brand** (`company_email` in programme)
   - Client details
   - Product required
   - Referrer information

2. **Zokli/Admin** (`admin@zokli.com`)
   - Full referral details
   - For tracking/reporting

3. **User/Referrer** (user's email)
   - Confirmation of submission
   - What to expect next

### **Status Tracking**

- 🔵 **Open** - New referral submitted
- 🟡 **In Process** - Company is working on it
- 🟢 **Closed** - Deal completed

### **Commission Management**

Admins specify commission structure:
- Percentage-based (e.g., "10% on first year premium")
- Fixed amount (e.g., "₹5,000 per successful referral")
- Tiered structure
- Payment timeline

---

## 📊 Usage Flow

### **Admin Creates Programme**

1. Log in to admin panel
2. Navigate to **Partner Programmes**
3. Click **"Create Programme"**
4. Fill in details:
   - Header: "Sell Life Insurance"
   - Company: "ABC Insurance Ltd."
   - Link: https://abcinsurance.com
   - Description: "Partner with India's leading insurance provider"
   - Commission: "10% commission on first year premium, paid quarterly"
   - Email: company@abcinsurance.com
5. Submit

### **User Submits Referral**

1. Navigate to **More → Biz**
2. Browse available programmes
3. Click **"Refer a Client"**
4. Enter client details:
   - Name: John Doe
   - Phone: 9876543210
   - Email: john@example.com
   - Product: Term Life Insurance
5. Submit
6. Emails sent to all 3 parties

### **Admin Tracks Progress**

1. Go to **"All Referrals"** tab
2. Filter by status/programme
3. Click edit icon
4. Update status: Open → In Process → Closed
5. Add notes if needed

---

## 🎯 Programme Examples

### **Example 1: Life Insurance**
- **Header**: Sell Life Insurance
- **Company**: ABC Insurance Ltd.
- **Commission**: 10% of first year premium
- **Products**: Term Life, Whole Life, Child Plans

### **Example 2: Real Estate**
- **Header**: Real Estate Referrals
- **Company**: XYZ Properties
- **Commission**: ₹10,000 per successful sale
- **Products**: Apartments, Villas, Commercial Space

### **Example 3: Mutual Funds**
- **Header**: Mutual Fund Investments
- **Company**: Investment Advisors Inc.
- **Commission**: 0.5% on assets under management
- **Products**: Equity Funds, Debt Funds, Hybrid Funds

---

## 💡 Best Practices

### **For Admins**

✅ **Clear Commission Structure**
- Be specific about percentages/amounts
- Include payment timeline
- Mention minimum requirements

✅ **Programme Description**
- Highlight benefits
- Mention target audience
- Include unique selling points

✅ **Quick Status Updates**
- Update referrals promptly
- Add notes for context
- Close completed deals

### **For Users**

✅ **Quality Referrals**
- Refer genuinely interested clients
- Provide accurate contact information
- Specify exact product requirements

✅ **Follow Up**
- Check status regularly
- Maintain communication with clients
- Build long-term relationships

---

## 📧 Email Templates

### **To Company**
```
Subject: New Referral: Sell Life Insurance

New lead from Zokli Partner Programme:

Client: John Doe
Phone: 9876543210
Email: john@example.com
Product Required: Term Life Insurance

Referred By: Sarah (sarah@example.com)

Commission: 10% of first year premium
```

### **To User**
```
Subject: Referral Confirmation: Sell Life Insurance

Your referral has been submitted successfully!

Client: John Doe
Programme: Sell Life Insurance
Product: Term Life Insurance

The company will contact your client within 48 hours.
You'll earn 10% commission when the deal closes.

Track status in Biz → My Referrals
```

---

## 🔐 Security

- ✅ Session-based authentication
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (HTML escaping)
- ✅ Input validation
- ✅ Access control (user-specific data)

---

## 📱 Mobile Responsive

Both admin and user pages are fully responsive:
- Desktop: Multi-column layouts
- Tablet: Optimized grids
- Mobile: Single column, touch-friendly

---

## 🛠️ Troubleshooting

### Issue: Tables don't exist
**Solution**: Run `database_partner_programmes.sql`

### Issue: Emails not sending
**Solution**: 
1. Update admin email in PHP files
2. Configure SMTP for production
3. Check spam folders

### Issue: Can't create programmes
**Solution**: Check admin is logged in and has permissions

### Issue: Referrals not showing
**Solution**: Check browser console for errors, verify programme is active

---

## 🎊 Complete Feature List

| Feature | Admin | User |
|---------|-------|------|
| Create Programmes | ✅ | ❌ |
| View Programmes | ✅ | ✅ |
| Submit Referrals | ❌ | ✅ |
| Track All Referrals | ✅ | Own Only |
| Update Status | ✅ | View Only |
| Toggle Programme Active/Inactive | ✅ | ❌ |
| Delete Programmes | ✅ | ❌ |
| Email Notifications | ✅ | ✅ |
| Filter Referrals | ✅ | ❌ |
| Add Notes | ✅ | ❌ |

---

## 🚀 Next Steps

1. **Create Test Programme**
   - Use sample data
   - Test email flow

2. **Submit Test Referral**
   - Use your own details
   - Check all 3 emails

3. **Track Progress**
   - Update status
   - Add notes

4. **Go Live!**
   - Create real programmes
   - Onboard partners

---

## 📞 Navigation

- **Admin**: Sidebar → Partner Programmes
- **User**: Footer → More → Biz

---

**Your Partner Programme is ready to generate leads! 🎯**

*Start connecting businesses with customers today.*

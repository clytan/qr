# Influencer Collaborations Feature

## Overview
A comprehensive influencer collaboration management system that allows brands and admins to create collaboration requests and influencers to accept them, with automated email notifications to all parties.

## Features

### 🎯 **For Brands & Admins**
- Create detailed collaboration requests
- Upload up to 3 product photos (downloadable)
- Specify product categories (lifestyle, skincare, haircare, etc.)
- Define financial terms (Barter or Paid)
- Track collaboration status (Pending, Active, Completed)
- Manage all collaborations from a central dashboard

### 💼 **For Influencers**
- View all available collaboration opportunities
- Download product photos
- Review detailed collaboration requirements
- Accept collaborations with one click
- Receive email confirmation upon acceptance

### 📧 **Email Notifications**
Upon collaboration acceptance, automated emails are sent to:
1. **Brand** - Confirmation and influencer details
2. **Influencer** - Collaboration details and next steps
3. **Admin** - Record of new active collaboration

### 🎨 **User Interface**
- **3 Separate Tabs** with distinct banner sets:
  - **Pending Requests**: View and manage pending collaboration requests
  - **Active Collabs**: Monitor ongoing collaborations
  - **Completed**: Review finished collaborations
- **Minimum 3 Banners per tab**: Each tab features unique banners with:
  - Animated gradient backgrounds
  - Numbered progression (01, 02, 03)
  - Icon-based visual hierarchy
  - Descriptive content for user guidance

## Installation

### 1. Database Setup
Run the SQL script to create the required table:

```bash
mysql -u your_username -p your_database < admin/database_influencer_collabs.sql
```

Or manually execute the SQL in phpMyAdmin or your MySQL client:

```sql
-- See database_influencer_collabs.sql for the full schema
CREATE TABLE influencer_collabs (...)
```

### 2. File Upload Directory
The system will automatically create the uploads directory, but you can manually create it:

```bash
mkdir -p uploads/collabs
chmod 777 uploads/collabs
```

### 3. Email Configuration
Update the email settings in `admin/src/ui/collaborations.php`:

```php
// Line ~230
$admin_email = "admin@yourcompany.com"; // Change this to your admin email
```

For production, configure proper SMTP settings using PHPMailer or similar.

### 4. Access the Feature
Navigate to: **Admin Panel → Influencer Collabs**

## Usage Guide

### Creating a Collaboration Request

1. Click **"Create New Collab"** button
2. Fill in the form:
   - **Collaboration Title**: e.g., "Summer Skincare Campaign"
   - **Category**: Select from dropdown (lifestyle, skincare, etc.)
   - **Product Description**: Detailed description of the product
   - **Product Link**: URL to product page (optional)
   - **Product Photos**: Upload 1-3 high-quality images
   - **Financial Details**: Choose Barter or Paid
     - If Paid, enter the amount in ₹
   - **Detailed Summary**: Requirements, deliverables, timeline
   - **Brand Email**: Contact email for notifications
3. Click **"Create Collaboration"**

### Managing Collaborations

#### Pending Tab
- View all pending collaboration requests
- Download product photos
- Delete unwanted requests

#### Active Tab
- Monitor ongoing collaborations
- View influencer details
- Mark collaborations as completed
- Download assets

#### Completed Tab
- Review past collaborations
- Analyze performance
- Reference for future campaigns

### Accepting Collaborations (For Influencers)
*Note: This requires integration with the influencer dashboard*

1. View available collaborations
2. Review all details and photos
3. Click **"Accept Collaboration"**
4. Receive email confirmation with all details

## Categories Supported

- **Lifestyle**
- **Skincare**
- **Haircare**
- **Fashion**
- **Fitness**
- **Food & Beverage**
- **Technology**
- **Other**

## Financial Options

### Barter
- Product exchange
- No monetary payment
- Perfect for product reviews

### Paid
- Specify payment amount in ₹
- Track financial commitments
- Professional partnerships

## Technical Details

### Database Schema
```sql
influencer_collabs (
  id, collab_title, category, product_description,
  product_link, photo_1, photo_2, photo_3,
  financial_type, financial_amount, detailed_summary,
  brand_email, status, created_by, created_on,
  accepted_by, accepted_on, completed_on, is_deleted
)
```

### File Structure
```
admin/
├── src/
│   ├── ui/
│   │   └── collaborations.php (Main page)
│   └── components/
│       └── sidebar.php (Updated with menu item)
├── database_influencer_collabs.sql (Database schema)
└── README_COLLABORATIONS.md (This file)

uploads/
└── collabs/ (Product photos storage)
```

### Security Features
- SQL injection prevention via prepared statements
- File upload validation (images only)
- XSS protection with HTML escaping
- Authentication required (admin panel)

## Customization

### Email Template
Modify the `sendCollabAcceptanceEmails()` function in `collaborations.php` to customize:
- Email subject
- HTML template
- Brand colors
- Footer information

### UI Styling
All styles are contained in the `<style>` section of `collaborations.php`:
- Modify color gradients
- Adjust banner animations
- Change card layouts
- Update responsive breakpoints

### Banner Content
Edit the banner cards in each tab section:
- Update icons (Font Awesome)
- Change titles and descriptions
- Add or remove banners
- Modify animations

## Browser Support
- Chrome (Latest)
- Firefox (Latest)
- Safari (Latest)
- Edge (Latest)

## Mobile Responsive
Fully responsive design with:
- Adaptive grid layouts
- Touch-friendly buttons
- Mobile-optimized forms
- Responsive navigation

## Support & Troubleshooting

### Common Issues

**Uploads not working:**
- Check directory permissions: `chmod 777 uploads/collabs`
- Verify PHP upload_max_filesize setting
- Check disk space

**Emails not sending:**
- Configure SMTP settings
- Check server mail() function
- Verify email addresses are valid
- Check spam folders

**Database errors:**
- Ensure table is created
- Check column names match
- Verify connection settings

## Future Enhancements
- [ ] Influencer dashboard integration
- [ ] Performance metrics tracking
- [ ] Advanced filtering and search
- [ ] Bulk operations
- [ ] Export to CSV/PDF
- [ ] Calendar integration
- [ ] Real-time notifications
- [ ] Contract management

## Credits
Developed for the QR Admin Panel
Built with PHP, MySQL, jQuery, and Font Awesome

---

**Version:** 1.0  
**Last Updated:** December 2025  
**License:** Proprietary
